<?php

namespace app\shellHUA;

use app\common\model\OrderhexiaoModel;
use app\common\model\OrderModel;
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
        $this->setName('Timecheckorder')->setDescription('定时查询订单充值手机余额!');
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
            $orderHXModel = new OrderhexiaoModel();
            $orderModel = new OrderModel();
            //一直查询  --等待 回调 code!=0  改为status  =2
            $orderData = $orderModel
                ->where('order_status', '=', 4)
                ->where('next_check_time', '<', time())
//                ->where('order_limit_time', '>', time())
//                ->where('check_status', '=', 0)
                ->where('check_times', '<', 3)
                ->select();

            $totalNum = count($orderData);
            if ($totalNum > 0) {
                foreach ($orderData as $k => $v) {
//                    $db::startTrans();
                    $updateCheckWhere['order_no'] = $v['order_no'];
//                    $updateCheckWhere['check_status'] = 0;
                    $lock = $db::table("bsa_order")->where($updateCheckWhere)->find();
                    if ($lock) {
                        if ($lock['check_status'] == 0) {
                            //修改订单查询状态为查询中
                            $updateCheckData['check_status'] = 1;
                            $updateCheckData['last_use_time'] = time();
                            $updateCheckData['next_check_time'] = time() + 90;
                            $db::table("bsa_order")->where($updateCheckWhere)
                                ->update($updateCheckData);
                            //修改订单查询状态为查询中 end

                            $getResParam['order_no'] = $v['order_no'];
                            $getResParam['phone'] = $v['account'];
                            $getResParam['action'] = "other";
                            $checkStartTime = date("Y-m-d H:i:s", time());
                            $getPhoneAmountRes = $orderHXModel->checkPhoneAmount($getResParam, $v['order_pay']);
                            if ($getPhoneAmountRes != "success") {
                                $updateCheckWhere['order_no'] = $v['order_no'];
                                $updateCheckData['check_status'] = 0;
                                $db::table("bsa_order")->where($updateCheckWhere)
                                    ->update($updateCheckData);
                            }

                            logs(json_encode(['phone' => $v['account'],
                                "order_no" => $v['order_no'],
                                "startTime" => $checkStartTime,
                                "endTime" => date("Y-m-d H:i:s", time()),
                                "getPhoneAmountRes" => $getPhoneAmountRes
                            ]), 'TimecheckordercheckPhoneAmount');
//                            $db::commit();
                        } else {
//                            $db::rollback();
                        }
                    } else {
//                        $db::rollback();
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