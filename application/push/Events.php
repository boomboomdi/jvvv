<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */

//declare(ticks=1);
use \GatewayWorker\Lib\Gateway;
use think\Config;
use \think\Db;
use think\log;
use think\Request;

use app\api\model\SmsModel;

use app\api\model\OrderModel;   //支付宝即时码  order
use app\api\model\DeviceModel;   //支付宝即时码  device

use app\common\model\OrdercybModel;   //采源宝  order
use app\api\model\DevicecybModel;    //采源宝  device

use app\common\model\OrderzklModel;   //吱口令 order
use app\common\model\DevicezklModel;  //吱口令 device

use app\common\model\OrdertbhbModel;   //淘宝红包  order
use app\common\model\DevicetbhbModel;  //淘宝红包  device

use app\common\model\yunshanfu\OrderysfModel;   //云闪付  order
use app\common\model\yunshanfu\DeviceysfModel;  //云闪付  device

use app\common\model\jiaoyimao\OrderjymModel;   //交易猫  order
use app\common\model\jiaoyimao\DevicejymModel;  //交易猫  device

use app\common\model\huabeidaihuan\OrderhbdhModel;   //花呗代还  order
use app\common\model\huabeidaihuan\DevicehbdhModel;  //花呗代还  device

use app\common\model\paijitang\OrderpjtModel;   //拍机堂 order
use app\common\model\paijitang\DevicepjtModel;  //拍机堂  device

use app\common\model\devicenb\DevicenbModel;  //内部  device


/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $clientId 连接id
     */
    public static function onConnect($clientId)
    {
        // 向当前client_id发送数据
        Gateway::sendToClient($clientId, json_encode(['action' => 'ping', 'clientId' => $clientId]));
    }

    /**
     * 当客户端发来消息时触发
     * @param int $clientId 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($clientId, $message)
    {
        $messageData = json_decode($message, true);

        if (!$messageData) {
            return;
        }

        try {
            //支付宝固额  / 采源宝
            if (isset($messageData['action'])) {
                switch ($messageData['action']) {
                    case "ping":
                        if (isset($messageData['device']) && $messageData['device'] == "cyb") {
                            $devicecybModel = new DevicecybModel();
                            $returnData = $devicecybModel->cybping($clientId, $messageData);
                        } else if (isset($messageData['device']) && $messageData['device'] == "pay_url") {   //内部
                            $deviceModel = new DevicenbModel();
                            $returnData = $deviceModel->ping($clientId, $messageData);
                        } else if (isset($messageData['device']) && $messageData['device'] == "zzkl") {
                            $devicezklModel = new DevicezklModel();
                            $returnData = $devicezklModel->ping($clientId, $messageData);
                        } else if (isset($messageData['device']) && $messageData['device'] == "ssm") {
                            $deviceModel = new DeviceModel();
                            $returnData = $deviceModel->zfbping($clientId, $messageData);
                        } else if (isset($messageData['device']) && $messageData['device'] == "tbhb") {
                            $deviceModel = new DevicetbhbModel();
                            $returnData = $deviceModel->ping($clientId, $messageData);
                        }  else if (isset($messageData['device']) && $messageData['device'] == "ysf_bank") {
                            $deviceModel = new DeviceysfModel();
                            $returnData = $deviceModel->ping($clientId, $messageData);
                        } else if (isset($messageData['device']) && $messageData['device'] == "jym") {
                            $deviceModel = new DevicejymModel();
                            $returnData = $deviceModel->ping($clientId, $messageData);
                        } else if (isset($messageData['device']) && $messageData['device'] == "hbdh") {
                            $deviceModel = new DevicehbdhModel();
                            $returnData = $deviceModel->ping($clientId, $messageData);
                        } else {
                            $deviceModel = new DeviceModel();
                            $returnData = $deviceModel->zfbping($clientId, $messageData);
                        }
                        //{
                        //  "action":"ping",
                        //  "account":"",  //支付宝号
                        //  "device":"",  //回调类型
                        //  "deviceId":deviceId,   //设备id
                        //  "version":version,   //插件版本号
                        //  "project":"设备名称"  //支付宝固额
                        //}
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    case "getQrCodeCallback":   //支付宝即时码/云闪付  回调订单收款码
                        //{
                        //  "action"："getQrCodeCallback",
                        //  "account": "",  //支付宝账号
                        //  "orderNo":"orderNo",//平台订单号
                        //  "zfbOrderNo":"orderNo",//平台订单号
                        //  "url":"收款链接",//收款链接
                        //}
                        if (isset($messageData['device']) && $messageData['device'] == "ysf_bank") {
                            $orderModel = new OrderysfModel();
                            $returnData = $orderModel->getQrCodeCallback($clientId, $messageData);
                        }else{
                            $orderModel = new OrderModel();
                            $returnData = $orderModel->zfbGetQrCodeCallback($clientId, $messageData);
                        }

                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    case "getNewPayUrlCallback":   //监控获取短链接
                        //{
                        //  "action"："getQrCodeCallback",
                        //  "account": "",  //支付宝账号
                        //  "orderNo":"orderNo",//平台订单号
                        //  "zfbOrderNo":"orderNo",//平台订单号
                        //  "url":"收款链接",//收款链接
                        //}
                        if (isset($messageData['device']) && $messageData['device'] == "paijitang") {
                            $orderModel = new OrderpjtModel();
                            $returnData = $orderModel->getQrCodeCallback($clientId, $messageData);
                        }else{
                            $orderModel = new OrderModel();
                            $returnData = $orderModel->zfbGetQrCodeCallback($clientId, $messageData);
                        }
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    case "getTransQrCodeCallback":   //支付宝zkl 回调订单收款码
                        //{
                        //  "action"："getQrCodeCallback",
                        //  "account": "",  //支付宝账号
                        //  "orderNo":"orderNo",//平台订单号
                        //  "zfbOrderNo":"orderNo",//平台订单号
                        //  "url":"收款链接",//收款链接
                        //}
                        $orderModel = new OrderzklModel();    //app\common\model
                        $returnData = $orderModel->getQrCodeCallback($clientId, $messageData);
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    case "upload":   //淘宝红包zkl 回调订单收款码  OrdertbhbModel
                        //{
                        //"action": "upload",
                        //"hongbao_id": "152381583682524597",
                        //"note": "恭喜发财，大吉大利！",
                        //"shortUrl": "alipays:\/\/platformapi\/startApp?appId=20000125&orderSuffix=h5_route_token%3D%22RZ41L6cHLfX97llMVVb64AAw5LqvaXmobilecashierRZ41%22%26is_h5_route%3D%22true%22%23Intent%3Bscheme%3Dalipays%3Bpackage%3Dcom.eg.android.AlipayGphone%3Bend"
                        //}
                        $orderModel = new OrdertbhbModel();    //app\common\model
                        $returnData = $orderModel->getQrCodeCallback($clientId, $messageData);
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    case "orderCallback":
                        //{
                        //  "action":"orderCallback",
                        //  "orderNo":orderNo,  //订单号
                        //  "money":money,
                        //  "account":account
                        //}
                        //详细待确认
                        //新回调
                        if (isset($messageData['device'])) {
                            logs(json_encode(['client' => $clientId, '$data' => $messageData]), 'orderCallback_log');

                            switch ($messageData['device']) {
                                case "zzkl":  //zzkl 回调  app\common\model
                                    $orderModel = new OrderzklModel();
                                    $returnData = $orderModel->orderCallback($clientId, $messageData);
                                    Gateway::sendToClient($clientId, json_encode($returnData));
                                    break;
                                case "cyb":  //cyb 回调
                                    $ordercybModel = new OrdercybModel();  //app\common\model
                                    $returnData = $ordercybModel->cybOrderCallbackByUid($clientId, $messageData);
                                    Gateway::sendToClient($clientId, json_encode($returnData));
                                    break;
                                case "ssm":  //ssm 回调
                                    $orderModel = new OrderModel();  //app\api\model
                                    $returnData = $orderModel->zFbOrderCallback($clientId, $messageData);
                                    break;
                                case "tbhb":  //ssm 回调
                                    $orderModel = new OrdertbhbModel();  //app\api\model
                                    $returnData = $orderModel->orderCallback($clientId, $messageData);
                                    break;
                                case "ysf_bank":  //ssm 回调
                                    $orderModel = new OrderysfModel();  //app\api\model
                                    $returnData = $orderModel->orderCallback($clientId, $messageData);
                                    break;
                                case "jym":  //ssm 回调
                                    $orderModel = new OrderjymModel();  //app\api\model
                                    $returnData = $orderModel->orderCallback($clientId, $messageData);
                                    break;
                                case "hbdh":  //ssm 回调
                                    $orderModel = new OrderhbdhModel();  //app\api\model
                                    $returnData = $orderModel->orderCallback($clientId, $messageData);
                                    break;
                            }
                        }
                        else {
                            //老回调
                            $ordercybModel = new OrdercybModel();
                            $orderModel = new OrderModel();
                            //OLD回调
                            if (isset($messageData['uid'])) {
                                //即时码  where  orderNo_me
                                $orderData = $orderModel->where('order_me', '=', $messageData['orderNo'])->find();
                                if (!empty($orderData)) {
                                    $returnData = $orderModel->zFbOrderCallback($clientId, $messageData);
                                } else {
                                    //采源宝
                                    $ordercybData = $ordercybModel->where('order_me', '=', $messageData['orderNo'])->find();
                                    if (!empty($ordercybData)) {
                                        $returnData = $ordercybModel->cybOrderCallback($clientId, $messageData);
                                    } else {
                                        $returnData = $ordercybModel->cybOrderCallbackByUid($clientId, $messageData);
                                    }
                                }
                            } else {
                                $orderData = $ordercybModel->where('order_me', '=', $messageData['orderNo'])->find();
                                if (!empty($orderData)) {
                                    $returnData = $ordercybModel->cybOrderCallback($clientId, $messageData);
                                } else {
                                    $returnData = $orderModel->zFbOrderCallback($clientId, $messageData);
                                }
                            }
                        }
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    //采源宝 获取吱口令
                    case "ret_zkl":
                        //采源宝生成吱口令回调
                        //{
                        //"ali_uid": "2088632607110015",
                        //"code": true,
                        //"msg_id": "123123123",
                        //"shop_id": "607984509688",
                        //"sid": "1913230d21bcffef55ddd6b1d3708f46",
                        //"uid": "2206725967945",
                        //"z_jine": "100",
                        //"address": "束言才，17634621670，鸡西市莱阳街12号-6-5",
                        //"orderId": "894215584745964579",
                        //"zkl_data": "3dZIm4z01QY",
                        //"action": "ret_zkl"
                        //}
                        $devicecybModel = new DevicecybModel();
                        $returnData = $devicecybModel->getZklCallback($clientId, $messageData);
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    //采源宝  吱口令回调收款码
                    case "getZhiQrCodeCallback":
                        //{
                        //"account": "18428382473",
                        //"url": "https://qr.alipay.com/s7x108544oabfkkftatrp97",
                        //"orderNo": "订单号",
                        //"zhiCode": "吱口令",
                        //"action": "getZhiQrCodeCallback"
                        //}

                        $ordercybModel = new OrdercybModel();
                        $returnData = $ordercybModel->getQrCodeCallback($clientId, $messageData);
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                        break;
                }
            }
            //支付宝固额
            //旺信红包  start   client -> service
            if (isset($messageData['action1'])) {
                switch ($messageData['action']) {
                    case "ping":
                        //心跳  {"action":"ping","account":account} //
                        //  account为账号的id，用于区分设备
                        //  服务器如果15秒没有收到ping，即可认为设备已关闭
                        $deviceModel = new DeviceModel();
                        $returnData = $deviceModel->ping($clientId, $messageData);
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    case "connect":
                        //上线    {"action":"connect","account":account}
                        //后台录入->设备上线->设备开启->后台才能开启收款。
                        $deviceModel = new DeviceModel();
                        $returnData = $deviceModel->websocketConnect($clientId, $messageData);
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    case "getQrCodeCallback":
                        //  获取收款链接   client ->service   {"action":"getQrCodeCallback","orderId":orderId,"orderNo":"orderNo","url":url}
                        //  orderId 旺信订单号；orderNo 通道订单号； url 订单链接；
                        //  详细待确认
                        $orderModel = new OrderModel();
                        $returnData = $orderModel->getQrCodeCallback($clientId, $messageData);
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    case "proGetQrCodeCallback":
                        //  预生成收款链接返回   client ->service   {"action":"getQrCodeCallback","orderId":orderId,"orderNo":"orderNo","url":url}
                        //  orderId 旺信订单号；orderNo 通道订单号； url 订单链接；
                        //  详细待确认
                        $orderModel = new OrderModel();
                        $returnData = $orderModel->proGetQrCodeCallback($clientId, $messageData);
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    case "orderCallback":
                        //接收收款消息（匹配订单并回调）  {"action":"orderCallback","orderId":orderId,"money":money,"account":account}
                        //  orderId 旺信订单号；money 付款金额； account 收款账户；
                        //详细待确认
                        $orderModel = new OrderModel();
                        $returnData = $orderModel->orderCallback($clientId, $messageData);
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                        break;
                }
            }
            //旺信红包  end

            //attach
            if (isset($messageData['attach'])) {
                $receiveData = $messageData['attach'];
                switch ($receiveData['api']) {
                    //获取收款码 start
                    case 'getQrCode':
                        //logs(json_encode(['message'=>$messageData,'api'=>"getQrCode"]),'getQrCode');

                        if (!isset($receiveData['orderMe'])) {
                            logs(json_encode(['message' => $messageData, 'errorMsg' => 'no orderMe']), 'getQrCode_fail_one');
                        }
                        //收款连接获取失败！
                        $code = "10000";
                        $msg = "getQrCode_do_success!";
                        if ($messageData['code'] == 10000) {
                            //qrCodeId
                            if (!isset($messageData['qrCodeId'])) {
                                $code = "10001";
                                $msg = "getQrCode_qrCodeId!";
                                logs(json_encode(['message' => $messageData, 'errorMsg' => 'no qrCodeId']), 'getQrCode_fail_two');
                            }
                            if (!isset($messageData['qrCode'])) {
                                $code = "10002";
                                $msg = "getQrCode_qrUrl!";
                                logs(json_encode(['message' => $messageData, 'errorMsg' => 'no qrUrl']), 'getQrCode_fail_three');
                            }
                            $updateWhere['order_me'] = $receiveData['orderMe'];  //订单号为条件
                            $updateData = ['qr_url' => $messageData['qrCode'], 'url_update_time' => time(), 'order_status' => 3, 'order_ysf' => $messageData['qrCodeId']];
                            if ($code == 10000) {
                                $orderModel = new OrderModel();
                                $changOrderQrUrl = $orderModel->updateOrderQrCode($updateWhere, $updateData);
                                if (!$changOrderQrUrl) {
                                    $code = "17777";
                                    $msg = "changOrderQrUrl_fail!";
                                    logs(json_encode(['message' => $messageData, 'changOrderQrUrl' => $changOrderQrUrl]), 'getQrCode_changOrderQrUrl_fail');
                                }
//                                else {
//                                    logs(json_encode(['message'=>$messageData,'changOrderQrUrl'=>$changOrderQrUrl]),'getQrCode_success');
//                                }
                            }

                        } else {
                            $updateWhere['order_me'] = $receiveData['orderMe'];  //订单号为条件
                            $updateData = ['url_update_time' => time(), 'order_status' => 2];  //6 获取收款码失败
                            $orderModel = new OrderModel();
                            $changOrderQrUrl = $orderModel->updateOrderQrCode($updateWhere, $updateData);
                            if (!$changOrderQrUrl) {
                                $code = "18888";
                                $msg = "changOrderQrUrl_fail!";
                                logs(json_encode(['message' => $messageData, 'changOrderQrUrl' => $changOrderQrUrl]), 'getQrCode_fail_error');
                            } else {
                                $code = "10000";
                                $msg = "changOrderQrUrlFail_success!";
                                logs(json_encode(['message' => $messageData, 'changOrderQrUrl' => $changOrderQrUrl]), 'getQrCode_fail_success');
                            }
                        }
                        $returnData = array("code" => $code, 'msg' => $msg, "time" => time());
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    //获取收款码 end
                    case "getQrCodePayer":
                        //logs(json_encode(['message'=>$messageData,'api'=>"getQrCodePayer"]),'getQrCodePayer');
                        $code = "10000";
                        $msg = 'getQrCodePayer_success';
                        if (isset($messageData['payerList']) && !empty($messageData['payerList'])) {
                            if (!is_array($messageData['payerList'])) {
                                $code = "10003";
                                $msg = 'payerList_error';
                            }
//                            //循环入库 start
//                            foreach ($messageData['payerList'] as $k =>$v){
//
//                            }
//                            //循环入库  end
                            $payTimes = count($messageData['payerList']);
//                            $playOrderData = $messageData['payerList'][0];
                            //查询订单
                            $orderModel = new OrderModel ();
                            $orderData = $orderModel->where('order_ysf', '=', $receiveData['qrCodeId'])->find();
                            $lastSql = $orderModel->getLastSql();

                            if (empty($orderData)) {
                                $code = "10004";
                                $msg = $receiveData['qrCodeId'] . "no find order";
                                logs(json_encode(['message' => $messageData, 'errorMessage' => 'no orderData', 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback_no_order');
                            }
                            if ($orderData['order_status'] == 1) {
                                $code = "10015";
                                $msg = $receiveData['qrCodeId'] . "_orderCallback_repeat_order";
                                logs(json_encode(['message' => $messageData, 'errorMessage' => 'no orderData', 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback_repeat_order');
                            }
                            //回调总后台  start
                            $notifyCallbackResult = "测试成功！";
                            if ($orderData['notify_url'] != "test.com") {

                                //封装数据
                                $notifyToMainStationData = $orderModel::doNotifyToMainStationData($orderData, $orderData['payable_amount']);
                                for ($x = 0; $x < 5; $x++) {
                                    //回调总后台
                                    $notifyCallbackResult = cUrlGetData($orderData['notify_url'], $notifyToMainStationData, ['Content-Type:application/json']);//请求回调
                                    if ($notifyCallbackResult == "success") {
                                        $notifyCallbackResult = "完成且回调!";
                                        break;
                                    }
                                }
                            }
                            //本地更新  start
                            $updateOrderResult = $orderModel->doNotifySuccess($orderData, $orderData['payable_amount'], $payTimes);
                            if ($updateOrderResult['code'] != 10000) {
                                $lastSql = $orderModel->getLastSql();
                                $code = "11111";
                                $msg = "本地更新失败!";
                                $notifyCallbackResult .= "本地更新失败！";
                                logs(json_encode(['message' => $orderData, 'errorMessage' => $updateOrderResult['msg'], 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback_local_change_fail');
                            }
                            $level = 1;
                            if (!isset($updateOrderResult['code']) || $updateOrderResult['code'] != 10000) {
                                $level = 2;
                                $lastSql = $orderModel->getLastSql();
                                $code = "12222";
                                $msg = "本地更新失败!:200";
                                logs(json_encode(['message' => $messageData, 'errorMessage' => 'updateOrderResult fail', 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback_fail');
                            }
                            //本地更新  end
                            $smsModel = new SmsModel();
                            $sms = trim($receiveData['qrCodeId']);
                            $insertData['sms'] = $sms;
                            $insertData['phone'] = $orderData['account'];
                            $insertData['card'] = $orderData['card'];
                            $insertData['channel'] = $orderData['channel'];
                            $insertData['order_no'] = $orderData['order_no'];
                            $insertData['return_msg'] = $notifyCallbackResult;
                            $insertData['level'] = $level;
                            $insertSms = $smsModel->addSmsForYsf($insertData);
                            $lastSql = $smsModel->getLastSql();
                            if (!$insertSms) {
                                $code = "13333";
                                $msg = "本地更新失败!:300";
                                logs(json_encode(['message' => $insertData, 'errorMessage' => 'insert sms fail', 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback_insert_sms_fail');
                            }

                        } else {
                            $code = "10002";
                            $msg = 'require_payerList';
                        }
                        if ($code > 10002) {
                            $ping = array("code" => $code, "msg" => $msg, 'attach' => $messageData);
                            Gateway::sendToClient($clientId, json_encode($ping));
                        }

                        break;
                        break;
                }

            }
            //attach end
            //api start
//            logs(json_encode(['message'=>Config::get('database')]),'message_test');
            if (isset($messageData['api'])) {
                $lastSql = "";
                switch ($messageData['api']) {
                    case "connect":   //上线包
                        //api(connect)、time、deviceId、deviceType、project（云闪付）、version（版本号）、phone

                        //                    if (!isset($messageData['time'])||empty($messageData['time'])) {
                        ////                        return;
                        ////                        $updateData['time'] = $messageData['time'];
                        //                    }
                        $code = "10000";
                        $msg = "上线成功!";
                        //版本号
                        if (isset($messageData['version'])) {
                            $updateData['version'] = $messageData['version'];
                        }
                        //程序名称（云闪付）
                        if (isset($messageData['project'])) {
                            $updateData['project'] = $messageData['project'];
                        }
                        //设备 phone 非空
                        if (!isset($messageData['phone']) || empty($messageData['phone'])) {
                            $code = "10001";
                            $msg = "上线失败!：require_phone";
                            $updateData['phone'] = $messageData['phone'];
                        }
                        //设备标识deviceId  非空
                        if (!isset($messageData['deviceId']) || empty($messageData['deviceId'])) {
                            $code = "10002";
                            $msg = "上线失败!：require_deviceId";
                        } else {
                            $updateData['device_id'] = $messageData['deviceId'];
                        }
                        //工作室标识
                        if (isset($messageData['channel'])) {
                            $updateData['channel'] = $messageData['channel'];
                        }
                        $updateData['client_id'] = $clientId;
                        $updateData['update_time'] = time();
                        if ($code == 10000) {
                            $deviceModel = new DeviceModel();
                            $where['phone'] = $messageData['phone'];
                            $res = $deviceModel->where($where)->find();  //s_device phone

                            if ($res) {
                                //如果存在改变状态
                                $updateRes = $deviceModel->where($where)->update($updateData);
                                if (!$updateRes) {
                                    $code = "17777";
                                    $msg = "上线失败!";
                                }
                            } else {
                                $code = "18888";
                                $msg = "上线失败，需要后台提前录入!";
//                                //如果不存在,新增记录
//                                $updateData['phone'] = $messageData['phone'];
//                                //$updateData['name'] = $messageData['name'];
//                                $updateData['create_time'] = time();
//                                $res1 = $deviceModel->insert($updateData);
//                                $lastSql = $deviceModel->getLastSql();
//                                if (!$res1) {
//                                    $code = "19999"; $msg = "上线失败!";
//                                    logs(json_encode(['message' => $messageData, 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'connect_fail');
//                                }
                            }
                            $lastSql = $deviceModel->getLastSql();
                            logs(json_encode(['message' => $messageData, 'lastSql' => $lastSql, 'res' => $res]), 'connect_log');

                        }

                        //心跳返回
                        $returnData = array("code" => $code, 'msg' => $msg, "time" => time());
                        Gateway::sendToClient($clientId, json_encode($returnData));
                        break;
                    case "ping":   //心跳包

                        //判断当前设备是否存在
                        $deviceModel = new DeviceModel();
                        $where['client_id'] = $clientId;
                        $res = $deviceModel->where($where)->find();
                        $updateData['client_id'] = $clientId;
                        $updateData['is_online'] = 1;
                        $updateData['update_time'] = time();

                        $code = "10000";
                        $msg = "心跳连接成功!";
                        if (!$res) {
                            $code = "10001";
                            $msg = "心跳失败连接失败，后台无此设备!";
                        } else {
                            //如果存在改变状态
                            $res = $deviceModel->where($where)->update($updateData);
                            if ($res == "") {
                                $code = "10001";
                                $msg = "心跳失败!";
                            }
                        }

                        logs(json_encode(['message' => $messageData, 'res' => $res]), 'ping_log');
                        //心跳返回
                        $ping = array("code" => $code, "msg" => $msg, 'attach' => $messageData);
                        Gateway::sendToClient($clientId, json_encode($ping));
                        break;
                    case "orderCallback":   //通知支付结果   废用中
                        $lastSql = "";
                        //云闪付账号
                        if (!isset($messageData['phone'])) {
                            logs(json_encode(['message' => $messageData, 'errorMessage' => 'no phone', 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback');
                            return;
                        }
                        //云闪付订单号
                        if (!isset($messageData['order_ysf'])) {
                            logs(json_encode(['message' => $messageData, 'errorMessage' => 'no order_ysf', 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback');
                            return;
                        }
                        //云闪付订单号  玩家付款人姓名
                        if (!isset($messageData['play_name'])) {
                            logs(json_encode(['message' => $messageData, 'errorMessage' => 'no play_name', 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback');
                            return;
                        }
                        $deviceModel = new DeviceModel();
                        $where['phone'] = $messageData['phone'];
                        $res = $deviceModel->field('id')->where($where)->find();
                        if (!$res) {
                            logs(json_encode(['message' => $messageData, 'errorMessage' => 'no device' . $messageData['phone'], 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback');
                            return;
                        }
                        //查询订单
                        $orderModel = new OrderModel ();
                        $orderData = $orderModel->where('order_ysf', '=', $messageData['order_ysf'])->find();
                        if (empty($orderData)) {
                            logs(json_encode(['message' => $messageData, 'errorMessage' => 'no orderData' . $messageData['order_ysf'], 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback');
                            return;
                        }
                        //回调总后台  start
                        $notifyCallbackResult = "测试成功！";
                        if ($orderData['notify_url'] != "test.com") {
                            $notifyToMainStationData = $orderModel::doNotifyToMainStationData($orderData, $orderData['payable_amount']);
                            for ($x = 0; $x < 5; $x++) {
                                $notifyCallbackResult = cUrlGetData($orderData['notify_url'], $notifyToMainStationData, ['Content-Type:application/json']);//请求回调
                                if ($notifyCallbackResult == "success") {
                                    break;
                                }
                            }
                        }
                        //回调总后台 end
                        //本地更新  start
                        $updateOrderResult = $orderModel->doNotifySuccess($orderData, $orderData['payable_amount']);
                        if ($updateOrderResult['code'] != 10000) {
                            $notifyCallbackResult .= "本地更新失败！";
                            logs(json_encode(['message' => $orderData, 'errorMessage' => $updateOrderResult['msg'], 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback_local_change_fail');
                            break;
                        }
                        $level = 1;
                        if (!isset($updateOrderResult['code']) || $updateOrderResult['code'] != 10000) {
                            $level = 2;
                            logs(json_encode(['message' => $messageData, 'errorMessage' => 'updateOrderResult fail' . $messageData['order_ysf'], 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback_fail');
                        }
                        //本地更新  end
                        $smsModel = new SmsModel();
                        $sms = trim($messageData['order_ysf']);
                        $insertData['sms'] = $sms;
                        $insertData['phone'] = $messageData['phone'];
                        $insertData['card'] = $orderData['card'];
                        $insertData['channel'] = $orderData['channel'];
                        $insertData['order_no'] = $orderData['channel'];
                        $insertData['return_msg'] = $notifyCallbackResult;
                        $insertData['level'] = $level;
                        $insertSms = $smsModel->addSmsForYsf($insertData);
                        $lastSql = $smsModel->getLastSql();
                        if (!$insertSms) {
                            logs(json_encode(['message' => $insertData, 'errorMessage' => 'insert sms fail', 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'orderCallback_insert_sms_fail');
                        }
                        break;

                }
            }
            //api end
        } catch (\Exception $exception) {
            logs(json_encode(['message' => $messageData, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'events_exception');
        } catch (\Error $error) {
            logs(json_encode(['message' => $messageData, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'events_error');
        }
    }

    /**
     * 当用户断开连接时触发
     * @param $clientId
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function onClose($clientId)
    {
        $db = new Db();
        $deviceData = $db::table('s_device_cyb')->where('client_id', '=', $clientId)->find();
        if ($deviceData) {
            $db::table('s_device_cyb')->where('client_id', '=', $clientId)->update(['is_online' => 2]);
        } else {
            $db::table('s_device')->where('client_id', '=', $clientId)->update(['is_online' => 2]);
        }
    }
}
