<?php

namespace app\shell;

use app\common\model\OrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\SystemConfigModel;
use app\common\model\NotifylogModel;
use think\Db;

class Timenotifyorder extends Command
{
    protected function configure()
    {
        $this->setName('Timenotifyorder')->setDescription('回调四方！');
    }

    /**
     * 定时回调四方
     * @param Output $output
     * @return int|null|void
     * @todo
     */
    protected function execute(Input $input, Output $output)
    {
        $db = new Db();
        try {
            $orderModel = new OrderModel();
            $orderData = $orderModel
                ->where('order_status', '=', 1)
                ->where('pay_status', '=', 1)
//                ->where('notify_status', '<>', 1)
                ->where('do_notify', '=', 0)
                ->where('notify_times', '<', 5)
                ->select();
            $totalNum = count($orderData);
            if ($totalNum > 0) {
                foreach ($orderData as $k => $v) {
                    $orderWhere['order_no'] = $v['order_no'];
                    $orderWhere['account'] = $v['account'];
                    $notifying['do_notify'] = 1;
                    $db::table('bsa_order')->where($orderWhere)->update($notifying);
                    $notifyOrderRes = $orderModel->orderNotify($v);
                    if (!isset($notifyOrderRes['code']) || $notifyOrderRes['code'] != 1000) {
                        logs(json_encode(['orderData' => $v,
                            "time" => date("Y-m-d H:i:s", time()),
                            "notifyRes" => json_encode($notifyOrderRes),
                        ]), 'ADONTDELETENotifyOrderFail');
                    }
                    $notifying['do_notify'] = 0;
                    $db::table('bsa_order')->where($orderWhere)->update($notifying);
                }
            }
            $output->writeln("Timenotifyhxiao:订单总数" . $totalNum);
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'Timenotifyhxiao_exception');
            $output->writeln("Timenotifyhxiao:exception");
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'TiTimenotifyhxiao_error');
            $output->writeln("Timenotifyhxiao:error");
        }

    }
}