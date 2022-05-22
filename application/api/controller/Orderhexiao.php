<?php

namespace app\api\controller;

use app\api\validate\OrderhexiaoValidate;
use app\admin\model\WriteoffModel;
use app\common\model\OrderhexiaoModel;
use app\common\model\OrderexceptionModel;

use app\common\Redis;
use think\Db;
use think\facade\Log;
use think\Request;
use think\Controller;
use Zxing\QrReader;

class Orderhexiao extends Controller
{

    /**
     * 核销商上传推单
     * @return \think\response\Json
     */
    public function uploadOrder()
    {

        $data = @file_get_contents("php://input");
        $param = json_decode($data, true);
        logs(json_encode(['message' => $param, "time" => date("Y-m-d H:i:s", time())]), 'uploadOrder_log');
        $orderExceptionModel = new OrderexceptionModel();
        if (mt_rand(0, 9) > 5) {
            usleep(3000);
        }
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
                return json(msg(-2, '', 'Useless write-off'));
            }
            $md5Sting = $param['write_off_sign'] . $param['order_no'] . $param['account'] . $param['order_amount'] . $param['limit_time'] . $param['notify_url'] . $writeOff['token'];
            $doMd5 = md5($md5Sting);
            if (md5($param['write_off_sign'] . $param['order_no'] . $param['account'] . $param['order_amount'] . $param['limit_time'] . $param['notify_url'] . $writeOff['token']) != $param['sign']) {
//                logs(json_encode(['param' => $param, 'md5Sting' => $md5Sting, 'md5' => $doMd5]), 'uploadOrder_md5');
                return json(msg(-3, '', 'check sign fail!'));
            }
            $orderHeXModel = new OrderhexiaoModel();

            $redis = new Redis();
            $setRes = $redis->setnx($param['account'], $param['order_no'], 30);
            if (!$setRes) {
                return json(msg(-6, '', "请勿重新上传"));
            }
            //开始插入
            $isHasOrder = Db::table("bsa_order_hexiao")
                ->where('order_no', '=', $param['order_no'])
                ->find();

            //存在相同单号回滚返回
            if ($isHasOrder) {
                return json(msg(-5, '', '订单号已存在!'));
            }

            $isHasWhere[] = ['account', '=', $param['account']];
//            $isHasWhere[] = ['notify_status', '<>', 1];
            //是否存在未支付相同手机号订单号 回滚返回
            $isHas = Db::table("bsa_order_hexiao")
                ->where($isHasWhere)
                ->select();

            if (!empty($isHas)) {
                foreach ($isHas as $k => $v) {
                    if ($v['notify_status'] <> 1) {
                        return json(msg(-4, '', '该账号有未回调订单!'));
                    }
                }
            }
//            Db::commit();

            $addParam = $param;
            unset($addParam['sign']);
            $addParam['add_time'] = time();
            $addParam['order_serial'] = $orderHeXModel->createOrderSerial();
            $addParam['status'] = 0;
            $where['account'] = $param['account'];
            $where['order_no'] = $param['order_no'];
//            Db::commit();
            $res = $orderHeXModel->addOrder($where, $addParam);
//
            if ($res['code'] != 0) {
                $redis->delete($param['account']);
                return json(msg(-6, $addParam['account'], "上传失败，重复上传"));
            }

            return json(msg(1, '', "success"));

        } catch (\Exception $exception) {
            logs(json_encode(['param' => $param, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'uploadOrder_exception');
            return json(msg(-11, '', '下单异常:uploadOrder_exception!' . $exception->getMessage()));
        } catch (\Error $error) {
            logs(json_encode(['param' => $param, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'uploadOrder_error');
            return json(msg(-22, '', '下单异常:uploadOrder_exception!' . $error->getMessage()));
        }
    }

    /**
     * 推单查询状态
     */
    public function orderInfo(Request $request)
    {
        $param = $request->param();
        $data = @file_get_contents('php://input');
        $param = json_decode($data, true);
        logs(json_encode(['message' => $param, "time" => date("Y-m-d H:i:s", time())]), 'orderInfoHxOrder_log');

        try {
            $validate = new OrderhexiaoValidate();
            if (!$validate->scene("orderInfo")->check($param)) {
                return json(msg(-1, '', $validate->getError()));
            }
            //验签
            $writeOffModel = new WriteoffModel();
            $writeOff = $writeOffModel->where(['write_off_sign' => $param['write_off_sign']])->find();
            if (empty($writeOff)) {
                return json(msg(-1, '', '错误的核销商'));
            }
            if (md5($param['write_off_sign'] . $param['order_no'] . $param['account'] . $writeOff['token']) != $param['sign']) {
                return json(msg(-1, '', 'fuck you!'));
            }
            $orderHXModel = new OrderhexiaoModel();
            $where['account'] = $param['account'];
            $where['order_no'] = $param['order_no'];
            $res = $orderHXModel->where($where)->find();

            if (!$res) {
                return json(msg(-2, $where['order_no'], "没有此核销单"));
            }
            if ($res['pay_status'] != 1) {
                return json(msg(2, $where['order_no'], "success"));
            }
            return json(msg(1, $where['order_no'], "success"));

        } catch (\Exception $exception) {
            logs(json_encode(['param' => $param, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'orderInfo_exception');
            return apiJsonReturn('-11', "orderInfo exception!" . $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['param' => $param, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'orderInfo_error');
            return json(msg('-22', '', 'orderInfo error!' . $error->getMessage()));
        }
    }

    public function getStrTest()
    {

    }

}