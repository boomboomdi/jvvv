<?php

namespace app\api\controller;


use think\Controller;
use think\Db;
use app\common\model\OrderhexiaoModel;
use app\common\model\OrderModel;
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

class Orderinfo extends Controller
{

    /**
     * 正式入口
     * @param Request $request
     * @return void
     */
    public function order(Request $request)
    {
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);

        $db = new Db();
        try {
            logs(json_encode(['message' => $message, 'line' => $message]), 'order_fist');
            $validate = new OrderinfoValidate();
            if (!$validate->check($message)) {
                return apiJsonReturn(-1, '', $validate->getError());
            }
            $db = new Db();
            //验证商户
            $token = $db::table('bsa_merchant')->where('merchant_sign', '=', $message['merchant_sign'])->find()['token'];
            if (empty($token)) {
                return apiJsonReturn(-2, "商户验证失败！");
            }
            $sig = md5($message['merchant_sign'] . $message['order_no'] . $message['amount'] . $message['time'] . $token);
            if ($sig != $message['sign']) {
                logs(json_encode(['orderParam' => $message, 'doMd5' => $sig]), 'orderParam_signfail');
                return apiJsonReturn(-3, "验签失败！");
            }
            $orderFind = $db::table('bsa_order')->where('order_no', '=', $message['order_no'])->count();
            if ($orderFind > 0) {
                return apiJsonReturn(-4, "单号重复！");
            }

            //$user_id = $message['user_id'];  //用户标识
            // 根据user_id  未付款次数 限制下单 end

            $orderNoFind = $db::table('bsa_order')->where('order_no', '=', $message['order_no'])->find();
            if (!empty($orderNoFind)) {
                return apiJsonReturn(-5, "该订单号已存在！");
            }
            $orderLimitTime = SystemConfigModel::getOrderLockTime();
            $orderHxCanUseTime= SystemConfigModel::getOrderHxCanUseTime();
            $db::startTrans();
            $hxOrderData = $db::table("bsa_order_prepare")
                ->where('order_amount', '=', $message['amount'])
                ->where('status', '=', 3)               //默认初始状态
                ->where('order_status', '=', 3)         //等待匹配
                ->where('get_url_status', '=', 1)       //预拉成功
                ->where('order_limit_time', '=', 0)
                ->where('check_status', '<>', 1)        //是否查单使用中
                ->where('add_time', '>', time() - $orderHxCanUseTime) //  匹配当前时间在 核销限制回调时间480s之前的核销单
                ->order("add_time asc")
                ->lock(true)
                ->find();
            if (!$hxOrderData) {
                $db::rollback();
                return apiJsonReturn(-5, "无可用订单-5！！");
            }

            //下单成功
            $insertOrderData['merchant_sign'] = $message['merchant_sign'];  //商户
            $insertOrderData['amount'] = $message['amount']; //支付金额
            $insertOrderData['order_no'] = $message['order_no'];  //商户订单号
            ;  // 0、等待访问 1、支付成功（下单成功）！2、支付失败（下单成功）！3、下单失败！4、等待支付（下单成功）！5、已手动回调。
            $insertOrderData['order_status'] = 0; //状态
            $insertOrderData['payable_amount'] = $message['amount'];       //应付金额
            $insertOrderData['order_limit_time'] = (time() + $orderLimitTime);  //订单表
            $insertOrderData['next_check_time'] = (time() + 30);   //下次查询余额时间
            $insertOrderData['payment'] = $message['payment']; //JDPAY
            $insertOrderData['add_time'] = time();  //入库时间
            $insertOrderData['notify_url'] = $message['notify_url']; //下单回调地址 notify url
            $insertOrderData['order_desc'] = "等待访问!"; //订单描述
            $insertOrderData['check_result'] = "等待访问！";

//            $url = "http://175.178.241.238/pay/#/kindsRoll";
//            $url = "http://175.178.241.238/pay/#/jdios";   //京东页面
//            $url = "http://175.178.241.238/pay/#/wxsrc";   //京东页面
//            $url = "http://175.178.241.238/pay/#/weixinpay";   //京东页面
            $url = "http://175.178.241.238/pay/#/wsf";   //京东页面
            $url = "http://175.178.241.238/pay/#/pddwx";   //拼多多页面
            $apiUrl = $request->domain() . "/api/orderinfo/getorderinfo";
            $url = $url . "?order_id=" . $message['order_no'] . "&amount=" . $message['amount'] . "&apiUrl=" . $apiUrl;
            $orderModel = new OrderModel();
            $createOrderOne = $orderModel->addOrder($insertOrderData);
            if (!isset($createOrderOne['code']) || $createOrderOne['code'] != 0) {
                $db::rollback();
                logs(json_encode(['action' => 'getUseHxOrderRes',
                    'insertOrderData' => $insertOrderData,
                    'createOrderOne' => $createOrderOne,
                    'lastSal' => $db::order("bsa_order")->getLastSql()
                ]), 'addOrderFail_log');
                json(msg(-8, $url, "下单有误！"));
            }
            $db::commit();
            return json(msg(10000, $url, "下单成功"));
//            return apiJsonReturn(10000, "下单成功", $url);
        } catch (\Error $error) {

            logs(json_encode(['file' => $error->getFile(),
                'line' => $error->getLine(), 'errorMessage' => $error->getMessage()
            ]), 'orderError');
            return json(msg(-22, '', "接口异常!-22"));
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage(),
                'lastSql' => $db::table('bsa_order')->getLastSql(),
            ]), 'orderException');
            return json(msg(-11, '', "接口异常!-11"));
        }
    }


    /**
     * 获取订单链接
     * @param Request $request
     * @return array|bool|\think\response\Json
     */
    public function getOrderInfo(Request $request)
    {

        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);

        try {
            $orderShowTime = SystemConfigModel::getOrderShowTime();
            logs(json_encode([
                'action' => 'getOrderInfo',
                'message' => $message
            ]), 'getOrderInfo');
            if (!isset($message['order_no']) || empty($message['order_no'])) {
                return json(msg(-1, '', '单号有误！'));
            }
            if (!isset($message['os']) || empty($message['os'])) {
                return json(msg(-1, '', '请求有误！'));
            }
            $db = new Db();
            $orderInfo = $db::table("bsa_order")
                ->where("order_no", "=", $message['order_no'])->find();
            if (empty($orderInfo)) {
                return json(msg(-2, '', '订单不存在！'));
            }
            if ($orderInfo['pay_status'] == 1) {
                return json(msg(-3, '', '订单已支付！'));
            }
            if (time() > ($orderInfo['add_time'] + $orderShowTime)) {
                return json(msg(-5, '', '订单超时，请重新下单'));
            }
            if ($orderInfo['order_status'] != 4) {
                if ($orderInfo['order_status'] == 0) {
                    $orderHxLockTime = SystemConfigModel::getOrderHxLockTime();
                    $db::startTrans();
                    $hxOrderData = $db::table("bsa_order_prepare")
                        ->where('order_amount', '=', $orderInfo['amount'])
                        ->where('status', '=', 3)               //默认初始状态
                        ->where('order_status', '=', 3)         //等待匹配
                        ->where('get_url_status', '=', 1)       //预拉成功
                        ->where('order_limit_time', '=', 0)
                        ->where('check_status', '<>', 1)        //是否查单使用中
                        ->where('add_time', '>', time() - $orderHxLockTime) //  匹配当前时间在 核销限制回调时间480s之前的核销单
                        ->order("add_time asc")
                        ->lock(true)
                        ->find();
                    if (!$hxOrderData) {
                        $db::rollback();
                        $orderUpdate['order_desc'] = "无预拉单|匹配失败"; //支付订单
                        // 0、等待下单 1、支付成功（下单成功）！2、支付失败（下单成功）！3、下单失败！4、等待支付（下单成功）！5、已手动回调。
                        $orderUpdate['order_status'] = 3;
                        $updateOrderRes = $db::table("bsa_order")
                            ->where("order_no", "=", $message['order_no'])
                            ->update($orderUpdate);
                        if (!$updateOrderRes) {
                            logs(json_encode([
                                'action' => 'updateMatchForOrder',
                                'orderNo' => $message['order_no'],
                                'updateMatch' => $hxOrderData,
                                'updateOrderRes' => $updateOrderRes,
                            ]), 'updateOrderFailMatchFail');
                            $db::rollback();
                            return json(msg(-101, '', '下单频繁，请稍后再下-101！'));
                        }
                        $db::commit();
                        return json(msg(-102, '', '无可用订单-102！'));
                    }
                    $hxWhere['id'] = $hxOrderData['id'];
                    $hxWhere['order_me'] = $hxOrderData['order_me'];
                    $updateMatch['status'] = 1;           //使用中
                    $updateMatch['check_status'] = 2;     //查单中
                    $updateMatch['order_status'] = 1;     //以匹配
                    $updateMatch['use_time'] = time();    //使用时间
                    $updateMatch['last_use_time'] = time();
                    $updateMatch['order_limit_time'] = (time() + $orderHxLockTime);  //匹配成功后锁定
                    $updateMatch['order_status'] = 1;
                    $updateMatch['order_no'] = $message['order_no'];   //四方单号
                    $updateMatch['order_desc'] = "等待访问！";
                    $updateMatch['check_result'] = "等待访问！";
                    $updateHxOrderRes = $db::table("bsa_order_prepare")
                        ->where($hxWhere)
                        ->update($updateMatch);
                    if (!$updateHxOrderRes) {
                        logs(json_encode([
                            'action' => 'updateMatchForHxOrder',
                            'hxWhere' => $hxWhere,
                            'updateMatch' => $updateMatch,
                            'updateMatchSuccessRes' => $updateHxOrderRes,
                        ]), 'updateMatchSuccessFail');
                        $db::rollback();
                        return json(msg(-103, '', '下单频繁，请稍后再下-103！'));
                    }
                    $orderUpdate['user_ip'] = getLocationByIp(request()->ip())."|".request()->ip();
                    $orderUpdate['click_time'] = time();
                    $orderUpdate['order_status'] = 4;
                    $orderUpdate['order_desc'] = "匹配成功，等待访问";            //描述
                    $orderUpdate['order_me'] = $hxOrderData['order_me'];       //本平台订单号
                    $orderUpdate['order_pay'] = $hxOrderData['order_pay'];     //抖音单号
                    $orderUpdate['ck_account'] = $hxOrderData['ck_account'];   //cookie account
                    $orderUpdate['qr_url'] = $hxOrderData['qr_url']; //支付订单
                    //前端请求你新增字段：os（数据为：android或ios）
                    //你请求我新增字段：os（数据为：android或ios）
                    $orderUpdate['pay_type'] = $message['os'];
                    $updateOrderRes = $db::table("bsa_order")
                        ->where("order_no", "=", $message['order_no'])
                        ->update($orderUpdate);
                    if (!$updateOrderRes) {
                        logs(json_encode([
                            'action' => 'updateMatchForOrder',
                            'orderNo' => $message['order_no'],
                            'updateMatch' => $hxOrderData,
                            'updateMatchSuccessRes' => $updateHxOrderRes,
                        ]), 'updateMatchSuccessFail');
                        $db::rollback();

                        return json(msg(-104, '', '下单频繁，请稍后再下-104！'));
                    }
                    $db::commit();
                    $orderInfo = $db::table("bsa_order")
                        ->where("order_no", "=", $message['order_no'])->find();
                }
            }
            $orderModel = new OrderModel();
            $getPayUrlRes = $orderModel->getOrderUrl($orderInfo);

            if (!isset($getPayUrlRes['code']) || $getPayUrlRes['code'] != 0) {
                return json(msg(-7, '', "订单链接获取失败，请重新下单！"));
            }
            $returnData['amount'] = $orderInfo['amount'];
            $returnData['payUrl'] = $getPayUrlRes['data'];
            $limitTime = (($orderInfo['add_time'] + $orderShowTime) - time());
            $returnData['limitTime'] = (int)($limitTime);
            return json(msg(0, $returnData, "success"));
        } catch (\Exception $exception) {
            logs(json_encode(['param' => $message,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()]), 'getOrderInfoException');
            return apiJsonReturn(-11, "exception!");
        } catch (\Error $error) {
            logs(json_encode(['param' => $message,
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()]), 'getOrderInfoError');
            return json(msg(-22, '', "error!"));
        }
    }

    /**
     * 引导页面查询订单状态getOrderStatus
     */
    public function getOrderStatus(Request $request)
    {
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);

        try {
            $orderShowTime = SystemConfigModel::getOrderShowTime();
            logs(json_encode([
                'action' => 'getOrderInfo',
                'message' => $message
            ]), 'getOrderInfo');
            if (!isset($message['order_no']) || empty($message['order_no'])) {
                return json(msg(-1, '', '单号有误！'));
            }
            $db = new Db();
            $orderInfo = $db::table("bsa_order")
                ->where("order_no", "=", $message['order_no'])->find();
            if (empty($orderInfo)) {
                return json(msg(-2, '', '订单不存在！'));
            }
            if ($orderInfo['pay_status'] == 1) {
                return json(msg(-33, '', '订单已支付！'));
            }
            if (time() > ($orderInfo['add_time'] + $orderShowTime)) {
                return json(msg(-5, '', '订单超时，请重新下单'));
            }
            if ($orderInfo['order_status'] != 4) {
                return json(msg(-6, '', '订单状态有误，请重新下单！'));
            }
            $returnData['amount'] = $orderInfo['amount'];
            $limitTime = (($orderInfo['add_time'] + $orderShowTime) - time());
            $returnData['limitTime'] = (int)($limitTime);
            return json(msg(0, $returnData, "success"));
        } catch (\Exception $exception) {
            logs(json_encode(['param' => $message,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()]), 'getOrderInfoException');
            return apiJsonReturn(-11, "exception!");
        } catch (\Error $error) {
            logs(json_encode(['param' => $message,
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()]), 'getOrderInfoError');
            return json(msg(-22, '', "error!"));
        }
    }

    public function changeCookieStatus(Request $request)
    {
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);

        try {
            logs(json_encode([
                'action' => 'changeCookieStatus',
                'message' => $message
            ]), 'changeCookieStatus');
            if (!isset($message['ck_account']) || empty($message['ck_account'])) {
                return json(msg(-1, '', 'ck_account！'));
            }
            $db = new Db();
            $cookieInfo = $db::table("bsa_cookie")
                ->where("account", "=", $message['ck_account'])->find();
            if (empty($cookieInfo)) {
                return json(msg(-2, '', '不存在！'));
            }
            $where['account'] = $message['ck_account'];
            $update['error_times'] = $cookieInfo['error_times'] + 1;
            $update['order_desc'] = '主动失效(预拉错误' . ($cookieInfo['error_times'] + 1) . ')';
            $update['status'] = 2;
            $res = $db::table("bsa_cookie")->where($where)->update($update);
            if(!$res){
                logs(json_encode([
                    'action' => 'changeCookieStatus',
                    'where' => $where,
                    'update' => $update,
                    'res' => $res
                ]), 'changeCookieStatusFail');
                return json(msg(-9, '', "change fail"));
            }
            return json(msg(0, '', "success"));
        } catch (\Exception $exception) {
            logs(json_encode(['param' => $message,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()]), 'changeCookieStatusException');
            return apiJsonReturn(-11, "exception!");
        } catch (\Error $error) {
            logs(json_encode(['param' => $message,
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()]), 'changeCookieStatusError');
            return json(msg(-22, '', "error!"));
        }
    }
}