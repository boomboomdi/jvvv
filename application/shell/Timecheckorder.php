<?php

namespace app\shell;

use app\common\model\OrderModel;
use app\common\model\OrderprepareModel;
use app\common\Redis;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\SystemConfigModel;
use app\common\model\NotifylogModel;
use think\Db;

class Timecheckorder extends Command
{
    protected function configure()
    {
        $this->setName('Timecheckorder')->setDescription('定时查询订单状态!');
    }

    /**
     * 定时查询话单余额
     * @param Output $output
     * @return int|null|void
     * @todo
     */
    protected function execute(Input $input, Output $output)
    {

        $db = new Db();
        try {
            $orderPrepareModel = new OrderprepareModel();
            $orderModel = new OrderModel();
            //一直查询  --等待 回调 code!=0  改为status  =2
            $orderData = $orderModel
                ->where('order_status', '=', 4)
                ->where('next_check_time', '<', time())
                ->where('order_pay', '<>', null)
                ->where('order_me', '<>', null)
                ->where('check_times', '<', 20)
                ->select();

            $totalNum = count($orderData);
            if ($totalNum > 0) {
                foreach ($orderData as $k => $v) {
                    $redis = new Redis(['index' => 1]);
                    $PrepareOrderKey = "CheckOrderStatus" . $v['order_me'];
                    $setRes = $redis->setnx($PrepareOrderKey, $v['order_me'], 20);
                    if ($setRes) {
                        //修改订单查询状态为查询中
                        $updateCheckData['check_status'] = 1;
                        $updateCheckData['last_check_time'] = time();
                        $updateCheckWhere['order_me'] = $v['order_me'];
                        $db::table("bsa_order")->where($updateCheckWhere)
                            ->update($updateCheckData);
                        //修改订单查询状态为查询中 end

                        $cookieWhere['account'] = $v['ck_account'];
                        $cookie = Db::table("bsa_cookie")->where($cookieWhere)->find();
                        if (!empty($cookie) && isset($cookie['cookie'])) {
                            //查单请求：ck，后台订单号，官方订单号，金额
                            $getResParam['cookie'] = $cookie['cookie'];
                            $getResParam['order_me'] = $v['order_me'];
                            $getResParam['order_pay'] = $v['order_pay'];
                            $getResParam['amount'] = $v['amount'];
                            $checkStartTime = date("Y-m-d H:i:s", time());
                            $checkOrderStatusRes = $orderPrepareModel->checkOrderStatus($getResParam);
                            if (!isset($checkOrderStatusRes['code']) || $checkOrderStatusRes['code'] != 0) {
                                $updateCheckWhere['order_no'] = $v['order_no'];
                                $updateCheckData['check_status'] = 3;
                                $db::table("bsa_order")->where($updateCheckWhere)
                                    ->update($updateCheckData);
                            }
                            logs(json_encode([
                                "order_no" => $v['order_no'],
                                "order_pay" => $v['order_pay'],
                                "startTime" => $checkStartTime,
                                "endTime" => date("Y-m-d H:i:s", time()),
                                "getPhoneAmountRes" => $checkOrderStatusRes
                            ]), 'Timecheckorder');
                        } else {
                            $redis->delete($PrepareOrderKey);
                        }
                    }
                }

            }
            $output->writeln("Timecheckorder:订单总数" . $totalNum);
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'Timecheckorder_exception');
            $output->writeln("Timecheckorder:exception");
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'Timecheckorder_error');
            $output->writeln("Timecheckorder:error");
        }

    }
}