<?php

namespace app\api\controller;

use app\common\model\DeviceModel;
use app\common\model\NotifylogModel;
use app\api\validate\NotifylogValidate;
use app\api\validate\DeviceapiValidate;
use app\common\model\OrderModel;
use think\Db;
use think\facade\Log;
use think\Request;
use think\Controller;
use Zxing\QrReader;

class Device extends Controller
{

    public function queryBalance(Request $request)
    {
        $data = @file_get_contents('php://input');
        $param = json_decode($data, true);
        logs(json_encode([
            'param' => $param,
            'ip' => $request->ip(),
            'startTime' => date("Y-m-d H:i:s", time())
        ]), 'queryBalance');
        try {
            $validate = new DeviceapiValidate();
            if (!$validate->scene('ping')->check($param)) {
                return json(msg(-1, '', $validate->getError()));
            }
            if (isset($param['type'])) {
                unset($param['type']);
            }
            $deviceModel = new DeviceModel();
            $updateParam['account'] = $param['account'];
            $updateParam['heart_time'] = time();
            $updateParam['device_status'] = 1;
//            $updateParam['studio'] = $param['studio'];
            $where['account'] = $param['account'];
            $where['studio'] = $param['studio'];

            $res = $deviceModel->devicePing($where, $updateParam);

            if ($res['code'] != 0) {
                return json(msg('-2', '', $res['msg']));
            }
            return json(msg('1', '', "ping success"));

        } catch (\Exception $e) {
            Log::error('ping error!', $param);
            return json(msg('-11', '', 'saveBase64toImg error!' . $e->getMessage()));
        }
    }

    /**
     * 心跳
     * @param Request $request
     * @return void
     */
    public function ping(Request $request)
    {
        $data = @file_get_contents('php://input');
        $param = json_decode($data, true);
        Log::info('ping log first!', $param);
        try {
            $validate = new DeviceapiValidate();
            if (!$validate->scene('ping')->check($param)) {
                return json(msg(-1, '', $validate->getError()));
            }
            if (isset($param['type'])) {
                unset($param['type']);
            }
            $deviceModel = new DeviceModel();
            $updateParam['account'] = $param['account'];
            $updateParam['heart_time'] = time();
            $updateParam['device_status'] = 1;
//            $updateParam['studio'] = $param['studio'];
            $where['account'] = $param['account'];
            $where['studio'] = $param['studio'];

            $res = $deviceModel->devicePing($where, $updateParam);

            if ($res['code'] != 0) {
                return json(msg('-2', '', $res['msg']));
            }
            return json(msg('1', '', "ping success"));

        } catch (\Exception $e) {
            Log::error('ping error!', $param);
            return json(msg('-11', '', 'saveBase64toImg error!' . $e->getMessage()));
        }
    }

    /**
     * 图片上传
     * @param Request $request
     * @return void
     */
    public function uploadQrCode(Request $request)
    {
        $data = @file_get_contents('php://input');
        $param = json_decode($data, true);
        Log::info('uploadQrCode log!', $param);
        $updateParam = [];
        try {

            $validate = new DeviceapiValidate();
            if (!$validate->scene('upload')->check($param)) {
                return json(msg(-1, '', $validate->getError()));
            }
            $saveImg = $this->saveBase64toImg($param['qr_img']);
            if ($saveImg['code'] != 0) {
                return json(msg(-1, '', $saveImg['img']));
            }
            if (isset($param['type'])) {
                unset($param['type']);
            }
            $param['qr_url'] = $saveImg['data'];
            $imgUrl = $request->root(true) . "upload" . $saveImg['data'];
            $qrcode = new QrReader($imgUrl);
            $imgUrlText = $qrcode->text(); //return decoded text from QR Code
            $param['qr_url'] = $imgUrlText;
            $deviceModel = new DeviceModel();
            $updateParam['account'] = $param['account'];
            $updateParam['qr_url'] = $param['qr_url'];
            $updateParam['studio'] = $param['studio'];
            $updateParam['qr_update_time'] = time();
            $res = $deviceModel->updateDeviceQrUrl($updateParam);
            if ($res['code'] != 0) {
                return json(msg('-2', '', $res['msg']));
            }
            return json(msg('1', '', $res['msg']));

        } catch (\Exception $e) {
            Log::error('uploadQrCode error!', $param);
            return json(msg('-11', '', 'saveBase64toImg error!' . $e->getMessage()));
        }

    }

    /**
     * 保存base54 数据流 转图片保存
     * @param $base64_image_content
     * @return array|void
     */
    protected function saveBase64toImg($base64_image_content)
    {
        try {
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
                $type = $result[2];  //图片后缀

                $dateFile = date('Y-m-d', time()) . "/";  //创建目录
                $new_file = ROOT_PATH . 'public' . DS . 'upload' . $dateFile;
                if (!file_exists($new_file)) {
                    //检查是否有该文件夹，如果没有就创建，并给予最高权限
                    mkdir($new_file, 0700);
                }

                $filename = time() . '_' . uniqid() . ".{$type}"; //文件名
                $new_file = $new_file . $filename;

                //写入操作
                if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                    return modelReMsg(0, $dateFile . $filename, '保存成功！');  //返回文件名及路径
//                    return $dateFile . $filename;  //返回文件名及路径
                } else {
                    return modelReMsg('-1', '', '保存失败！');  //返回文件名及路径
//                    return false;
                }
            } else {
                return modelReMsg('-2', '', '数据类型有误！');  //返回文件名及路径
            }
        } catch (\Exception $e) {
            Log::error('saveBase64toImg error!', $base64_image_content);
            return modelReMsg('-11', '', 'saveBase64toImg error!' . $e->getMessage());
        }

    }

    public function index(Request $request)
    {
        try {
//            $param = $request->param();
////            $deviceapiValidate = new DeviceapiValidate();
//            $validate = new DeviceapiValidate();
//            if (!$validate->scene('upload')->check($param)) {
//                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
//            }
//            $qr_img = $param['qr_img'];
            $img = "http://dvvv.com/upload/test.jpg";
            $imageInfo = getimagesize($img);
//            $base64 = "" . chunk_split(base64_encode(file_get_contents($img)));
            $base64_image_content = 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($img)));
//            $base64 =  'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($img)));
            //64 =>存储文件-》提取文件二维码链接-》链接设备链接
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
                $type = $result[2];  //图片后缀

                $dateFile = date('Y-m-d', time()) . "/";  //创建目录

//              $rootPath = $request->root(true);

//                var_dump($rootPath);exit;
                $new_file = ROOT_PATH . 'public' . DS . 'upload' . $dateFile;
                if (!file_exists($new_file)) {
                    //检查是否有该文件夹，如果没有就创建，并给予最高权限
                    mkdir($new_file, 0700);
                }

                $filename = time() . '_' . uniqid() . ".{$type}"; //文件名
                $new_file = $new_file . $filename;

                //写入操作
                if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                    $qrUrl = $dateFile . $filename;  //返回文件名及路径
                } else {
                    return false;
                }
            }
            var_dump($qrUrl);
            $img = $request->root() . "upload" . $qrUrl;
//            var_dump($img);exit;
            $qrcode = new QrReader($img);
            $text = $qrcode->text(); //return decoded text from QR Code
//            var_dump($text);exit;
//        $this ->assign("img",);
//
//            return $this->fetch();
        } catch (\Exception $e) {
            $returnMsg['code'] = 1009;
            $returnMsg['msg'] = "系统错误错误!" . $e->getMessage();
            return json_encode($returnMsg);
        }
    }

    /**
     * 设备通知  账号，设备id，时间，金额
     * @param Request $request
     * @return \think\response\Json|void
     */
    public function notify(Request $request)
    {
        $param = $request->param();
        Log::info('order notify log!', $param);
        try {
            $validate = new NotifylogValidate();
            if (!$validate->check($param)) {
                return json(msg(-1, '', $validate->getError()));
            }
            $notifyParam = $param;

            $notifyParam['status'] = 2;
            $notifyParam['add_time'] = time();
            if (isset($param['pay_name'])) {
                $notifyParam['notify_pay_name'] = mb_substr($param['pay_name'], -1, 1, 'utf-8');;
            }
            $notifyParam['payment'] = "alipay(aa)";
            $notifyParam['notify_log_desc'] = "alipay(aa) notify";
            $notifyLogModel = new NotifylogModel();
            //匹配订单
            $orderModel = new OrderModel();
            $matchRes = $orderModel->orderMatch($notifyParam);
            //匹配订单a:未匹配到订单 start
            if ($matchRes['code'] != 0) {
                $notifyParam['status'] = 4; //未匹配到订单
                $notifyLogModel->addNotifyLog($notifyParam);
                return json(msg('1', '', $matchRes['msg']));
            }
            //匹配订单a:未匹配到订单 end

            //匹配订单b:匹配到订单=>回调 start
            $orderModel->orderNotify($matchRes['data']);
            //匹配订单b:匹配到订单 end
            $notifyParam['order_no'] = $matchRes['data']['order_no'];
            $notifyParam['status'] = 1;
            $notifyParam['notify_log_desc'] = "alipay(aa) notify match success";

            $res = $notifyLogModel->addNotifyLog($notifyParam);
            if ($res['code'] != 0) {
                Log::error('notify addNotifyLog error!' . $res['msg'], $param);
                return json(msg('1', '', $res['msg']));
            }
        } catch (\Exception $e) {
            Log::error('notify error!', $param);
            return json(msg('1', '', ' notify error!' . $e->getMessage()));
//            return json(msg('-11', '', ' notify error!' . $e->getMessage()));
        }
    }

}