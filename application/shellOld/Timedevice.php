<?php

namespace app\shellOld;

use app\common\model\DeviceModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\OrderModel;
use app\common\model\SystemConfigModel;
use think\Db;

class Timedevice extends Command
{
    protected function configure()
    {
        $this->setName('Timedevice')->setDescription('定时处理超市订单');
    }

    /**
     * 定时处理超时订单 修改订单状态
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        $totalNum = 0;
        $successNum = 0;
        $errorNum = 0;
        try {
            //时间差
            $limitTime = SystemConfigModel::getPayLimitTime();
            $now = time();
            $lockLimit = $now - $limitTime;
            // 1、支付成功（下单成功）！2、支付失败（下单成功）！3、下单失败！4、等待支付（下单成功）！5、已手动回调。

            $updateStepOneData['order_status'] = 2;
            $updateDataWhere['order_status'] = 2;
            $orderModel = new OrderModel();
            $deviceModel = new DeviceModel();
            $totalNum = $orderModel->where('add_time', '<', $lockLimit)->where($updateDataWhere)->count();
//            $db = new Db();
            if ($totalNum > 0) {
                $updateData = $orderModel->where('add_time', '<', $lockLimit)->where($updateDataWhere)->select();
                foreach ($updateData as $key => $val) {
                    $deviceWhere['account'] = $val['account'];
                    $deviceUpdate['order_status'] = 1;
                    $deviceModel->updateDeviceStatus($deviceWhere, $deviceUpdate);
                    //循环处理超时订单以及解锁相应得设备
                    $updateOrder['order_status'] = 3;
                    $orderModel->where('order_status', '=', $val['order_status'])->update($updateOrder);
                }
            }


            $output->writeln("Timedevice:总应强制超时订单数" . $totalNum);
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'Timedevice exception');
            $output->writeln("Timedevice:总应强制超时订单数" . $totalNum . "exception");
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'Timedevice  error');
            $output->writeln("Timedevice:总应强制超时订单数" . $totalNum . "error");
        }

    }
}