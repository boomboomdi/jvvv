<?php

namespace app\shellOld;

use app\common\model\OrderdouyinModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\SystemConfigModel;
use app\common\model\NotifylogModel;
use think\Db;
use think\facade\Log;


class Timecheckdouyinhuadan extends Command
{
    protected function configure()
    {
        $this->setName('Timecheckdouyinhuadan')->setDescription('定时处理（抖音话单）回调!');
    }

    /**
     * 定时处理回调日志 修改订单状态  @param Input $input
     * @param Output $output
     * @return int|null|void
     * @todo
     */
    protected function execute(Input $input, Output $output)
    {
        $totalNum = 0;
        $errorNum = 0;
        $doNum = 0;
        $orderData = [];
        $db = new Db();
        try {
            $limit = 10;
            $limitTime = SystemConfigModel::getDouyinPayLimitTime();
//            $limitTime = 900;
            $now = time();
            $lockLimit = $now - $limitTime;
            $orderdouyinModel = new OrderdouyinModel();
            //查询下单之前280s 到现在之前20s的等待付款订单
//            $updateData = $orderModel->where('add_time', '<', $lockLimit)->where($updateDataWhere)->select();
            $LimitStartTime = time() - 900;
            $LimitEndTime = $now - 10;
            $orderData = $orderdouyinModel
                ->where('order_status', '<>', 1)
                ->where('notify_status', '=', 0)
//                ->where('url_status', '=', 2)
//                ->where('add_time', '<', $LimitStartTime)   //时间是过了当前时间之前的15分
                ->where('limit_time_1', '<', time())   //当前时间  > 推单终止使用时间
                ->order('add_time asc')
                ->limit(10)
                ->select();

            $totalNum = count($orderData);
            if ($totalNum > 0) {
                foreach ($orderData as $k => $v) {
                    //回调商户
//                    $orderNotifyNoPayToWriteRes = $orderdouyinModel->orderDouYinNotifyToWriteOff($v);
                    $orderdouyinModel->orderDouYinNotifyToWriteOff($v);

                    $doNum++;
                }
            }
            $output->writeln("Timecheckdouyinhuadan:订单总数" . $totalNum . "处理" . $doNum);
        } catch (\Exception $exception) {
            logs(json_encode(['totalNum' => $totalNum, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'Timecheckdouyinhuadanexception');
            $output->writeln("Timecheckdouyinhuadan:订单总数" . $totalNum . "exception" . json_encode($orderData));
        } catch (\Error $error) {
            logs(json_encode(['totalNum' => $totalNum, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'Timecheckdouyinhuadanerror');
            $output->writeln("Timecheckdouyinhuadan:订单总数" . $totalNum . "error" . json_encode($orderData));
        }
    }
}