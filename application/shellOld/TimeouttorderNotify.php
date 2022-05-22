<?php

namespace app\shellOld;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\TorderModel;
use app\common\model\SystemConfigModel;
use think\Db;

class TimeouttorderNotify extends Command
{
    protected function configure()
    {
        $this->setName('TimeouttorderNotify')->setDescription('定时处理超时/订单');
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
            $updateStepOneData['orderStatus'] = 5;
            $updateDataWhere['orderStatus'] = 0;
            $torderModel = new TorderModel();
            $totalNum = $torderModel->where('orderCreateDate', '<', $lockLimit)->where($updateDataWhere)->count();
            $updateData = $torderModel->where('orderCreateDate', '<', $lockLimit)->where($updateDataWhere)->select();
//            $db = new Db();
            if ($totalNum > 0) {
                foreach ($updateData as $key => $val) {
                     $torderModel->tOrderNotifyForFail($val['apiMerchantOrderNo']);
                    //循环处理超时订单以及解锁相应得设备
                     $torderModel->where('apiMerchantOrderNo', '=', $val['apiMerchantOrderNo'])->update($updateStepOneData);
                }
            }


            $errorNum = 0;
            if (!$successNum) {
                $errorNum = $totalNum;
            }
            $output->writeln("TimeouttorderNotify:总应强制超时订单数" . $totalNum );
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'TimeouttorderNotify_exception');
            $output->writeln("TimeouttorderNotify:总应强制超时订单数" . $totalNum . "exception");
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'TimeouttorderNotify_error');
            $output->writeln("TimeouttorderNotify:总应强制超时订单数" . $totalNum . "error");
        }

    }
}