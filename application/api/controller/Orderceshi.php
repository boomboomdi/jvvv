<?php

namespace app\api\controller;


use app\admin\model\WriteoffModel;
use app\api\validate\OrderhexiaoValidate;
use app\common\model\OrderexceptionModel;
use app\common\model\SystemConfigModel;
use think\Controller;
use think\Db;
use app\common\model\OrderhexiaoModel;
use app\common\Redis;
use think\Request;
use think\Validate;

class Orderceshi extends Controller
{

    public function getOrderHxLockTime()
    {

        $orderHxLockTime = SystemConfigModel::getOrderHxLockTime();
        var_dump($orderHxLockTime);exit;
    }

    public function ceshiAdd()
    {

        $redis = new Redis();
//        var_dump($redis);exit;
        $account = "123123123";
        $redis->set($account, $account, 180);
        $isHas = $redis->get($account);
        var_dump($isHas);
//        exit;
    }

    public function ceshiAdd2()
    {

        $redis = new Redis();
//        var_dump($redis);exit;
        $account = "ces1i";
        $ishas = $redis->get($account);
        if (empty($ishas)) {
            echo "重新设置";
            $redis->set($account, $account, 180);
        } else {
            echo($redis->get($account));
        }
    }

    public function getCeshi()
    {

        $redis = new Redis();
//        $redis->set('test',"1111111111111");
        $res = $redis->setnx('test1', "bbb", 10);
        var_dump($res);
        exit;
        echo $redis->get('test1');  //2结果：1111111111111
//        var_dump($res);  //结果：
    }

    public function deleteCeShi()
    {

        $redis = new Redis();
        $res = $redis->get('test1');  //2结果：1111111111111
        var_dump($res);
        exit;  //结果：
        $account = "first";
        $setRes = $redis->set($account, $account, 180);
        $setRes = $redis->setnx($account, $account, 180);
        var_dump($setRes);
        exit;
        $redis->delete($account);
        $ishas = $redis->get($account);
        echo $ishas;
    }

    public function deleteCeShi1()
    {

        $orderHxLockTime = SystemConfigModel::getOrderHxLockTime();
        var_dump($orderHxLockTime);
        exit;
        $redis = new Redis();
        $account = "first";
        $redis->delete($account);

    }

    public function deletedd()
    {

        $redis = new Redis();
        $account = "first";
        $ishas = $redis->get($account);
        echo $ishas;
    }

    /**
     * 核销商上传推单
     * @return \think\response\Json
     */
    public function uploadOrder()
    {
        $redis = new Redis();
        $data = @file_get_contents("php://input");
        $param = json_decode($data, true);
        logs(json_encode(['message' => $param, "time" => date("Y-m-d H:i:s", time())]), 'uploadOrder_log');
        $orderExceptionModel = new OrderexceptionModel();
        try {
            $validate = new OrderhexiaoValidate();
            if (!$validate->scene('uploadOrder')->check($param)) {
                return json(msg(-1, '', $validate->getError()));
            }
            $operatorArray[] = '移动';
            $operatorArray[] = '联通';
            $operatorArray[] = '电信';
            //operator 运营商  in_array($input,$allowedChars)
            if (!in_array($param['operator'], $operatorArray)) {
                return json(msg(-1, '', "运营商格式错误"));
            }

            //验签
            $writeOffModel = new WriteoffModel();
            $writeOff = $writeOffModel->where(['write_off_sign' => $param['write_off_sign']])->find();
            if (empty($writeOff)) {
                $exception['order_no'] = $param['order_no'];
                $exception['content'] = $data;
                $exception['action_result'] = 'Useless write-off';
                $exception['desc'] = "核销售商不存在！";
                $orderExceptionModel->addLog($param['write_off_sign'], 'uploadOrder', $exception);
                return json(msg(-2, '', 'Useless write-off'));
            }
            $md5Sting = $param['write_off_sign'] . $param['order_no'] . $param['account'] . $param['order_amount'] . $param['limit_time'] . $param['notify_url'] . $writeOff['token'];
            $doMd5 = md5($md5Sting);
            if (md5($param['write_off_sign'] . $param['order_no'] . $param['account'] . $param['order_amount'] . $param['limit_time'] . $param['notify_url'] . $writeOff['token']) != $param['sign']) {
//                logs(json_encode(['param' => $param, 'md5Sting' => $md5Sting, 'md5' => $doMd5]), 'uploadOrder_md5');
                $exception['order_no'] = $param['order_no'];
                $exception['action_result'] = 'check sign fail!';
                $exception['content'] = json_encode(['param' => $param, 'md5Sting' => $md5Sting, 'md5' => $doMd5]);
                $exception['desc'] = "上传签名有误！";
                $orderExceptionModel->addLog($param['write_off_sign'], 'uploadOrder', $exception);
                return json(msg(-3, '', 'check sign fail!'));
            }
            $orderHeXModel = new OrderhexiaoModel();

            //查询是否已经有有相同手机号
            $isHasWhere['account'] = $param['account'];
            $isHasWhere['notify_status'] = 0;
            $isHas = $orderHeXModel->where($isHasWhere)->find();
            if ($isHas) {
                $exception['order_no'] = $param['order_no'];
                $exception['action_result'] = 'check sign fail!';
                $exception['content'] = json_encode(['param' => $param]);
                $exception['desc'] = "有未回调订单" . $isHas['order_no'];
                $orderExceptionModel->addLog($param['write_off_sign'], 'uploadOrder', $exception);
                return json(msg(-4, '', '该账号有未回调订单!'));
            }

            $addParam = $param;
            unset($addParam['sign']);
            $addParam['add_time'] = time();
            $addParam['status'] = 0;
            $where['account'] = $param['account'];
            $where['order_no'] = $param['order_no'];

            $res = $orderHeXModel->addOrder($where, $addParam);

            if ($res['code'] != 0) {
                $exception['order_no'] = $param['order_no'];
                $exception['content'] = json_encode(['param' => $param, 'md5Sting' => $md5Sting, 'md5' => $doMd5]);
                $exception['action_result'] = json_encode($res);
                $exception['desc'] = "上传签名有误！";
                $orderExceptionModel->addLog($param['write_off_sign'], 'uploadOrder', $exception);
//                logs(json_encode(['addParam' => $addParam, 'addRes' => $res, "time" => date("Y-m-d H:i:s", time())]), 'uploadOrder_log');
                return json(msg(-5, '', $res['msg']));
            }
////            $returnData['code'] = 1;
//            $returnData['order_no'] = $param['order_no'];
            if (($param['limit_time'] - time()) > 420) ;
            $hxOrderKey = $param['write_off_sign'] . "" .
                $redis->lpush($param['write_off_sign'] . "upload", ($param['limit_time'] - time()));
            return json(msg(1, '', "success"));

        } catch (\Exception $exception) {
            logs(json_encode(['param' => $param, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'uploadOrder_exception');
            return json(msg('-11', '', '下单异常:uploadOrder_exception!' . $exception->getMessage()));
        } catch (\Error $error) {
            logs(json_encode(['param' => $param, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'uploadOrder_error');
            return json(msg('-22', '', '下单异常:uploadOrder_exception!' . $error->getMessage()));
        }
    }

    public function ceshi1(Request $request)
    {
        $autoCheckOrderTime = SystemConfigModel::getAutoCheckOrderTime();
        var_dump($autoCheckOrderTime);
        exit;
//        $rootPath = $request->domain();
//        var_dump($rootPath);exit;
    }

    public function rand()
    {
//        156975286加十位时间戳
//        $metas = range(0, 9);
//        $metas = array_merge($metas, range('A', 'Z'));
//        $metas = array_merge($metas, range('a', 'z'));
//        $str = '';
//        for ($i = 0; $i < 10; $i++) {
//            $str .= $metas[rand(0, count($metas) - 1)];
//        }
//        return $str;

        $orderHxOrder = new OrderhexiaoModel();
//        return rand(3);
//        $lenth = strlen($orderHxOrder->createOrderSerial());
//        echo  $lenth."</br>";
        echo $orderHxOrder->createOrderSerial();
    }

    public function getMillisecond()
    {

        list($msec, $sec) = explode(' ', microtime());

        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        return $msectimes = substr($msectime, 0, 13);

    }

    /**
     *
     * @param $what 1:数字|2:字母
     * @param $number
     * @return string
     */
    function getRandString($what, $number)
    {
        $string = '';
        for ($i = 1; $i <= $number; $i++) {
            $panduan = 1;
            if ($what == 3) {
                if (rand(1, 2) == 1) {
                    $what = 1;
                } else {
                    $what = 2;
                }
                $panduan = 2;
            }
            if ($what == 1) {
                $string .= rand(0, 9);
            } elseif ($what == 2) {
                $rand = rand(0, 24);
                $b = 'a';
                for ($a = 0; $a <= $rand; $a++) {
                    $b++;
                }
                $string .= $b;
            }
            if ($panduan == 2) $what = 3;
        }
        return $string;
    }
}