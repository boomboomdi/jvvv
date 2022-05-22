<?php

namespace app\common\model;

use app\api\validate\OrderinfoValidate;
use think\Db;
use think\facade\Log;
use think\Model;

class NotifylogModel extends Model
{
    protected $table = 'bsa_notify_log';

    /**
     * 获取订单
     * @param $limit
     * @param $where
     * @return array
     */
    public function getNotifyLogs($limit, $where)
    {
        $prefix = config('database.prefix');
        try {
            $res = $this->field($prefix . 'order.*')->where($where)
                ->order('notify_id', 'desc')->paginate($limit);

        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $res, 'ok');
    }

    /**
     * 增加回调日志
     * @param $notifyOrderLog
     * @return array
     */
    public function addNotifyLog($notifyOrderLog)
    {
        try {
            $has = $this->where('order_pay', $notifyOrderLog['order_pay'])->findOrEmpty()->toArray();
            if (!empty($has)) {
                $notifyOrderLog['notify_log_desc'] = "alipay(aa) notify match fail repeat";  //重复
                $notifyOrderLog['status'] = 3;
            }
            $this->insert($notifyOrderLog);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '添加回调日志成功');
    }

    /**
     * 获取订单信息
     * @param $OrderId
     * @return array
     */
    public function getOrderById($OrderId)
    {
        try {

            $info = $this->where('order_no', $OrderId)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    //通道下单
    public function createOrder($payment, $apiSign = "")
    {
        //如果指定通道
        if (!empty($apiSign)) {

        }
        //支付方式

        //通道标识


    }

    /**
     * 订单回调 通道/手动回调 总入口
     * @param $orderData
     * @param $status 1 丢入通知队列  2、手动回调()
     * @return array|void
     */
    public function orderNotify($orderData, $status = 1)
    {
        Log::write("OrderModel:/n/order notify start: /n" . json_encode($orderData), "info");
        Db::startTrans();
        try {
            //更改订单状态 order
            //1、支付成功（下单成功）！2、支付失败（下单成功）！3、下单失败！4、等待支付（下单成功）！5、已手动回调。6、回调中（还未通知商户）
            $orderWhere['order_me'] = $orderData['order_me'];
            $orderWhere['order_pay'] = $orderData['order_pay'];
//            $order = Db::table('bsa_order')->where($orderWhere)->find();
            //4和6是可回调状态
            if ($orderData['order_status'] != 6 || $orderData['order_status'] != 4) {
                $returnMsg['code'] = 1003;
                $returnMsg['msg'] = "不可回调状态!";
                $returnMsg['data'] = $orderData;
            }

            $orderUpdate['order_status'] = 6;

            if ($orderData['order_status'] == 6) { //手动回调 本地更新未通知四方
                return $this->orderNotifyForMerchant($orderData, 2);
            }
            $orderUpdate['order_status'] = 6;
            $orderUpdate['update_time'] = time();
            $orderUpdate['actual_amount'] = (float)$orderData['actual_amount'];
            Db::table('bsa_order')->where($orderWhere)->update($orderUpdate);
            //更改商户余额 merchant
            $merchantWhere['merchant_sign'] = $orderData['merchant_sign'];
            Db::table('bsa_merchant')->where($merchantWhere)->find();
            Db::table('bsa_merchant')->where($merchantWhere)
                ->update([
                    "amount" => Db::raw("amount") + $orderData['amount']
                ]);
            //接口使用次数
            $payPpiWhere['api_sign'] = $orderData['api_sign'];
            Db::table('bsa_payapi')->where($payPpiWhere)->find();
            Db::table('bsa_payapi')->where($payPpiWhere)
                ->update([
                    "amount" => Db::raw("pay_number") + 1
                ]);
            return $this->orderNotifyForMerchant($orderData);
        } catch (\Exception $exception) {
            Db::rollback();
            Log::write("OrderModel:/n/order notify exception: /n" . json_encode($orderData) . "/n order notify: /n/t exception:" . $exception->getMessage(), "info");
            return modelReMsg(-2, '', $exception->getMessage());
        } catch (\Error $error) {
            Db::rollback();
            Log::write("OrderModel:/n/order notify error: /n" . json_encode($orderData) . "/n order notify: /n/t error:" . $error->getMessage(), "info");
            return modelReMsg(-3, '', $error->getMessage());
        }
    }

    /**
     * 支付成功（通知商户）
     * @param $data
     * @param $status
     * @return void
     * @todo
     */
    public function orderNotifyForMerchant($data, $status = 1)
    {
        try {
            //$status 决定order_status 是手动回调还是自动完成且回调
            $validate = new OrderinfoValidate();
            //请求参数不完整
            if (!$validate->check($data)) {
                $returnMsg['code'] = 1002;
                $returnMsg['msg'] = "回调参数有误!";
                $returnMsg['data'] = $validate->getError();
                return $returnMsg;
            }
            //参与回调参数
            $callbackData['merchant_sign'] = $data['merchant_sign'];
            $callbackData['client_ip'] = $data['callback_ip'];
            $callbackData['order_no'] = $data['order_no'];
            $callbackData['order_pay'] = $data['order_me'];  //
            $callbackData['payment'] = $data['payment'];
            $callbackData['amount'] = $data['amount'];
            $callbackData['actual_amount'] = $data['actual_amount'];
            $callbackData['pay_time'] = $data['pay_time'];
            $callbackData['returnUrl'] = $data['returnUrl'];

            $merchantWhere['merchant_sign'] = $data['merchant_sign'];
            $token = Db::table("bsa_merchant")->where($merchantWhere)->find()['token'];
            $callbackData['key'] = $token;

            unset($callbackData['sign']);
            ksort($callbackData);
            $returnMsg = array();
            $callbackData['sign'] = strtoupper(md5(urldecode(http_build_query($callbackData)) . "&key=" . $token));
//            $sign = md5("merchant_sign=" . $data['merchant_sign'] .
//                "&client_ip=" . $data['client_ip'] .
//                "&order_no=" . $data['order_no'] .
//                "&order_pay=" . $data['order_pay'] .
//                "&payment=" . $data['payment'] .
//                "&amount=" . $data['amount'] .
//                "&actual_amount=" . $data['actual_amount'] .
//                "&pay_time=" . $data['pay_time'] .
//                "&returnUrl=" . $data['returnUrl'] .
//                "&key=" . $data['token']
//            );
            //回调处理
            $notifyResult = curlPost($data['notify_url'], $callbackData);

            Log::log('1', "notify merchant order ", $notifyResult);
            $result = json_decode($notifyResult, true);
            //通知失败

            $orderWhere['order_no'] = $callbackData['order_no'];
            if ($result != "SUCCESS") {
                Db::table('bsa_torder')->where($orderWhere)
                    ->update([
                        'info' => json_encode($notifyResult)
                    ]);
                $returnMsg['code'] = 1000;
                $returnMsg['msg'] = "统计成功，回调商户失败!";
                $returnMsg['data'] = json_encode($notifyResult);
                return $returnMsg;
            }
            //如果是手动回调
            $orderWhere['order_no'] = $callbackData['order_no'];
            if ($status == 2) {
                Db::table('bsa_order')->where($orderWhere)
                    ->update([
                        'order_status' => 5,
                        'update_time' => time(),
                        'status' => 1
                    ]);
            } else {
                $orderUpdate['order_status'] = 1;
                $orderUpdate['update_time'] = time();
                $orderUpdate['status'] = 1;
                Db::table('bsa_order')->where($orderWhere)->update($orderUpdate);
            }
            $returnMsg['code'] = 1000;
            $returnMsg['msg'] = "回调商户成功!";
            $returnMsg['data'] = json_encode($notifyResult);
            return $returnMsg;
        } catch (\Exception $exception) {
            Log::write("/n/t Orderinfo/callbacktomerchant: /n/t" . json_encode($data) . "/n/t" . $exception->getMessage(), "exception");
            return modelReMsg('20009', "", "商户回调异常" . $exception->getMessage());
        } catch (\Error $error) {
            Log::write("/n/t Orderinfo/callbacktomerchant: /n/t" . json_encode($data) . "/n/t" . $error->getMessage(), "error");
            return modelReMsg('20099', "", "商户回调错误" . $error->getMessage());

        }

    }


}