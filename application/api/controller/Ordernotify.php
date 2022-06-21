<?php

namespace app\api\controller;


use think\Controller;
use think\Db;
use app\common\model\OrderhexiaoModel;
use app\common\model\OrderModel;
use app\common\model\CammyModel;
use app\api\validate\OrderinfoValidate;
use app\api\validate\NotifyOrderStatusValidate;
use think\Request;
use app\common\model\SystemConfigModel;
use think\Validate;
use app\common\Redis;

header('Access-Control-Allow-Origin:*');
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept,Authorization");
header('Access-Control-Allow-Methods:GET,POST,PUT,DELETE,OPTIONS,PATCH');

class Ordernotify extends Controller
{

    /**
     * @param Request $request
     * @return void
     */
    public function notifyOrderStatus0069(Request $request)
    {
        session_write_close();
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);
        try {
            logs(json_encode([
                'param' => $message,
                'ip' => $request->ip(),
                'startTime' => date("Y-m-d H:i:s", time())
            ]), 'notifyOrderStatus0069');
            $validate = new NotifyOrderStatusValidate();
            if (!$validate->scene('notify')->check($message)) {
                return apiJsonReturn(-1, '', $validate->getError());
            }
//        'pay_status' => 'require',        //支付状态  1 之外按支付失败处理
//        'time' => 'require',              //时间|支付时间
//        'order_me' => 'require',          //平台单号
//        'order_pay' => 'require',         //京东单号
//        'amount' => 'require',            //卡密金额
//        'card_name' => "require",         //卡密账号
//        'card_password' => "require",     //卡密密码
            $orderModel = new OrderModel();
            $orderWhere['order_me'] = $message['order_me'];  //平台单号
            $orderWhere['order_pay'] = $message['order_pay'];   //订单匹配手机号
            $orderInfo = $orderModel->where($orderWhere)->find();
            //支付成功  把卡蜜存起来

            if ($message['pay_status'] == 1) {
                if (!$validate->scene('payNotify')->check($message)) {
                    return apiJsonReturn(-5, '', $validate->getError());
                }
                $cammyData['card_name'] = $message['card_name'];
                $cammyData['order_me'] = $message['order_me'];
                $cammyData['card_password'] = $message['card_password'];
                $cammyData['amount'] = $message['amount'];
                $cammyData['add_time'] = time();
                $cammyData['update_time'] = time();

                $cammyModel = new CammyModel();

                $insertCammyRes = $cammyModel->addCammy($cammyData);
                logs(json_encode([
                    'cammyData' => $cammyData,
                    'insertCammyRes' => $insertCammyRes
                ]), 'AAAAAAAAAAAACAMMY');
                if (!isset($insertCammyRes['code']) || $insertCammyRes['code'] != 0) {
                    logs(json_encode([
                        'cammyData' => $cammyData,
                        'insertCammyRes' => $insertCammyRes
                    ]), 'AAAAAAAAAAAACAMMYfail');
                    if($insertCammyRes['code'] == -1){
                        return json(msg(-1, '', '卡密重复！'));
                    }
                } else {
                    $orderUpdate['cammy_status'] = 1;   //有卡蜜
                }
            }

            if (empty($orderInfo)) {
                logs(json_encode([
                    "time" => date("Y-m-d H:i:s", time()),
                    'param' => $message
                ]), 'notifyOrderStatusMatchOrderFail');
                return json(msg(-2, '', '无此订单！'));
            }
            if ($orderInfo['pay_status'] == 1) {
                return json(msg(-3, '', '订单已支付！'));
            }
            $checkOrderStatus = "查询失败！";

            $orderUpdate['check_status'] = 3;   //可在查询状态
            if ($message['check_status'] == 1) {
                $orderUpdate['check_status'] = 3;   //可在查询状态
                $checkOrderStatus = "查询成功！";
            }

            $payStatus = "支付失败！";
            if ($message['pay_status'] == 1) {
                $orderUpdate['check_status'] = 2;                     //不可查询状态
                $payStatus = "支付成功！";
                $orderUpdate['pay_status'] = 1;                       //支付成功！
                $orderUpdate['order_status'] = 1;                     //支付成功！
                $orderUpdate['actual_amount'] = $message['amount'];   //支付金额
                $orderUpdate['pay_time'] = $message['time'];          //支付 时间
            }
            $orderInfo['pay_status'] = 1;
            $checkResult = "第" . ($orderInfo['check_times'] + 1) . "次查询" . $checkOrderStatus . "|" . $payStatus . "(" . date("Y-m-d H:i:s") . ")";

            $nextCheckTime = time() + 30;  //设置第三次往后的查询时间
            $autoCheckOrderTime = SystemConfigModel::getAutoCheckOrderTime();
            if (is_int($autoCheckOrderTime)) {
                $nextCheckTime = time() + $autoCheckOrderTime;
            }
            //查询成功  更新订单表、预拉单表
            $orderWhere['order_no'] = $orderInfo['order_no'];
            $orderUpdate['check_times'] = $orderInfo['check_times'] + 1;
            $orderUpdate['next_check_time'] = $nextCheckTime;
            $orderUpdate['check_result'] = $checkResult;
            $updateCheck = Db::table("bsa_order")->where($orderWhere)
                ->update($orderUpdate);
            if (!$updateCheck) {
                logs(json_encode(["time" => date("Y-m-d H:i:s", time()),
                    'action' => "checkNotifySuccess",
                    'payStatus' => $payStatus,
                    'message' => json_encode($message),
                    "updateCheck" => $updateCheck
                ]), 'notifyOrderStatus0069Fail');
                return json(msg(-9, '', '接收成功,更新失败！'));
            }
            //1、支付到账
            if ($message['pay_status'] == 1) {
                //更新预拉单表
                $updatePrepareOrderWhere['order_me'] = $orderInfo['order_me'];
                $updatePrepareOrder['pay_time'] = time();
                $updatePrepareOrder['order_desc'] = "支付成功！";
                $updatePrepareOrder['pay_status'] = 1;
                $updatePrepareOrder['pay_amount'] = $message['amount'];
                $updatePrepareOrderRes = Db::table("bsa_order_prepare")
                    ->where($updatePrepareOrderWhere)->update($updatePrepareOrder);

                logs(json_encode([
                    "time" => date("Y-m-d H:i:s", time()),
                    'PrepareOrderWhere' => $updatePrepareOrderWhere,
                    'updatePrepare' => $updatePrepareOrder,
                    'updatePrepareOrderRes' => $updatePrepareOrderRes
                ]), 'notifyUpdatePrepareFail');
            }

            return json(msg(0, '', '接收成功,更新成功！'));
        } catch (\Exception $exception) {
            logs(json_encode(['param' => $message,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()]), 'notifyOrderStatusException');
            return json(msg(-11, '', 'Exception！'));
        } catch (\Error $error) {
            logs(json_encode(['param' => $message,
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()]), 'notifyOrderStatusError');
            return json(msg(-22, '', "Error！"));
        }
    }
}