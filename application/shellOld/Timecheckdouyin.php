<?php

namespace app\shellOld;

use app\common\model\OrderdouyinModel;
use app\common\model\OrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\SystemConfigModel;
use app\common\model\NotifylogModel;
use think\Db;

class Timecheckdouyin extends Command
{
    protected function configure()
    {
        $this->setName('Timecheckdouyin')->setDescription('定时处理（抖音）查单回调!');
    }

    /**
     * 定时处理回调日志 修改订单状态  @param Input $input
     * @param Output $output
     * @return int|null|void
     * @todo
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $limit = 10;
            $limitTime = SystemConfigModel::getPayLimitTime();
            $now = time();
            $lockLimit = $now - $limitTime;
            $orderdouyinModel = new OrderdouyinModel();
            $orderModel = new OrderModel();
//            $notifyLogModel = new NotifylogModel();
//            $notifyLogWhere['status'] = 2;
            $LimitStartTime = time() - $limitTime;
            $LimitEndTime = time() - 10;
            $where[] = ['add_time', 'between', [$lockLimit, $now - 20]];
            $where[] = ['order_status', '4'];
            //查询下单之前280s 到现在之前20s的等待付款订单
            //一直查询  --等待 回调 code!=0  改为status  =2
            $orderData = $orderdouyinModel->where('order_status', '<>', 1)
                ->where('notify_status', '=', 0)
                ->where('url_status', '=', 1)
                ->where('order_me', '<>', null)
                ->where('status', '=', 1)  //
//                ->where('last_use_time', '>', $LimitStartTime - 100)
//                ->where('last_use_time', '<', $LimitEndTime)
                ->select();
            logs(json_encode(['orderData' => $orderData, "sql" => Db::table("bsa_torder_douyin")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'Timecheckdouyin_log1');
            $db = new Db();
            $totalNum = count($orderData);
            if ($totalNum > 0) {
                foreach ($orderData as $k => $v) {
                    $getResParam['order_no'] = (string)$v['order_pay'];
                    $getResParam['order_url'] = $v['check_url'];
                    $getResParam['ck'] = $v['cookie'];
                    $getOrderStatus = $orderdouyinModel->checkOrderStatus($getResParam);
                    logs(json_encode(['orderData' => $v, "getOrderStatus" => $getOrderStatus, "time" => date("Y-m-d H:i:s", time())]), 'Timecheckdouyin_getOrderStatus_log');

                    $torderDouyinWhere['order_me'] = $v['order_me'];
                    $torderDouyinWhere['order_pay'] = $v['order_pay'];
                    if (isset($getOrderStatus['code']) && $getOrderStatus['code'] != 0) {
                        $prepareWhere['order_amount'] = $v['total_amount'];
                        $prepareWhere['status'] = 1;
                        $db::table("bsa_prepare_set")->where($prepareWhere)
                            ->update([
                                "can_use_num" => Db::raw("can_use_num-1")
                            ]);
                    }
                    //code==1  支付成功！
                    if (isset($getOrderStatus['code']) && $getOrderStatus['code'] == 1) {

                        $orderdouyinModelRes = $orderdouyinModel->orderDouYinNotifyToWriteOff($v);
                        if (!isset($orderdouyinModelRes['code']) || $orderdouyinModelRes['code'] != 0) {
                            logs(json_encode(['v' => $v, 'orderdouyinModelRes' => $orderdouyinModelRes, "sql" => Db::table("bsa_torder_douyin")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'orderdouyinModelRes_log2');
                        } else {
                            //支付成功
                            $orderWhere['order_pay'] = $v['order_pay'];
                            $orderWhere['order_me'] = $v['order_me'];
//                        $orderWhere['status'] = 2;

                            $order = Db::table("bsa_order")->where($orderWhere)->find();
                            logs(json_encode(['order' => $order, "sql" => Db::table("bsa_order")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'Timecheckdouyin_log');
                            if ($order) {
                                $res = $orderModel->orderNotify($order);
                                if ($res) {
                                    logs(json_encode(['order' => $order, 'orderNotifyRes' => $res, "sql" => Db::table("bsa_order")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'Timecheckdouyin_notify_log2');
                                }
                            }

                            $torderDouyinUpdate['order_status'] = 1;  //匹配订单支付成功
                            $torderDouyinUpdate['status'] = 2;   //推单改为最终结束状态
                            $torderDouyinUpdate['pay_time'] = time();
                            $torderDouyinUpdate['last_use_time'] = time();
                            $torderDouyinUpdate['success_amount'] = $v['total_amount'];
                            $torderDouyinUpdate['order_desc'] = "支付成功|待回调";
                            $updateTorderStatus = $orderdouyinModel->updateNotifyTorder($torderDouyinWhere, $torderDouyinUpdate);
                            if (!$updateTorderStatus) {
                                logs(json_encode(['torder_order_no' => $v['order_no'], 'updateTorderStatus' => $updateTorderStatus, "sql" => Db::table("bsa_torder_douyin")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'orderdouyinupdateNotifyTorder_log');
                            }
                        }

                    }
                    //支付链接不可用
                    if (isset($getOrderStatus['code']) && $getOrderStatus['code'] == 2) {
                        $torderDouyinUpdate['last_use_time'] = time();
                        $torderDouyinUpdate['status'] = 2;   //订单终结状态
                        $torderDouyinUpdate['url_status'] = 2;   //订单已失效 以停止查询
                        $torderDouyinUpdate['order_desc'] = "下单成功|匹配成功|订单失效";
                        $updateTorderStatus = $orderdouyinModel->updateNotifyTorder($torderDouyinWhere, $torderDouyinUpdate);
                        if ($updateTorderStatus) {
                            logs(json_encode(['torder_order_no' => $v['order_no'], 'updateTorderStatus' => $updateTorderStatus, "sql" => Db::table("bsa_torder_douyin")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'orderdouyinModelRes_log2');
                        }
                    }
//                    if (($v['add_time'] - time()) > 1000) {
//                        $torderDouyinWhere['order_me'] = $v['order_me'];
//                        $torderDouyinWhere['order_pay'] = $v['order_pay'];
//                        $torderDouyinUpdate['order_status'] = 2;  ///匹配订单支付超时
//                        $torderDouyinUpdate['status'] = 2;  ///推单改为最终结束状态 等待自动回调核销支付失败
//                        $torderDouyinUpdate['order_desc'] = "支付超时|准备回调核销失败";
//                        $orderdouyinModel->updateNotifyTorder($torderDouyinWhere, $torderDouyinUpdate);
//                        $orderdouyinModel->orderDouYinNotifyToWriteOff($v);
//                    }
                }

            }
            $output->writeln("Timecheckdouyin:订单总数" . $totalNum);
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'TimeouttorderNotify_exception');
            $output->writeln("Timecheckdouyin:订单总数" . $totalNum . "exception");
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'TimeouttorderNotify_error');
            $output->writeln("Timecheckdouyin:订单总数" . $totalNum . "error");
        }

    }
}