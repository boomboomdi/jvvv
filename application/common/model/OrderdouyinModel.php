<?php

namespace app\common\model;

use app\admin\model\CookieModel;
use app\api\model\OrderLog;
use app\api\validate\OrderinfoValidate;
use think\Db;
use think\facade\Log;
use think\Model;

class OrderdouyinModel extends Model
{
    protected $table = 'bsa_torder_douyin';

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
     * 增加推单
     * @param $where
     * @param $addParam
     * @return array
     */
    public function addOrder($where, $addParam)
    {
        try {
            $has = $this->where($where)->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '此推单已经存在');
            }
            $this->insert($addParam);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, "", '添加推单成功');
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

    //

    /**
     * 获取可用付款抖音话单支付链接
     * @param $where
     * @param $orderMe
     * @return array|\think\response\Json
     */
    public function getUseTorderUrl($where, $orderMe = "")
    {
        $orderWhere['status'] = 1;  //0:未使用1:启用中2:已禁用
        $orderWhere['url_status'] = 1;  //0:未使用1:启用中2:已禁用
        $orderWhere['total_amount'] = $where['amount'];  //
        $prepareSetWhere['order_amount'] = $where['amount'];
        $prepareSetWhere['status'] = 1;

        $db = new Db();
        try {
            //有没有
            $info = $this
                ->where('total_amount', '=', $where['amount'])
                ->where('url_status', '=', 1)
                ->where('order_me', '=', null)
                ->where('status', '=', 1)
                ->where('get_url_time', '>', time() - 180)
                ->where('get_url_time', '<', time())
                ->order("get_url_time asc")
                ->find();
            logs(json_encode(['where' => $where, 'info' => $info]), 'getUseTorderUrl_log');

            if (!empty($info)) {
                $prepare = $db::table("bsa_prepare_set")->where($prepareSetWhere)->count();
                $db::table("bsa_prepare_set")->where($prepareSetWhere)->update(
                    ['can_use_num' => ($prepare['can_use_num'] - 1)]
                );
                if (!empty($orderMe)) {
                    $updateTorderWhere['order_no'] = $info['order_no'];
                    $updateTorder['order_me'] = $orderMe;
//                    $updateTorder['url_status'] = 2;
                    $updateTorder['last_use_time'] = time();
//                    $updateTorder['last_use_time'] = time();
                    $updateTorder['order_desc'] = "匹配订单成功|" . date("Y-m-d H:i:s", time()) . "|" . $orderMe;
                    //绑定推单 通道订单号
                    $bindTorder = $db::table('bsa_torder_douyin')->where($updateTorderWhere)->update($updateTorder);
                    if (!$bindTorder) {

                        return apiJsonReturn(-1, "绑定订单失败", "");
                    }
                }
                $db::table('bsa_torder_douyin')->where($orderWhere)
                    ->update([
                        'status' => 1,
//                        'url_status' => 2,
                        'last_use_time' => time(),
                        'order_desc' => "匹配订单成功|" . date("Y-m-d H:i:s", time()) . "|" . $orderMe
                    ]);
                logs(json_encode(['info' => $info, 'bindOrderMe' => $orderMe]), 'torderBindTorder');
                return modelReMsg(0, $info, '匹配订单成功');
            }
            //或者：请求获取话单支付链接  || 范围为为匹配订单
            return modelReMsg(-2, '', '未匹配到订单');
        } catch (\Exception $exception) {
            logs(json_encode(['where' => $where, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'getUseTorderUrl_exception');
            return modelReMsg(-1, '', $exception->getMessage());
        } catch (\Exception $e) {
            logs(json_encode(['where' => $where, 'file' => $e->getFile(), 'line' => $e->getLine(), 'errorMessage' => $e->getMessage()]), 'getUseTorderUrl_error');
            return json(msg('-11', '', 'create order Exception!' . $e->getMessage() . $e->getFile() . $e->getLine()));
        }

    }

    /**
     * 获取可用付款抖音话单支付链接
     * @param $where
     * @return array
     */
    public function getUseTorder($amount, $cookie)
    {
        $returnCode = 3;
        $msg = "失败！";
        $db = new Db();
        $db::startTrans();
        try {
//            ->where('status', '=', 0)
//                ->where('url_status', '=', 0)
//                ->where('add_time', '>', time() - 600)
            //有没有
            $limit_time = time() - 600;
            // 可以预拉十分钟之内的未预拉（url_status）
            //  且可用（status=0）  预拉成功 status = 1
            //的推单的推单 （符合金额total_amount）
            $torder = $this
                ->where('status', '=', 0)
                ->where('total_amount', '=', $amount)
                ->where('url_status', '=', 0)
                ->where('add_time', '>', $limit_time)
                ->where('weight', '=', 0)
                ->order("t_id  asc")
//                ->lock(true)
                ->find();
            logs(json_encode(['startTime' => date("Y-m-d H:i:s", time()), "torder" => $torder]), 'lock_tOrder_log');

            if ($torder) {
                $updateWeight['weight'] = 1;
                $updateWeightRes = $this->where('t_id', '=', $torder['t_id'])->update($updateWeight);
                logs(json_encode(['updateWeightTime' => date("Y-m-d H:i:s", time()), "updateWeightRes" => $updateWeightRes]), 'lock_tOrder_log');

                if (!$updateWeightRes) {
                    $db::rollback();
                    return modelReMsg(-5, '', '没有可下单推单');
                }
                $info = $this
                    ->where('t_id', '=', $torder['t_id'])->lock(true)->find();
                logs(json_encode(['lockTime' => date("Y-m-d H:i:s", time()), "info" => $info]), 'lock_tOrder_log');

                if (!$info) {
                    $db::rollback();
                    return modelReMsg(-2, '', '没有可下单推单');
                }

                logs(json_encode(['info' => $info]), 'lock_torder_result');
                if (!empty($info['order_pay']) || !empty($info['pay_url']) || !empty($info['check_url'])) {
                    $db::rollback();
                    return modelReMsg(-2, '', '没有可下单推单');
                }
                $updateWhere['t_id'] = $info['t_id'];
                $updateWhere['order_no'] = $info['order_no'];
                $update['last_use_time'] = time();
                $update['use_times'] = $info['use_times'] + 1;
                $update['cookie'] = $cookie['cookie'];
                //获取话单
                $createParam['ck'] = $cookie['cookie'];   //COOKIE  bsa_cookie
                $createParam['account'] = $info['account'];   //account  bsa_torder_douyin
                $createParam['amount'] = $info['total_amount'];   //total_amount  bsa_torder_douyin
                $createParam['order_no'] = (string)$info['order_no'];   //order_no  bsa_torder_douyin
                $postStartDate = date("Y-m-d H:i:s", time());

                $notifyResult = curlPostJson("http://127.0.0.1:23946/createOrder", $createParam);
                $notifyResult = json_decode($notifyResult, true);
//                {"msg":"下单成功","order_url":"https://tp-pay.snssdk.com/cashdesk/?app_id=800095745677&encodeType=base64&merchant_id=1200009574&out_order_no=10000017080988975653278733&return_scheme=&return_url=aHR0cHM6Ly93d3cuZG91eWluLmNvbS9wYXk=&sign=976358abfe82f2e06d576dc22aa2dd05&sign_type=MD5&switch=00&timestamp=1648671358&total_amount=5500&trade_no=SP2022033104154330075991127887&trade_type=H5&uid=8b58441a628f2cee4bd6f629ccd9012a","amount":"55","ali_url":"https://mclient.alipay.com/cashier/mobilepay.htm?alipay_exterface_invoke_assign_target=invoke_139e2972e1746412b2bc190190e6ee54&alipay_exterface_invoke_assign_sign=_c_d_j6i_r_hoo%2Bue_vw_hdk_uh_m_cn%2B_t2_e_mi_o_vs_orkqhh_m_o_sjk_i6_yo8gwl9_hy_q%3D%3D","code":0,"order_id":"10000017080988975653278733"}
                logs(json_encode(['createParam' => $createParam['order_no'], "startTime" => $postStartDate, 'postEndDate' => date("Y-m-d H:i:s", time()), 'notifyResult' => $notifyResult]), 'PrepareordergetUseTorder_result');

                //更新预拉时间
                if (isset($notifyResult['code']) && $notifyResult['code'] == 0) {
//                    $updateRes = $db::table('bsa_torder_douyin')->where($updateWhere)->find();
//                    if (isset($info['order_pay']) && !empty($info['order_pay'])) {
                    $returnCode = 0;
                    $msg = "下单成功！";
                    //下单成功！
                    $update['pay_url'] = $notifyResult['ali_url'];
                    $update['check_url'] = $notifyResult['order_url'];
                    $update['order_pay'] = $notifyResult['order_id'];
                    $update['get_url_time'] = time();
                    $update['status'] = 1;
                    $update['url_status'] = 1;
                    $update['order_status'] = 0;
                    $updateTorder = $this->where($updateWhere)->update($update);
                    if ($updateTorder) {
                        logs(json_encode(['createParam' => $createParam, 'notifyResult' => $notifyResult]), 'PrepareordergetUseTorder_result');
                    }
//                    }
                }
                if (isset($notifyResult['code']) && $notifyResult['code'] == 1) {

                    $returnCode = 1;
                    $msg = "下单失败，ck失效！";
                    //下单失败！
                }
                if (isset($notifyResult['code']) && $notifyResult['code'] == 4) {
                    $returnCode = 4;
                    $msg = "下单失败，账号无法预拉！";
                    //下单失败！
                    //下单成功！
                    $update['status'] = 2;  //推单使用状态终结
                    $update['url_status'] = 1;  //已经请求
//                    $update['get_url_time'] = time();
                    $update['order_status'] = 0;   //等待付款 --等待通知核销
                    $update['order_desc'] = "拉单失败|" . $notifyResult['msg'];
                    $this->where($updateWhere)->update($update);

                }
                $db::commit();
                $updateWeight['weight'] = 0;
                $this->where('t_id', '=', $torder['t_id'])->update($updateWeight);
                return modelReMsg($returnCode, $info, $msg);
            }
            logs(json_encode(['account' => $cookie['account'], 'info' => $torder]), 'getUseTordera_fitst_log');

            $db::commit();
            //没有可下单推单！
            return modelReMsg(-2, '', '没有可下单推单');
        } catch (\Exception $exception) {
            $db::rollback();
            logs(json_encode(['where' => $amount, 'cookie' => $cookie, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'getUseTorder_exception');
            return modelReMsg(-1, '', $exception->getMessage());
        } catch (\Exception $e) {
            $db::rollback();
            logs(json_encode(['where' => $amount, 'cookie' => $cookie, 'file' => $e->getFile(), 'line' => $e->getLine(), 'errorMessage' => $e->getMessage()]), 'getUseTorder_error');
            return json(msg('-11', '', 'create order Exception!' . $e->getMessage() . $e->getFile() . $e->getLine()));
        }

    }

    /**
     * 获取可用付款抖音话单支付链接
     * @param $where
     * @return array
     */
    public function getUseTOrderNew($torder, $cookie)
    {
        $returnCode = 3;
        $msg = "失败！";
        $db = new Db();
        $db::startTrans();
        try {
            $info = $this->where('t_id', '=', $torder['t_id'])->lock(true)->find();
            logs(json_encode(['startTime' => date("Y-m-d H:i:s", time()), "info" => $info]), 'getUseTOrderNew_log');
            if (!$info) {
                $db::rollback();
                return modelReMsg(-1, '', '此推单暂不可预拉！');
            }
            if (empty($info)) {
                $db::rollback();
                return modelReMsg(-1, '', '此推单暂不可预拉:2！');
            }
//            if (isset($info['use_time']) && $info['use_time'] == 0) {
//
//            }
//            if (!empty($info['order_pay']) || !empty($info['pay_url']) || !empty($info['check_url'])) {
//                $db::rollback();
//                return modelReMsg(-2, '', '核销单已更新！');
//            }

//            $update['last_use_time'] = time();
//            $update['use_times'] = $info['use_times'] + 1;
//            $update['cookie'] = $cookie['cookie'];
            $update['weight'] = 1;
            $update['last_use_time'] = time();
            $update['ck_account'] = $cookie['account'];
            $update['cookie'] = $cookie['cookie'];
            $updateRes = $this->where('t_id', '=', $info['t_id'])->update($update);
            if (!$updateRes) {
                $db::rollback();
                logs(json_encode(['order_no' => $info['order_no'], '$update' => $update, 'updateRes' => $updateRes]), 'getUseTOrderNew_log');
                return modelReMsg(-2, '', 'updateRes_fail');

            }
            //获取话单
            $createParam['ck'] = $cookie['cookie'];   //COOKIE  bsa_cookie
            $createParam['account'] = $info['account'];   //account  bsa_torder_douyin
            $createParam['ck_account'] = $cookie['account'];   //account  bsa_cookie
            $createParam['amount'] = $info['total_amount'];   //total_amount  bsa_torder_douyin
            $createParam['order_no'] = (string)$info['order_no'];   //order_no  bsa_torder_douyin
            $postStartDate = date("Y-m-d H:i:s", time());

            $notifyResult = curlPostJson("http://127.0.0.1:23946/createOrder", $createParam);
//            $notifyResult = json_decode($notifyResult, true);
//                {"msg":"下单成功","order_url":"https://tp-pay.snssdk.com/cashdesk/?app_id=800095745677&encodeType=base64&merchant_id=1200009574&out_order_no=10000017080988975653278733&return_scheme=&return_url=aHR0cHM6Ly93d3cuZG91eWluLmNvbS9wYXk=&sign=976358abfe82f2e06d576dc22aa2dd05&sign_type=MD5&switch=00&timestamp=1648671358&total_amount=5500&trade_no=SP2022033104154330075991127887&trade_type=H5&uid=8b58441a628f2cee4bd6f629ccd9012a","amount":"55","ali_url":"https://mclient.alipay.com/cashier/mobilepay.htm?alipay_exterface_invoke_assign_target=invoke_139e2972e1746412b2bc190190e6ee54&alipay_exterface_invoke_assign_sign=_c_d_j6i_r_hoo%2Bue_vw_hdk_uh_m_cn%2B_t2_e_mi_o_vs_orkqhh_m_o_sjk_i6_yo8gwl9_hy_q%3D%3D","code":0,"order_id":"10000017080988975653278733"}

            logs(json_encode(['createParam' => $createParam, "startTime" => $postStartDate, 'postEndDate' => date("Y-m-d H:i:s", time()), 'notifyResult' => $notifyResult]), 'getUseTOrderNew_log');
            if ($notifyResult == "success") {
                $db::commit();
                return modelReMsg(0, $info, $msg);
            } else {
                $db::rollback();
            }
            return modelReMsg(-2, $info, "getUseTOrderNew_res预拉失败");

        } catch (\Exception $exception) {
            $db::rollback();
            logs(json_encode(['where' => $torder, 'cookie' => $cookie, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'getUseTorder_exception');
            return modelReMsg(-11, '', $exception->getMessage());
        } catch (\Exception $e) {
            $db::rollback();
            logs(json_encode(['where' => $torder, 'cookie' => $cookie, 'file' => $e->getFile(), 'line' => $e->getLine(), 'errorMessage' => $e->getMessage()]), 'getUseTorder_error');
            return json(msg(-22, '', 'create order Exception!' . $e->getMessage() . $e->getFile() . $e->getLine()));
        }

    }


    /**
     * 获取可用付款抖音话单支付链接
     * @param $where
     * @return array
     */
    public function getUseTOrderOLD2($torder, $cookie)
    {
        $returnCode = 3;
        $msg = "失败！";
        $db = new Db();
        $db::startTrans();
        try {
            $info = $this->where('t_id', '=', $torder['t_id'])->lock(true)->find();
            logs(json_encode(['startTime' => date("Y-m-d H:i:s", time()), "info" => $info]), 'getUseTOrderNew_log');
            if (!$info) {
                $db::rollback();
                return modelReMsg(-1, '', '此推单暂不可预拉！');
            }

            $updateWeight['weight'] = 1;
            $this->where('t_id', '=', $torder['t_id'])->update($updateWeight);
            if (!empty($info['order_pay']) || !empty($info['pay_url']) || !empty($info['check_url'])) {
                $db::rollback();
                return modelReMsg(-2, '', '核销单已更新！');
            }
            $updateWhere['t_id'] = $info['t_id'];
//            $updateWhere['order_no'] = $info['order_no'];
            $update['last_use_time'] = time();
            $update['use_times'] = $info['use_times'] + 1;
            $update['cookie'] = $cookie['cookie'];
            //获取话单
            $createParam['ck'] = $cookie['cookie'];   //COOKIE  bsa_cookie
            $createParam['account'] = $info['account'];   //account  bsa_torder_douyin
            $createParam['amount'] = $info['total_amount'];   //total_amount  bsa_torder_douyin
            $createParam['order_no'] = $info['order_no'];   //order_no  bsa_torder_douyin
            $postStartDate = date("Y-m-d H:i:s", time());

            $notifyResult = curlPostJson("http://127.0.0.1:23946/createOrder", $createParam);
            $notifyResult = json_decode($notifyResult, true);
//                {"msg":"下单成功","order_url":"https://tp-pay.snssdk.com/cashdesk/?app_id=800095745677&encodeType=base64&merchant_id=1200009574&out_order_no=10000017080988975653278733&return_scheme=&return_url=aHR0cHM6Ly93d3cuZG91eWluLmNvbS9wYXk=&sign=976358abfe82f2e06d576dc22aa2dd05&sign_type=MD5&switch=00&timestamp=1648671358&total_amount=5500&trade_no=SP2022033104154330075991127887&trade_type=H5&uid=8b58441a628f2cee4bd6f629ccd9012a","amount":"55","ali_url":"https://mclient.alipay.com/cashier/mobilepay.htm?alipay_exterface_invoke_assign_target=invoke_139e2972e1746412b2bc190190e6ee54&alipay_exterface_invoke_assign_sign=_c_d_j6i_r_hoo%2Bue_vw_hdk_uh_m_cn%2B_t2_e_mi_o_vs_orkqhh_m_o_sjk_i6_yo8gwl9_hy_q%3D%3D","code":0,"order_id":"10000017080988975653278733"}
            logs(json_encode(['createParam' => $createParam['order_no'], "startTime" => $postStartDate, 'postEndDate' => date("Y-m-d H:i:s", time()), 'notifyResult' => $notifyResult]), 'getUseTOrderNew_log');

            //预拉成功
            if (isset($notifyResult['code']) && $notifyResult['code'] == 0) {
                $returnCode = 0;
                $msg = "下单成功！";
                //下单成功！
                $update['pay_url'] = $notifyResult['ali_url'];
                $update['check_url'] = $notifyResult['order_url'];
                $update['order_pay'] = (string)$notifyResult['order_id'];
                $update['get_url_time'] = time();
                $update['status'] = 1;
                $update['url_status'] = 1;
                $update['order_status'] = 0;
                $this->where($updateWhere)->update($update);

            }
            if (isset($notifyResult['code']) && $notifyResult['code'] == 1) {

                $returnCode = 1;
                $msg = "下单失败，ck失效！";
                //下单失败！
            }
            if (isset($notifyResult['code']) && $notifyResult['code'] == 4) {
                $returnCode = 4;
                $msg = "下单失败，账号无法预拉！";
                //下单失败！
                $update['status'] = 2;  //推单使用状态终结
                $update['url_status'] = 1;  //已经请求
//                    $update['get_url_time'] = time();
                $update['order_status'] = 0;   //等待付款 --等待通知核销
                $update['order_desc'] = "拉单失败|" . $notifyResult['msg'];
                $this->where($updateWhere)->update($update);
            }
            $updateWeight['weight'] = 0;
            $this->where('t_id', '=', $torder['t_id'])->update($updateWeight);
            $db::commit();
            return modelReMsg($returnCode, $info, $msg);

        } catch (\Exception $exception) {
            $db::rollback();
            logs(json_encode(['where' => $torder, 'cookie' => $cookie, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'getUseTorder_exception');
            return modelReMsg(-1, '', $exception->getMessage());
        } catch (\Exception $e) {
            $db::rollback();
            logs(json_encode(['where' => $torder, 'cookie' => $cookie, 'file' => $e->getFile(), 'line' => $e->getLine(), 'errorMessage' => $e->getMessage()]), 'getUseTorder_error');
            return json(msg('-11', '', 'create order Exception!' . $e->getMessage() . $e->getFile() . $e->getLine()));
        }

    }

    /**
     * @return void
     */
    public function getTorderInfo($where)
    {
        try {
            $info = $this->where($where)->find();
            if (empty($info)) {
                return modelReMsg(-2, '', '未匹配到订单');
            }
        } catch (\Exception $e) {
            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $info, '匹配订单成功');
    }

    public function checkOrderStatus($orderData)
    {
//        Log::write("checkOrderStatus:/n/ start: /n" . json_encode($orderData), "info");
        try {
            $url = "http://127.0.0.1:23946/queryResult";
            $getOrderStatus = curlPostJson($url, $orderData);

//            Log::log('1', "checkOrderStatus order " . json_encode($getOrderStatus));
            return json_decode($getOrderStatus, true);

        } catch (\Exception $exception) {

            Log::write("checkOrderStatus:/n  Exception: /n" . $exception->getMessage(), "info");
            return modelReMsg(-2, '', $exception->getMessage());
        } catch (\Error $error) {
            Db::rollback();
            Log::write("checkOrderStatus:/n  Error: /n" . $error->getMessage(), "info");
            return modelReMsg(-3, '', $error->getMessage());
        }
    }

    /**
     * 订单回调 通道/手动回调 总入口
     * @param $orderData
     * @param $status 1、自动回调 2、手动回调
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
            $studioWhere['studio'] = $orderData['studio'];
            Db::table('bsa_studio')->where($studioWhere)->find();
            Db::table('bsa_studio')->where($studioWhere)
                ->update([
                    "amount" => Db::raw("amount") + $orderData['amount'],
                    "blance" => Db::raw("blance") - $orderData['blance'],
                    "breeze_amount" => Db::raw("breeze_amount") - $orderData['amount']
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

    /**
     * 根据金额生成 对核销淡定预拉单
     * @return void
     */
    public function createOrder($v, $prepareNum = 1)
    {
        $successNum = 0;
        $errorNum = 0;
        $msg = "";
        try {
            $amount = $v['order_amount'];
            //获取CK
            $cookieModel = new CookieModel();
            $cookieWhere["status"] = 1;
            $getCookie = $cookieModel->where($cookieWhere)->order("last_use_time desc")->find();
            if (empty($getCookie)) {
                return modelReMsg('-9', $successNum, "无可用ck");
            }
            $msg = "预产单失败！";

            //可以预拉十分钟之内的未预拉（url_status）
            //且可用（status=0）  预拉成功 status = 1
            //的推单的推单 （符合金额total_amount）

            $limit_time = time() - 600;
            $torderData = $this
                ->where('status', '=', 0)
                ->where('total_amount', '=', $amount)
                ->where('url_status', '=', 0)
                ->where('prepare_limit_time', '>', time())   //限制时间
//                ->where('add_time', '>', $limit_time)
                ->where('weight', '=', 0)
                ->order("add_time  asc")
                ->limit($prepareNum)
                ->select();
            logs(json_encode(["action" > 'createOrder', 'startTime' => date("Y-m-d H:i:s", time()), "info" => $torderData, "lastSql" => $this->getLastSql()]), 'getUseTOrderNew_log');

            if (empty($torderData) || count($torderData) == 0) {
                return modelReMsg(-8, $successNum, "无可用推单！");
            }

            foreach ($torderData as $key => $val) {
                if (!empty($val) && isset($val['order_no'])) {

                    $getCookieRes = $cookieModel->getUseCookie();
                    if ($getCookieRes['code'] != 0) {
                        $msg = $getCookieRes['msg'];
                        break;
                    }
                    $getUesTOrderRes = $this->getUseTOrderNew($val, $getCookieRes['data']);
                    if ($getUesTOrderRes['code'] == 0) {  //下单成功
                        $msg = "|" . $amount . "预产成功！" . $successNum++ . "个";
                        $successNum++;
                    } else {
                        $msg = "|" . $amount . "预产失败！" . $successNum++ . "个";
                        $successNum++;
                    }
                }
            }

            return modelReMsg(0, $successNum, $msg);
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'PrepareorderCreateOrderException_log');
            return modelReMsg('-11', $successNum, "预产单失败" . $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'PrepareorderCreateOrderError_log');
            return modelReMsg('-22', $successNum, "预产单失败" . $error->getMessage());
        }
    }

    /**
     * {
     * "write_off_sign":"lisi", //string
     * "order_no":"e10adc3949ba59abbe56e057f20f883e",  //推单单号|string
     * "account":"13388888888",      //充值账号|string
     * "total_amount":"1.00",        //金额|float保留两位
     * "success_amount":"1.00",        //充值金额|float保留两位
     * "pay_time":"Y-m-d H:i:s",    //支付时间|2022-4-1 12:21:12
     * "sign":"" |string
     * }
     * 回调核销后台
     * @param $tOrderData
     * @return void
     */
    public function orderDouYinNotifyToWriteOff($tOrderData, $orderStatus = 1)
    {
        $orderWhere['order_no'] = $tOrderData['order_no'];
        $notifyParam['write_off_sign'] = $tOrderData['write_off_sign'];
        $db = new Db();
        $db::startTrans();
        try {
            $db = new Db();
            $notifyParam['write_off_sign'] = $tOrderData['write_off_sign'];
            $writeWhere['write_off_sign'] = $notifyParam['write_off_sign'];
            $token = $db::table("bsa_write_off")->where($writeWhere)->find();
            $tOrderDataWhere['order_no'] = (string)$tOrderData['order_no'];
            $tOrderData = $db::table("bsa_torder_douyin")->where($tOrderDataWhere)->lock(true)->find();
            $notifyParam['order_no'] = $tOrderData['order_no'];
            $notifyParam['account'] = $tOrderData['account'];
            $notifyParam['total_amount'] = $tOrderData['total_amount'];
            $notifyParam['success_amount'] = $tOrderData['success_amount'];
            $notifyParam['order_status'] = $tOrderData['order_status'];
            if ($tOrderData['pay_time'] != 0) {
                $notifyParam['time'] = date("Y-m-d H:i:s", $tOrderData['pay_time']);
            } else {
                $notifyParam['time'] = date("Y-m-d H:i:s", time());
            }
            $md5Sting = $notifyParam['write_off_sign'] . $notifyParam['order_no'] . $notifyParam['account'] . $notifyParam['order_status'] . $notifyParam['total_amount'] . $notifyParam['success_amount'] . $notifyParam['time'] . $token['token'];
            $notifyParam['sign'] = md5($md5Sting);

            //回调核销  已经收到款项
            $notifyResult = curlPostJson($tOrderData['notify_url'], $notifyParam);
            logs(json_encode(['notify_url' => $tOrderData['notify_url'], 'notifyParam' => $notifyParam, "paramAddTime" => date("Y-m-d H:i:s", $tOrderData['add_time']), "notifyResult" => $notifyResult]), 'curlPostJsonToWriteOff_log');

//            Log::log('orderDouYinNotifyToWriteOff!', $notifyParam, $notifyResult);
//            $result = json_decode($notifyResult, true);

            $match_order_desc = "匹配失败|";
            if (!empty($tOrderData['order_me'])) {
                $match_order_desc = "匹配成功|";
            }
            $add_order_desc = "|支付失败";

            $successAmount = $tOrderData['success_amount'];
            if ($tOrderData['order_status'] == 1) {
                $successAmount = $tOrderData['total_amount'];
                $add_order_desc = "|支付成功";
            }

            $do_order_desc = "|手动核销回调|";
            if ($orderStatus == 1) {
                $do_order_desc = "|自动核销回调|";
            }
            $order_desc = $match_order_desc . $add_order_desc . $do_order_desc . $notifyResult;
            //通知结果不为success
            if ($notifyResult != "success") {
                $db::rollback();
                $db::table('bsa_torder_douyin')->where($orderWhere)
                    ->update([
                        'order_desc' => "fail:" . $order_desc
                    ]);
                logs(json_encode(['notify_url' => $tOrderData['notify_url'], 'notifyParam' => $notifyParam, "paramAddTime" => date("Y-m-d H:i:s", $tOrderData['add_time']), "notifyResult" => $notifyResult]), 'curlPostJsonToWriteOffNoSuccess_log');
                return modelReMsg(-2, "", json_encode($notifyResult));

            } else {
                $db::table('bsa_torder_douyin')->where($orderWhere)
                    ->update([
                        'status' => 2,
                        'url_status' => 2,
                        'success_amount' => $successAmount,
                        'notify_status' => 1,
                        'notify_time' => time(),
                        'order_desc' => $order_desc
                    ]);
                $db::commit();
//                logs(json_encode(['notify_url' => $tOrderData['notify_url'], 'notifyParam' => $notifyParam, "paramAddTime" => date("Y-m-d H:i:s", $tOrderData['add_time']), "notifyResult" => $notifyResult]), 'curlPostJsonToWriteOffSuccess_log');
            }
            return modelReMsg(0, "", json_encode($notifyResult));
        } catch (\Exception $exception) {
            $db::rollback();
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'orderDouYinNotifyToWriteOffException_log');
            return modelReMsg('-11', "", "回调失败" . $exception->getMessage());
        } catch (\Error $error) {
            $db::rollback();
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'orderDouYinNotifyToWriteOffError_log');
            return modelReMsg('-22', "", "回调失败" . $error->getMessage());

        }
    }

    /**
     * 推单成功修改状态
     * @param $where
     * @param $torderDouyinUpdate
     * @return array
     */
    public function updateNotifyTorder($where, $torderDouyinUpdate)
    {
        try {
            $res = $this->where($where)->update($torderDouyinUpdate);
            logs(json_encode(['where' => $where, 'torderDouyinUpdate' => $torderDouyinUpdate, 'res' => $res]), 'TimecheckdouyinUpdateNotifyTOrder_log');

            if (!$res) {
                logs(json_encode(['where' => $where, 'torderDouyinUpdate' => $torderDouyinUpdate, 'res' => $res]), 'TimecheckdouyinUpdateNotifyTOrderFail_log');
                return modelReMsg('-1', "", "更新失败");
            }
            logs(json_encode(['where' => $where, 'torderDouyinUpdate' => $torderDouyinUpdate, 'res' => $res]), 'TimecheckdouyinUpdateNotifyTOrder_log');

            return modelReMsg('0', "", "更新成功");

        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'TimecheckdouyinUpdateNotifyTorderException_log');
            return modelReMsg('20009', "", "商户回调异常" . $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'TimecheckdouyinUpdateNotifyTorderError_log');
            return modelReMsg('20099', "", "商户回调错误" . $error->getMessage());
        }
    }
}