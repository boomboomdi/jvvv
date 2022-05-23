<?php

namespace app\api\controller;


use think\Controller;
use think\Db;
use app\common\model\OrderhexiaoModel;
use app\common\model\OrderModel;
use app\api\validate\OrderinfoValidate;
use app\api\validate\CheckPhoneAmountNotifyValidate;
use think\Request;
use app\common\model\SystemConfigModel;
use think\Validate;
use app\common\Redis;

header('Access-Control-Allow-Origin:*');
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept,Authorization");
header('Access-Control-Allow-Methods:GET,POST,PUT,DELETE,OPTIONS,PATCH');

class OrderinfoHUAFEI extends Controller
{


    /**
     * 正式入口
     * @param Request $request
     * @return void
     */
    public function order(Request $request)
    {
//        session_write_close();

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

            $orderMe = md5(uniqid() . getMillisecond());
            $orderFind = $db::table('bsa_order')->where('order_me', '=', $orderMe)->find();
            if (!empty($orderFind)) {
                $orderMe = md5(uniqid() . getMillisecond());
            }
            $orderNoFind = $db::table('bsa_order')->where('order_no', '=', $message['order_no'])->find();
            if (!empty($orderNoFind)) {
                return apiJsonReturn(-5, "该订单号已存在！");
            }
            $bsaWriteOff = $db::table("bsa_write_off")->where('status', '=', 1)->column('write_off_sign');
            if (empty($bsaWriteOff)) {
                return apiJsonReturn(-6, "无可匹配订单！");
            }
            $orderLimitTime = SystemConfigModel::getOrderLockTime();
            $db::startTrans();
            $hxOrderData = $db::table("bsa_order_hexiao")
                ->field("bsa_order_hexiao.*")
                ->where('order_amount', '=', $message['amount'])
                ->where('order_me', '=', null)
                ->where('status', '=', 0)
                ->where('order_status', '=', 0)
                ->where('write_off_sign', 'in', $bsaWriteOff)
                ->where('order_limit_time', '=', 0)
                ->where('check_status', '=', 0)  //是否查单使用中
                ->where('limit_time', '>', time() + $orderLimitTime) //  匹配当前时间在 核销限制回调时间480s之前的核销单
                ->order("add_time asc")
                ->lock(true)
                ->find();
            if (!$hxOrderData) {
                $db::rollback();
                return apiJsonReturn(-5, "无可用订单-5！！");
            }

//            var_dump($hxOrderData);exit;
//            $url = "http://175.178.241.238/pay/#/huafei";
            $url = "http://175.178.241.238/pay/#/kindsRoll";
//            if ($message['payment'] == "alipay") {
//                //支付宝 http://175.178.241.238/pay/#/huafeiZfb?order_id=1652284620.115997636502970&amount=30
////                $url = "http://175.178.241.238/pay/#/huafeiZfb";
//                $url = "http://175.178.241.238/pay/#/huafeiNewZfb";
//            }
            $apiUrl = $request->domain() . "/api/orderinfo/getorderinfo";
            $url = $url . "?order_id=" . $message['order_no'] . "&amount=" . $message['amount'] . "&apiUrl=" . $apiUrl;

            $orderHxLockTime = SystemConfigModel::getOrderHxLockTime();
            $hxWhere['id'] = $hxOrderData['id'];
            $hxWhere['order_no'] = $hxOrderData['order_no'];
            $updateMatch['check_status'] = 0;
            $updateMatch['status'] = 1;   //匹配中
            $updateMatch['use_time'] = time();   //使用时间
            $updateMatch['last_use_time'] = time();
            $updateMatch['order_limit_time'] = (time() + $orderHxLockTime);  //匹配成功后锁定3600s 后没支付可以重新解锁匹配
            $updateMatch['order_status'] = 1;
            $updateMatch['order_me'] = $orderMe;
            $updateMatch['order_desc'] = "等待访问！";
            $updateMatch['check_result'] = "等待访问！";

            $updateHxOrderRes = $db::table("bsa_order_hexiao")->where($hxWhere)->update($updateMatch);
            logs(json_encode([
                'action' => 'updateMatch',
                'hxWhere' => $hxWhere,
                'updateMatch' => $updateMatch,
                'updateMatchSuccessRes' => $updateHxOrderRes,
            ]), 'updateMatchSuccess');
            if (!$updateHxOrderRes) {
                logs(json_encode([
                    'action' => 'updateMatch',
                    'hxWhere' => $hxWhere,
                    'updateMatch' => $updateMatch,
                    'updateMatchSuccessRes' => $updateHxOrderRes,
                ]), 'updateMatchSuccessFail');
                $db::rollback();
                return apiJsonReturn(-5, '', '下单频繁，请稍后再下-5！');
            }
            $insertOrderData['order_status'] = 0;
            //下单成功
            $insertOrderData['merchant_sign'] = $message['merchant_sign'];  //商户
            $insertOrderData['amount'] = $message['amount']; //支付金额
            $insertOrderData['order_no'] = $message['order_no'];  //商户订单号
            ;  // 0、等待下单 1、支付成功（下单成功）！2、支付失败（下单成功）！3、下单失败！4、等待支付（下单成功）！5、已手动回调。
            $insertOrderData['order_status'] = 0; //状态
            $insertOrderData['order_me'] = $orderMe; //本平台订单号
            $insertOrderData['order_pay'] = $hxOrderData['order_no'];   //匹配核销单订单号
            $insertOrderData['account'] = $hxOrderData['account'];   //匹配核销单账号
            $insertOrderData['write_off_sign'] = $hxOrderData['write_off_sign'];   //匹配核销单核销商标识
            $insertOrderData['payable_amount'] = $message['amount'];  //应付金额
            $insertOrderData['order_limit_time'] = (time() + $orderLimitTime);  //订单表 $orderLimitTime
            $insertOrderData['next_check_time'] = (time() + 90);   //下次查询余额时间（第二次）
            $insertOrderData['payment'] = $message['payment']; //alipay
            $insertOrderData['add_time'] = time();  //入库时间
            $insertOrderData['notify_url'] = $message['notify_url']; //下单回调地址 notify url
            $insertOrderData['qr_url'] = $url; //支付订单
            $insertOrderData['order_desc'] = "等待访问!"; //订单描述

            $orderModel = new OrderModel();
            $createOrderOne = $orderModel->addOrder($insertOrderData);
//            $createOrderOne = $db::table("bsa_order")->insert($insertOrderData);;
            if (!isset($createOrderOne['code']) || $createOrderOne['code'] != 0) {
                $db::rollback();
                logs(json_encode(['action' => 'getUseHxOrderRes',
                    'insertOrderData' => $insertOrderData,
                    'createOrderOne' => $createOrderOne,
                    'lastSal' => $db::order("bsa_order")->getLastSql()
                ]), 'addOrderFail_log');
                return apiJsonReturn(-6, "下单有误！");
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
     * 玩家点击支付方式
     * @return \think\response\Json
     */
    public function addOrderPayType()
    {
        $data = @file_get_contents('php://input');
        $param = json_decode($data, true);
        logs(json_encode(['param' => $param]), 'addOrderPayType');
        if (!isset($param['payType']) || empty($param['payType'])) {
            return json(msg(-1, '', "payType error"));
        }
        if (!isset($param['order_no']) || empty($param['order_no'])) {
            return json(msg(-1, '', "order_no error"));
        }
        try {

            $orderShowTime = SystemConfigModel::getOrderShowTime();
            $db = new Db();
//            $orderModel = new OrderModel();
            $orderInfo = $db::table("bsa_order")
                ->where("order_no", "=", $param['order_no'])
                ->find();
            if (empty($orderInfo)) {
                return json(msg(-1, '', "order error"));
            }
            if (($orderInfo['add_time'] + $orderShowTime) < time()) {
                return json(msg(-2, '', "order time out"));
            }
            $updateData['user_ip'] = getLocationByIp(request()->ip());
            $updateData['click_time'] = time();
            $updateData['pay_name'] = htmlspecialchars($param['payType']);
            $db::table("bsa_order")
                ->where("order_no", "=", $param['order_no'])
                ->update($updateData);
            return json(msg(0, '', "success"));
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(),
                'line' => $error->getLine(), 'errorMessage' => $error->getMessage()
            ]), 'addOrderPayTypeError');
            return json(msg(-22, '', "Error-22"));
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage(),
                'lastSql' => $db::table('bsa_order')->getLastSql(),
            ]), 'addOrderPayTypeException');
            return json(msg(-11, '', "Exception-11"));
        }
    }

    /**
     * 引导页面查询订单状态
     */
    public function getOrderInfo(Request $request)
    {
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);

        $orderLockTime = SystemConfigModel::getOrderLockTime();
        $orderShowTime = SystemConfigModel::getOrderShowTime();
        logs(json_encode([
            'action' => 'getOrderInfo',
            'message' => $message
        ]), 'getOrderInfo');
        if (!isset($message['order_no']) || empty($message['order_no'])) {
            return json(msg(-1, '', '单号有误！'));
        }
        $db = new Db();
        $orderModel = new OrderModel();
//        $where['order_no'] = $message['order_no'];
        $orderInfo = $db::table("bsa_order")
            ->where("order_no", "=", $message['order_no'])->find();
        if (empty($orderInfo)) {
            logs(json_encode([
                'action' => 'lockFail',
                'message' => $message,
                'lockRes' => $orderInfo,
            ]), 'getOrderInfoFail');
            return json(msg(-2, '', '访问繁忙，重新下单！'));
        }
        try {
            if ($orderInfo['order_status'] == 7) {

                logs(json_encode([
                    'action' => 'doMatching',
                    'message' => $message,
                ]), 'getOrderInfodoMatching');
                for ($i = 0; $i < 5; $i++) {
                    sleep(3);
                    $orderInfo = $db::table("bsa_order")->where("order_no", "=", $orderInfo['order_no'])->find();
                    if ($orderInfo['order_status'] == 4) {
                        if (($orderInfo['add_time'] + $orderShowTime) < time()) {
                            return json(msg(-5, '', '订单超时，请重新下单'));
                            break;
                        }
                        $returnData['phone'] = $orderInfo['account'];
                        $returnData['amount'] = $orderInfo['amount'];
                        $limitTime = (($orderInfo['add_time'] + $orderShowTime) - time());
                        $returnData['limitTime'] = (int)($limitTime);
//                $imgUrl = "http://175.178.195.147:9090/upload/tengxun.jpg";

                        $imgUrl = $request->domain() . "/upload/weixin517.jpg";
//                $imgUrl = urlencode($imgUrl);
                        $returnData['imgUrl'] = $imgUrl;
                        return json(msg(0, $returnData, "success"));

                    } else if ($orderInfo['order_status'] == 3) {
                        return json(msg(-3, '', '匹配繁忙，重新下单！'));
                    } else {
                        sleep(1);
                        continue;
                    }
                }
                return json(msg(-9, "", "网络异常，请刷新页面"));
            }
            if ($orderInfo['order_status'] == 0) {

                //展示时间 getOrderShowTime    getAutoCheckOrderTime
                if (time() > ($orderInfo['add_time'] + $orderShowTime)) {
                    return json(msg(-5, '', '订单超时，请重新下单'));
                }
                $db::startTrans();
                $orderInfo = $db::table("bsa_order")
                    ->where("order_no", "=", $orderInfo['order_no'])
                    ->where("order_status", "=", 0)
                    ->lock(true)
                    ->find();
                if (!$orderInfo || $orderInfo['order_status'] > 0) {
                    logs(json_encode([
                        'action' => 'lockFail',
                        'message' => $message,
                        'lockRes' => $orderInfo,
                    ]), 'getOrderInfoFail');
                    $db::rollback();
                    return json(msg(-6, '', '请刷新或重新下单！-4'));
                }
                //更新为下当中状态
                $doMatch['order_status'] = 7;
                $db::table("bsa_order")->where("order_no", "=", $orderInfo['order_no'])->update($doMatch);
                $db::commit();
                //2、请求核销单
                $orderHXModel = new OrderhexiaoModel();
                $getUseHxOrderRes = $orderHXModel->getUseHxOrderNew($orderInfo);

                if (!isset($getUseHxOrderRes['code']) || $getUseHxOrderRes['code'] != 0) {
                    if (is_array($getUseHxOrderRes['data'])) {
                        $getUseHxOrderRes['data'] = json_encode($getUseHxOrderRes['data']);
                    }
                    logs(json_encode([
                        'action' => 'getUseHxOrderFail',
                        'insertOrderData' => $orderInfo,
                        'getUseHxOrderRes' => $getUseHxOrderRes
                    ]), 'getOrderInfoAmount_log');
                    //修改订单为下单失败状态。
                    $updateOrderStatus['order_status'] = 3;
                    $updateOrderStatus['last_use_time'] = time();
                    $updateOrderStatus['check_result'] = $getUseHxOrderRes['data'];
                    $updateOrderStatus['order_desc'] = "下单失败|-|" . $getUseHxOrderRes['msg'];
                    $updateMatchRes = $orderModel->where('order_no', $orderInfo['order_no'])->update($updateOrderStatus);
                    if (!$updateMatchRes) {
                        logs(json_encode([
                            'action' => 'updateMatchRes',
                            'message' => $message,
                            'updateMatchRes' => $updateMatchRes,
                        ]), 'getOrderInfoFail');
                        return json(msg(-5, '', '下单繁忙'));
                    }
                    return json(msg(-5, '', "下单繁忙，可重新下单！"));
                }
                //有没有之前没有支付的订单匹配的上金额
                $hasPayOrderData = $orderModel
                    ->where("account", '=', $orderInfo['account'])
                    ->where("order_pay", '=', $orderInfo['order_pay'])
                    ->where("order_no", '<>', $orderInfo['order_no'])
                    ->where("order_status", '<>', 1)
                    ->where("pay_status", '<>', 1)
                    ->where("end_check_amount", '<', $getUseHxOrderRes['data']['last_check_amount'] + 10)
                    ->where("add_time", '>', time() - 4600)
                    ->order('add_time desc')
                    ->find();
                if (!empty($hasPayOrderData)) {
                    logs(json_encode([
                        "nowTime" => date("Y-m-d H:i:s", time()),
                        'findLoseOrder' => $hasPayOrderData['order_no'],
                        'add_time' => $hasPayOrderData['add_time'],
                        'account' => $hasPayOrderData['account'],

                    ]), 'AfindHasPayOrderAndNotify');
                    //当前订单更改为下单失败状态
                    $updateOrderStatus['order_status'] = 3;
                    $updateOrderStatus['last_use_time'] = time();
                    $updateOrderStatus['check_result'] = "发现掉单此单失败" . json_encode($hasPayOrderData['order_no']);
                    $updateOrderStatus['order_desc'] = "发现掉单|此单下单失败！";
                    $updateMatchRes = $orderModel->where('order_no', $orderInfo['order_no'])->update($updateOrderStatus);
                    if (!$updateMatchRes) {
                        logs(json_encode([
                            'action' => 'updateMatchRes',
                            'message' => $message,
                            'updateMatchRes' => $updateMatchRes,
                        ]), 'hasPayOrderUpdateFail');
                        return json(msg(-31, '', '下单失败,请重新下单！-31'));
                    }
                    //修改订单为下单失败状态。  end

                    //给之前的订单回调 start
                    $orderHXModel = new OrderhexiaoModel();
                    $updateOrderWhere['order_no'] = $hasPayOrderData['order_no'];
                    $updateOrderWhere['account'] = $hasPayOrderData['account'];
                    //订单表
                    $localUpdateRes = $orderHXModel->loseOrderLocalUpdateNew($hasPayOrderData, 3, $getUseHxOrderRes['data']['last_check_amount']);
                    logs(json_encode([
                        "time" => date("Y-m-d H:i:s", time()),
                        'findLoseOrder' => $hasPayOrderData['order_no'],
                        'updateOrderWhere' => $updateOrderWhere,
                        'account' => $hasPayOrderData['account'],
                        'localUpdateRes' => $localUpdateRes
                    ]), 'AfindHasPayOrderAndNotify');
                    if (!isset($localUpdate['code']) || $localUpdate['code'] != 0) {
                        return json(msg(32, '', '下单失败，请重新下单2！'));
                    }
                    //给之前的订单回调 end
                    return json(msg(32, '', '下单失败，请重新下单1'));
                }
                $updateOrderStatus['order_status'] = 4;   //等待支付状态
                $updateOrderStatus['check_times'] = 1;   //下单成功就查询一次
                $updateOrderStatus['start_check_amount'] = $getUseHxOrderRes['data']['last_check_amount'];  //开单余额
                $updateOrderStatus['last_check_amount'] = $getUseHxOrderRes['data']['last_check_amount'];  //第一次查询余额
                $updateOrderStatus['end_check_amount'] = $getUseHxOrderRes['data']['last_check_amount'] + $orderInfo['amount'];  //应到余额
                $updateOrderStatus['order_desc'] = "下单成功,等待支付！";
//                $url = "http://175.178.241.238/pay/#/huafei";
                $url = "http://175.178.241.238/pay/#/kindsRoll";
//                if (isset($orderInfo['payment']) && $orderInfo['payment'] == "alipay") {
//                    $url = "http://175.178.241.238/pay/#/huafeiNewZfb";
//                }
//            订单号order_id   金额 amount   手机号 phone  二维码链接 img_url    有效时间 limit_time 秒
//            $imgUrl = "http://175.178.195.147:9090/upload/huafei.jpg";
//                $imgUrl = "http://175.178.195.147:9090/upload/tengxun.jpg";
                $imgUrl = $request->domain() . "/upload/weixin517.jpg";
//                $imgUrl = urlencode($imgUrl);

                $limitTime = (($orderInfo['add_time'] + $orderShowTime) - time());
                $url = $url . "?order_id=" . $message['order_no'] . "&amount=" . $orderInfo['amount'] . "&phone=" . $orderInfo['account'] . "&img_url=" . $imgUrl . "&limit_time=" . $limitTime;
                $updateOrderStatus['qr_url'] = $url;   //支付订单
//            $localOrderUpdateRes = $orderModel->localUpdateOrder($updateWhere, $updateOrderStatus);
                $localOrderUpdateRes = $db::table("bsa_order")
                    ->where('id', '=', $orderInfo['id'])
                    ->where('order_no', '=', $orderInfo['order_no'])
                    ->update($updateOrderStatus);
                if (!$localOrderUpdateRes) {
                    logs(json_encode([
                        'action' => 'localOrderUpdate',
                        'message' => $message,
                        'localOrderUpdateRes' => $localOrderUpdateRes,
                    ]), 'getOrderInfoFail');
                    $updateOrderStatus['order_status'] = 3;
                    $updateOrderStatus['last_use_time'] = time();
                    $updateOrderStatus['order_desc'] = "下单失败|" . "localOrderUpdateFail";
                    $orderModel->where('order_no', $orderInfo['order_no'])->update($updateOrderStatus);
                    return json(msg(-7, '', '下单繁忙'));
                }
                $returnData['phone'] = $orderInfo['account'];
                $returnData['amount'] = $orderInfo['amount'];
                $returnData['limitTime'] = (int)($limitTime);
                $returnData['imgUrl'] = $imgUrl;
                return json(msg(0, $returnData, 'order_success'));
            } else {
                if (time() > ($orderInfo['add_time'] + $orderShowTime)) {
                    return json(msg(-5, '', '订单超时，请重新下单'));
                }
                if ($orderInfo['order_status'] != 4) {
                    return json(msg(-5, '', '订单状态有误，请重新下单！'));
                }

                $returnData['phone'] = $orderInfo['account'];
                $returnData['amount'] = $orderInfo['amount'];
                $limitTime = (($orderInfo['add_time'] + $orderShowTime) - time());
                $returnData['limitTime'] = (int)($limitTime);
//                $imgUrl = "http://175.178.195.147:9090/upload/tengxun.jpg";

                $imgUrl = $request->domain() . "/upload/weixin517.jpg";
//                $imgUrl = urlencode($imgUrl);
                $returnData['imgUrl'] = $imgUrl;
                return json(msg(0, $returnData, "success"));
            }
        } catch (\Exception $exception) {
            logs(json_encode(['param' => $message,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()]), 'orderInfoException');
            return apiJsonReturn(-11, "orderInfo exception!" . $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['param' => $message,
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()]), 'orderInfoError');
            return json(msg(-22, '', 'orderInfo error!' . $error->getMessage()));
        }
    }

    //结果回调
    public function checkPhoneAmountNotify0076(Request $request)
    {
        session_write_close();
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);
        try {
            logs(json_encode([
                'param' => $message,
                'ip' => $request->ip(),
                'startTime' => date("Y-m-d H:i:s", time())
            ]), 'checkPhoneAmountNotify0076');
            $validate = new CheckPhoneAmountNotifyValidate();
            if (!$validate->check($message)) {
                return apiJsonReturn(-1, '', $validate->getError());
            }
            $orderModel = new OrderModel();
            $orderWhere['order_no'] = $message['order_no'];  //四方单号
            $orderWhere['account'] = $message['phone'];   //订单匹配手机号
            $orderInfo = $orderModel->where($orderWhere)->find();

            logs(json_encode([
                "time" => date("Y-m-d H:i:s", time()),
                'param' => $message
            ]), 'MatchOrderFailCheckPhoneAmountNotify0076');
            if (empty($orderInfo)) {
                return json(msg(-2, '', '无此订单！'));
            }
            if ($orderInfo['order_status'] == 1) {
                return json(msg(-3, '', '订单已支付！'));
            }
            $db = new Db();
            $checkResult = "第" . ($orderInfo['check_times'] + 1) . "次查询结果" . $message['amount'] . "(" . date("Y-m-d H:i:s") . ")";

            $nextCheckTime = time() + 300;  //设置第三次往后的查询时间
            $autoCheckOrderTime = SystemConfigModel::getAutoCheckOrderTime();
            if (is_int($autoCheckOrderTime)) {
                $nextCheckTime = time() + $autoCheckOrderTime;
            }
            if ($message['check_status'] != 1) {
                $updateCheckTimesRes = $db::table("bsa_order")->where($orderWhere)
                    ->update([
                        "check_status" => 0,  //查询结束
//                        "check_times" => $orderInfo['check_times'] + 1,
                        "next_check_time" => time() + 3,
                        "order_desc" => $checkResult,
                        "check_result" => $checkResult,
                    ]);
                logs(json_encode(['phone' => $orderInfo['account'],
                    "order_no" => $orderInfo['order_no'],
                    "notifyTime" => date("Y-m-d H:i:s", time()),
                    "updateCheckTimesRes" => $updateCheckTimesRes
                ]), '0076updateCheckPhoneAmountFail');
                return json(msg(1, '', '接收成功,更新成功1'));
            }
            //查询成功
            $orderWhere['order_no'] = $orderInfo['order_no'];
            $orderUpdate['check_times'] = $orderInfo['check_times'] + 1;
            $orderUpdate['check_status'] = 0;   //可在查询状态
            $orderUpdate['last_check_amount'] = $message['amount'];
            $orderUpdate['next_check_time'] = $nextCheckTime;
            $orderUpdate['check_result'] = $checkResult;
            $updateCheck = $db::table("bsa_order")->where($orderWhere)
                ->update($orderUpdate);
            if (!$updateCheck) {
                logs(json_encode(["time" => date("Y-m-d H:i:s", time()),
                    'action' => "checkNotifySuccess",
                    'message' => json_encode($message),
                    "updateCheck" => $updateCheck
                ]), '0076updateCheckPhoneAmountFail');
            }
            //1、支付到账
            if ($message['amount'] > ($orderInfo['end_check_amount'] - 10)) {
                //本地更新
                $orderHXModel = new OrderhexiaoModel();
                $updateOrderWhere['order_no'] = $orderInfo['order_no'];
                $updateOrderWhere['account'] = $orderInfo['account'];
                $orderHXData = $orderHXModel->where($orderWhere)->find();
                $localUpdateRes = $orderHXModel->orderLocalUpdate($orderInfo);
                logs(json_encode(["time" => date("Y-m-d H:i:s", time()),
                    'updateOrderWhere' => $updateOrderWhere,
                    'account' => $orderHXData['account'],
                    'localUpdateRes' => $localUpdateRes
                ]), '0076updateCheckPhoneAmountLocalUpdate');
                if (!isset($localUpdate['code']) || $localUpdate['code'] != 0) {
                    return json(msg(1, '', '接收成功,更新失败！'));
                }
                return json(msg(1, '', '接收成功,更新成功！'));
            }
            return json(msg(1, '', '接收成功,匹配失败！'));
        } catch (\Exception $exception) {
            logs(json_encode(['param' => $message,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()]), 'checkPhoneAmountNotify0076Exception');
            return json(msg(-11, '', '接收异常！'));
        } catch (\Error $error) {
            logs(json_encode(['param' => $message,
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()]), 'checkPhoneAmountNotify0076Error');
            return json(msg(-22, '', "接收错误！"));
        }
    }


}