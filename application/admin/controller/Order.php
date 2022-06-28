<?php
/**
 * Created by PhpStorm.
 * User: bl
 * Date: 2020/12/20
 * Time: 12:57
 */

namespace app\admin\controller;

use app\admin\model\OrderModel;
use app\common\model\OrderdouyinModel;
use app\common\model\OrderhexiaoModel;
use app\common\model\OrderprepareModel;
use think\Db;

class Order extends Base
{
    //订单列表
    public function index()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
            $orderNo = input('param.order_no');
            $account = input('param.account');
            $startTime = input('param.start_time');
            $endTime = input('param.end_time');
            $searchParam = input('param.');
            $where = [];
            if (isset($searchParam['order_no']) && !empty($searchParam['order_no'])) {
                $where[] = ['order_no', 'like', $searchParam['order_no'] . '%'];
            }
            if (isset($searchParam['account']) && !empty($searchParam['account'])) {
                $where[] = ['account', '=', $searchParam['account']];
            }
            if (isset($searchParam['order_me']) && !empty($searchParam['order_me'])) {
                $where[] = ['order_me', 'like', $searchParam['order_me'] . '%'];
            }
            if (isset($searchParam['order_pay']) && !empty($searchParam['order_pay'])) {
                $where[] = ['order_pay', 'like', $searchParam['order_pay'] . '%'];
            }
//            if (!empty($startTime)) {
//                $where[] = ['add_time', '>', strtotime($startTime)];
//            }
//            if (!empty($endTime)) {
//                $where[] = ['add_time', '<', strtotime($endTime)];
//            }
            $studio = session("admin_role_id");
            if ($studio == '9') {
                $where[] = ['merchant_sign', '=', session("admin_user_name")];
            }
            $Order = new OrderModel();
            $list = $Order->getOrders($limit, $where);

//            logs(json_encode(['searchParam' => $searchParam, "where" => $where, "last_sql" => Db::table('bsa_order')->getLastSql()]), 'orderIndex_log');
            $data = $list['data'];
            foreach ($data as $key => $vo) {
                // 1、支付成功（下单成功）！2、支付失败（下单成功）！3、下单失败！4、等待支付（下单成功）！5、已手动回调。
                if (!empty($data[$key]['pay_time']) && $data[$key]['pay_time'] != 0) {
                    $data[$key]['pay_time'] = date('Y-m-d H:i:s', $vo['pay_time']);
                }
                $data[$key]['check_amount'] = $data[$key]['start_check_amount'] . "-" . $data[$key]['end_check_amount'] . "-" . $data[$key]['last_check_amount'];
                $data[$key]['order_limit_time'] = date('Y-m-d H:i:s', $data[$key]['order_limit_time']);
                $data[$key]['notify_time'] = date('Y-m-d H:i:s', $data[$key]['notify_time']);
                $data[$key]['add_time'] = date('Y-m-d H:i:s', $vo['add_time']);
                $data[$key]['click_time'] = date('Y-m-d H:i:s', $vo['click_time']);
                if ($data[$key]['order_status'] == 0) {
                    $data[$key]['order_status'] = '<button class="layui-btn layui-btn-primary layui-btn-xs">订单生成</button>';
                }
                if (!empty($data[$key]['order_status']) && $data[$key]['order_status'] == 7) {
                    $data[$key]['order_status'] = '<button class="layui-btn layui-btn-disabled layui-btn-xs">订单匹配</button>';
                }
                if (!empty($data[$key]['order_status']) && $data[$key]['order_status'] == 1) {
                    $data[$key]['order_status'] = '<button class="layui-btn layui-btn-success layui-btn-xs">付款成功</button>';
                }
                if (!empty($data[$key]['order_status']) && $data[$key]['order_status'] == 2) {
                    $data[$key]['order_status'] = '<button class="layui-btn layui-btn-danger layui-btn-xs">付款失败</button>';
                }
                if (!empty($data[$key]['order_status']) && $data[$key]['order_status'] == 3) {
                    $data[$key]['order_status'] = '<button class="layui-btn layui-btn-disabled layui-btn-xs">下单失败</button>';
                }
                if (!empty($data[$key]['order_status']) && $data[$key]['order_status'] == 4) {
                    $data[$key]['order_status'] = '<button class="layui-btn layui-btn-warm layui-btn-xs">等待支付</button>';
                }

                if (!empty($data[$key]['order_status']) && $data[$key]['order_status'] == 5) {
                    $data[$key]['order_status'] = '<button class="layui-btn label-important layui-btn-xs">手动回调</button>';
                }

//                $data[$key]['apiMerchantOrderDate'] = date('Y-m-d H:i:s', $data[$key]['apiMerchantOrderDate']);
//                $data[$key]['pay_time'] = date('Y-m-d H:i:s', $data[$key]['pay_time']);
            }
            $list['data'] = $data;
            if (0 == $list['code']) {
                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }
        return $this->fetch();
    }

    /**
     * 手动回调
     * @return void
     */
    public function notify()
    {
        try {
            if (request()->isAjax()) {
                $id = input('param.id');
                if (empty($id)) {
                    return json(modelReMsg(-1, '', '参数错误!'));
                }
                //查询订单
                $orderData = Db::table("bsa_order")->where("id", $id)->find();
                if (empty($orderData)) {
                    return json(modelReMsg(-2, '', '回调订单有误!'));
                }
//                $orderModel = new OrderModel();

//                $orderWhere['order_me'] = $order['order_me'];
//                $orderData = $orderModel->where($orderWhere)->find();
                if (empty($orderData) || $orderData['pay_status'] == 1) {
                    return json(modelReMsg(-3, '', '此订单不可回调!'));
                }
                if ($orderData['pay_status'] == 1) {
                    return json(modelReMsg(-4, '', '已支付订单不可回调!'));
                }
                $orderUpdate['pay_status'] = 1;                         //支付成功！
                $orderUpdate['order_status'] = 1;                       //支付成功！
                $orderUpdate['check_status'] = 2;                       //不可查询状态
                $orderUpdate['actual_amount'] = $orderData['amount'];   //支付金额
                $orderUpdate['pay_time'] = time();                      //支付 时间
                $orderUpdate['check_result'] = "手动回调" . session('admin_user_name');                      //支付 时间
                $orderUpdate['order_desc'] = "手动回调";                      //备注
                $updateRes = Db::table("bsa_order")->where("id", $id)
                    ->update($orderUpdate);
                if (!$updateRes) {
                    return json(modelReMsg(-5, '', '更新失败!-5'));
                }
                if (!empty($orderData['order_me'])) {
                    //更新预拉单表
                    $updatePrepareOrderWhere['order_me'] = $orderData['order_me'];
                    $updatePrepareOrder['pay_time'] = time();
                    $updatePrepareOrder['order_desc'] = "支付成功！";
                    $updatePrepareOrder['pay_status'] = 1;
                    $updatePrepareOrder['pay_amount'] = $orderData['amount'];
                    $updatePrepareOrderRes = Db::table("bsa_order_prepare")
                        ->where($updatePrepareOrderWhere)->update($updatePrepareOrder);
                    if(!$updatePrepareOrderRes){
                        return json(modelReMsg(-6, '', '更新预拉单失败!-6'));
                    }
                }
                return json(modelReMsg(1000, '', '回调成功'));
            } else {
                return json(modelReMsg(-99, '', '访问错误'));
            }
        } catch (\Exception $exception) {
            logs(json_encode(['id' => $id, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'order_notify_exception');
            return json(modelReMsg(-11, '', '通道异常'));
        } catch (\Error $error) {
            logs(json_encode(['id' => $id, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'order_notify_error');
            return json(modelReMsg(-22, '', '通道异常'));
        }
    }

    /**
     * @return \think\response\Json
     */
    public function check()
    {
        try {
            if (request()->isAjax()) {
                $id = input('param.id');
                if (empty($id)) {
                    return json(modelReMsg(-1, '', '参数错误!'));
                }

                //查询订单
                $order = Db::table("bsa_order")->where("id", $id)->find();
                if (empty($order)) {
                    return json(modelReMsg(-2, '', '回调订单有误!'));
                }

                if ($order['order_status'] == 1) {
                    return json(modelReMsg(-3, '', '此订单已支付!-1'));
                }

                if ($order['pay_status'] == 1) {
                    return json(modelReMsg(-4, '', '此订单已支付!-2'));
                }
                if (empty($order['order_me']) || empty($order['order_pay'])) {
                    return json(modelReMsg(-5, '', '此订单未匹配-4!'));
                }
                if ((time() - $order['add_time']) < 300) {
                    return json(modelReMsg(-6, '', '请于' . (300 - (time() - $order['add_time'])) . "秒后查询！"));
                }

                $getResParam['action'] = 'first';
                $getResParam['order_no'] = $order['order_no'];
                $getResParam['phone'] = $order['account'];

                $checkStartTime = date("Y-m-d H:i:s", time());
                $cookieWhere['account'] = $order['ck_account'];
                $cookie = Db::table("bsa_cookie")->where($cookieWhere)->find();
                if (empty($cookie) || !isset($cookie['cookie'])) {
                    return json(modelReMsg(-7, '', '!'));
                }
                //查单请求：ck，后台订单号，官方订单号，金额
                $getResParam['cookie'] = $cookie['cookie'];
                $getResParam['order_me'] = $order['order_me'];
                $getResParam['order_pay'] = $order['order_pay'];
                $getResParam['amount'] = $order['amount'];
                $orderPrepareModel = new OrderprepareModel();
                $checkStartTime = date("Y-m-d H:i:s", time());
                $checkOrderStatusRes = $orderPrepareModel->checkOrderStatus($getResParam);
                logs(json_encode([
                    "order_no" => $order['order_no'],
                    "order_pay" => $order['order_pay'],
                    "startTime" => $checkStartTime,
                    "endTime" => date("Y-m-d H:i:s", time()),
                    "getPhoneAmountRes" => $checkOrderStatusRes
                ]), 'adminCheckLog');
                $updateCheckWhere['order_me'] = $order['order_me'];
                $updateCheckData['check_status'] = 3;
                Db::table("bsa_order")->where($updateCheckWhere)
                    ->update($updateCheckData);


            } else {
                return json(modelReMsg(-99, '', '访问错误'));
            }
        } catch (\Exception $exception) {
            logs(json_encode(['id' => $id, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'order_notify_exception');
            return json(modelReMsg(-11, '', '通道异常'));
        } catch (\Error $error) {
            logs(json_encode(['id' => $id, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'order_notify_error');
            return json(modelReMsg(-22, '', '通道异常'));
        }
    }

    /**
     * 手动回调
     * @return void
     */
    public function notifyOld()
    {
//        $order_no = input('param.order_no');
        try {
            if (request()->isAjax()) {
                $id = input('param.id');
//                $param = input('post.');

                if (empty($id)) {
                    return reMsg(-1, '', "回调错误！");
                }

                //查询订单
                $order = Db::table("bsa_order")->where("id", $id)->find();

                if (empty($order)) {
                    return reMsg(-1, '', "回调订单有误");
                }
                $orderModel = new \app\common\model\OrderModel();
                logs(json_encode(['notify' => "notify", 'id' => input('param.id')]), 'notify_first');
                $orderdouyinModel = new OrderdouyinModel();
                $torderWhere['order_me'] = $order['order_me'];
                $v = $orderdouyinModel->where($torderWhere)->find();
                logs(json_encode(['order_id' => $id, 'v' => $v, "sql" => Db::table("bsa_torder_douyin")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'order_notify_log2');

                if (!empty($v)) {
                    $orderdouyinModelRes = $orderdouyinModel->orderDouYinNotifyToWriteOff($v, 2);
                    if (!isset($orderdouyinModelRes['code']) || $orderdouyinModelRes['code'] != 0) {
                        return reMsg(-2, '', "核销回调fail！");
                        logs(json_encode(['v' => $v['order_no'], 'orderdouyinModelRes' => $orderdouyinModelRes, "time" => date("Y-m-d H:i:s", time())]), 'notify_fail_log');
                    } else {
                        $torderDouyinWhere['order_me'] = $v['order_me'];
                        $torderDouyinWhere['order_pay'] = $v['order_pay'];
                        $torderDouyinUpdate['order_status'] = 1;  //匹配订单支付成功
                        $torderDouyinUpdate['status'] = 2;   //推单改为最终结束状态
                        $torderDouyinUpdate['pay_time'] = time();
                        $torderDouyinUpdate['last_use_time'] = time();
                        $torderDouyinUpdate['success_amount'] = $v['total_amount'];
                        $torderDouyinUpdate['order_desc'] = "支付成功|待回调";
                        $updateTorderStatus = $orderdouyinModel->updateNotifyTorder($torderDouyinWhere, $torderDouyinUpdate);
                        if ($updateTorderStatus) {
                            logs(json_encode(['torder_order_no' => $v['order_no'], 'updateTorderStatus' => $updateTorderStatus, "sql" => Db::table("bsa_torder_douyin")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'order_notify_towrite_off_log2');
                        }
                        $notifyRes = $orderModel->orderNotify($order, 2);
                        if ($notifyRes['code'] != 1000) {
                            return json(['code' => -2, 'msg' => $notifyRes['msg'], 'data' => []]);
                        }
                    }
                }
                return json(['code' => 1000, 'msg' => '回调成功', 'data' => []]);
            } else {
                return json('访问错误', 20009);
            }
        } catch (\Exception $exception) {
            logs(json_encode(['id' => $id, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'order_notify_exception');
            return json('20009', "通道异常" . $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['id' => $id, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'order_notify_error');
            return json('20099', "通道异常" . $error->getMessage());
        }

    }
}