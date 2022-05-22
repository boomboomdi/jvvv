<?php

namespace app\shellOld;

use app\common\model\DeviceModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\OrderModel;
use app\common\model\SystemConfigModel;
use app\common\model\NotifylogModel;
use think\Db;

class Timenotify extends Command
{
    protected function configure()
    {
        $this->setName('Timenotify')->setDescription('定时处理回调数据!');
    }

    /**
     * 定时处理回调日志 修改订单状态  @todo
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $limit = 10;
            $limitTime = SystemConfigModel::getPayLimitTime();
            $now = time();
            $lockLimit = $now - $limitTime;
            $orderModel = new OrderModel();
            $deviceModel = new DeviceModel();
            $notifyLogModel= new NotifylogModel();
            $notifyLogWhere['status'] = 2;
            $notifyLogData = $notifyLogModel->where($notifyLogWhere)
                ->order('id', 'desc')->paginate($limit);
//            $totalNum = $->where('add_time', '<', $lockLimit)->where($updateDataWhere)->count();
//            $updateData = $orderModel->where('add_time', '<', $lockLimit)->where($updateDataWhere)->select();
//            $db = new Db();
            $totalNum = count($notifyLogData);
            if ($totalNum > 0) {
                foreach ($notifyLogData as $key => $val) {
                    $orderWhere['account'] = $val['account'];
                    $orderWhere['order_status'] = 4;
                    $orderWhere['amount'] = $val['amount'];
                    //匹配成功
                    $order = $orderModel->where($orderWhere)->where('add_time', '<', $lockLimit)->find();
                    if(!empty($order)){
                        //处理订单
                        //
                        
                        //处理设备.

                    }
                    //处理回调日志

//                    $deviceModel->updateDeviceStatus($deviceWhere, $deviceUpdate);
                    //循环处理超时订单以及解锁相应得设备
//                    $orderModel->where('apiMerchantOrderNo', '=', $val['apiMerchantOrderNo'])->update($updateStepOneData);
                }
            }


            $output->writeln("TimeouttorderNotify:总应强制超时订单数" . $totalNum);
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'TimeouttorderNotify_exception');
            $output->writeln("TimeouttorderNotify:总应强制超时订单数" . $totalNum . "exception");
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'TimeouttorderNotify_error');
            $output->writeln("TimeouttorderNotify:总应强制超时订单数" . $totalNum . "error");
        }

    }
}