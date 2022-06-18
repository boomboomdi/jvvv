<?php

namespace app\common\model;

use app\api\model\OrderLog;
use app\api\validate\OrderinfoValidate;
use think\Db;
use think\facade\Log;
use think\Model;

class OrderModel extends Model
{
    protected $table = 'bsa_order';

    /**
     * 获取订单
     * @param $limit
     * @param $where
     * @return array
     */
    public function getOrders($limit, $where)
    {
        $prefix = config('database.prefix');
        try {
            $res = $this->field($prefix . 'order.*')->where($where)
                ->order('order_no', 'desc')->paginate($limit);

        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $res, 'ok');
    }


    /**
     * 增加订单
     * @param $Order
     * @return array
     */
    public function addOrder($Order)
    {
        try {
            $has = $this->where('order_no', $Order['order_no'])->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '订单号已经存在');
            }
            $this->create($Order);

            $insData = $this->where('order_no', $Order['order_no'])->find();
            if (!isset($insData['id'])) {
                return modelReMsg(-3, "", "下单失败");
            }
        } catch (\Exception $e) {
            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $insData['id'], '添加订单成功');
    }

    /**
     * 订单回调第一步:匹配订单
     * @param $notifyParam
     * @return void
     */
    public function orderMatch($notifyParam)
    {
        $where = [];
        try {
            if (isset($notifyParam['notify_pay_name']) && !empty($notifyParam['notify_pay_name'])) {
                $where[] = ['notify_pay_name', $notifyParam['notify_pay_name']];
            }

            $where[] = ['account', $notifyParam['account']];
            $where[] = ['order_status', 4];
            $where[] = ['amount', $notifyParam['amount']];
            $where[] = ['operate_time', 'between', [time() - 3000, time()]];

            $info = $this->where($where)->find();
            if (empty($info)) {
                return modelReMsg(-2, '', '未匹配到订单');
            }
        } catch (\Exception $e) {
            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $info, '匹配订单成功');
    }

    /**
     * 订单回调: 通知商户
     * @return void
     */
    public function notifyToCenter()
    {

    }


    /**
     * 订单回调 通道/手动回调 总入口
     * @param $orderData
     * @param $status 1、自动回调 2、手动回调
     * @return array|void
     */
    public function orderNotify($orderData, $status = 1)
    {

        try {
            //更改订单状态 order
            //1、支付成功（下单成功）！2、支付失败（下单成功）！3、下单失败！4、等待支付（下单成功）！5、已手动回调。6、回调中（还未通知商户）
            $orderWhere['order_me'] = $orderData['order_me'];
            //4和6是可回调状态
            if ($orderData['order_status'] != 6 || $orderData['order_status'] != 4) {
                $returnMsg['code'] = 1003;
                $returnMsg['msg'] = "不可回调状态!";
                $returnMsg['data'] = $orderData;
            }
            $notifyRes = $this->orderNotifyForMerchant($orderData, $status);

            if ($notifyRes['code'] != 0) {
                logs(json_encode([
                    'orderData' => $orderData,
                    'status' => $status,
                    'notifyRes' => $notifyRes,
                ]), 'AorderNotifyForMerchantFail');
                return modelReMsg(-2, '', $notifyRes['msg']);
            }
            return modelReMsg(1000, '', $notifyRes['msg']);
        } catch (\Exception $exception) {
            logs(json_encode(['orderData' => $orderData, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'orderNotify_exception');
            return modelReMsg(-2, '', $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['orderData' => $orderData, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'orderNotify_error');
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
        $db = new Db();
        $db::startTrans();
        try {
            //$status 决定order_status 是手动回调还是自动完成且回调
            //参与回调参数
            $callbackData['merchant_sign'] = $data['merchant_sign'];
            $callbackData['order_no'] = (string)$data['order_no'];
            $callbackData['order_status'] = 1;
            $callbackData['order_pay'] = $data['order_me'];
            $callbackData['payment'] = $data['payment'];
            $callbackData['amount'] = $data['amount'];
            $callbackData['actual_amount'] = $data['actual_amount'];
            $callbackData['pay_time'] = date("Y-m-d H:i:s", $data['pay_time']);
            $validate = new OrderinfoValidate();

            //请求参数不完整
            if (!$validate->scene('notify')->check($callbackData)) {

                $returnMsg['code'] = -1;
                $returnMsg['msg'] = "回调参数有误!";
                $returnMsg['data'] = $validate->getError();
                return $returnMsg;
            }
            $merchantWhere['merchant_sign'] = $data['merchant_sign'];
            $token = Db::table("bsa_merchant")->where($merchantWhere)->find()['token'];

            $doMd5String = $callbackData['merchant_sign'] . $callbackData['order_no'] . $callbackData['amount'] . $callbackData['actual_amount'] . $callbackData['pay_time'] . $token;
            $callbackData['sign'] = md5($doMd5String);
            //回调处理
            $startTime = date("Y-m-d H:i:s", time());

            $notifyResult = curlPostJson($data['notify_url'], $callbackData);
            logs(json_encode([
                "order_no" => $callbackData['order_no'],
                "startTime" => $startTime,
                "endTime" => date("Y-m-d H:i:s", time()),
                'callbackData' => $callbackData,
                'notify_url' => $data['notify_url'],
                'notifyResult' => $notifyResult
            ]), 'curlPostForMerchant');
            $notifyResultLog = "第" . ($data['notify_times'] + 1) . "次回调:" . (string)($notifyResult) . "(" . date("Y-m-d H:i:s") . ")";

            //通知结果不为success
            if ($notifyResult != "success") {
                $db::rollback();
                $db::table('bsa_order')->where('order_no', '=', $callbackData['order_no'])
                    ->update([
                        'notify_time' => time(),
                        'notify_status' => 2,
                        'notify_times' => $data['notify_times'] + 1,
                        'notify_result' => $notifyResultLog,
                        'order_desc' => "回调失败:" . $notifyResult
                    ]);
                return modelReMsg(-3, "", "回调结果失败！");
            }
            $db::table('bsa_order')->where('order_no', '=', $callbackData['order_no'])
                ->update([
                    'notify_time' => time(),
                    'notify_status' => 1,
                    'notify_times' => $data['notify_times'] + 1,
                    'notify_result' => $notifyResultLog,
                    'order_desc' => "回调成功:" . $notifyResult
                ]);
            $db::commit();
            return modelReMsg(0, "", json_encode($notifyResult));

        } catch (\Exception $exception) {
            $db::rollback();
            logs(json_encode(['data' => $data, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'orderNotifyToMerchant_exception');
            return modelReMsg(-2, '', $exception->getMessage());
        } catch (\Error $error) {
            $db::rollback();
            logs(json_encode(['data' => $data, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'orderNotifyToMerchant_error');
            return modelReMsg(-3, '', $error->getMessage());
        }

    }

    /**
     * 下单更新
     * @param $where
     * @param $updateData
     * @return array
     */
    public function localUpdateOrder($where, $updateData)
    {
        $db = new Db();
        $db::startTrans();
        try {
            $orderInfo = $this->where($where)->lock(true)->find();
            if (!$orderInfo) {
                $db::rollback();
                logs(json_encode([
                    'orderWhere' => $where,
                    'updateData' => $updateData,
                    'orderInfo' => $orderInfo
                ]), 'AAlocalhostUpdateOrderFail_log');
                return modelReMsg(-1, "", "更新失败!");
            }
            $updateRes = $this->where($where)->update($updateData);

            if (!$updateRes) {
                $db::rollback();
                logs(json_encode([
                    'orderWhere' => $where,
                    'updateData' => $updateData,
                    'updateRes' => $updateRes
                ]), 'AAlocalhostUpdateOrderFail_log');
                return modelReMsg(-2, "", "更新失败");
            }
            logs(json_encode([
                'orderWhere' => $where,
                'updateData' => $updateData,
                'updateRes' => $updateRes,
                'lastSal' => $db::order("bsa_order")->getLastSql()
            ]), 'localhostUpdateOrder_log');
            $db::commit();
            return modelReMsg(0, "", "更新成功");

        } catch (\Exception $exception) {

            $db::rollback();
            logs(json_encode(['file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()
            ]), 'localhostUpdateOrderException');
            return modelReMsg(-11, "", $exception->getMessage());
        } catch (\Error $error) {

            $db::rollback();
            logs(json_encode([
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()
            ]), 'localhostUpdateOrderError');
            return modelReMsg(-22, "", $error->getMessage());
        }
    }


    /**
     * @param $order
     * @return array
     */
    public function getOrderUrl($order)
    {
        try {
            if(empty($order['ck_account'])){
                return modelReMsg(-1, "", 'cookie fail');
            }
            $cookie = Db::table("bsa_cookie")->where('account', '=', $order['ck_account'])->find();
            if (empty($cookie)) {
                return modelReMsg(-1, "", 'cookie fail');
            }
            $checkParam['qr_url'] = $order['qr_url'];
            $checkParam['cookie'] = $cookie['cookie'];
            $checkParam['os'] = $order['os'];

            $checkStartTime = date('Y-m-d H:i:s', time());
            $checkUrlRes = curlPostJson("http://127.0.0.1:23942/getRealurl", $checkParam);

            logs(json_encode([
                'param' => $checkParam,
                "startTime" => $checkStartTime,
                "endTime" => date("Y-m-d H:i:s", time()),
                "getOrderUrlRes" => $checkUrlRes
            ]), 'curlGetOrderUrlTwo');

            $checkUrlRes = json_decode($checkUrlRes, true);
            if (!isset($checkUrlRes['code']) || $checkUrlRes['code'] != 0) {
                return modelReMsg(-2, "", '订单链接获取失败，请重新下单！');
            }
            return modelReMsg(0, $checkUrlRes['data'], 'ok');
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
    }

}