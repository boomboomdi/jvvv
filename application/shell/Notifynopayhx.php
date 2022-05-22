<?php

namespace app\shell;

use app\common\model\OrderhexiaoModel;
use app\common\model\OrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\SystemConfigModel;
use app\common\model\NotifylogModel;
use think\Db;

class Notifynopayhx extends Command
{
    protected function configure()
    {
        $this->setName('Notifynopayhx')->setDescription('处理未支付订单,回调核销！');
    }

    /**
     * 定时回调核销 支付失败
     * @param Output $output
     * @return int|null|void
     * @todo
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $orderHXModel = new OrderhexiaoModel();
            $orderHXData = $orderHXModel
                ->where('notify_status', '=', 0)
                ->where('do_notify', '=', 0)
                ->where('notify_times', '<', 5)
                ->where('limit_time', '<', time())
                ->select();
            $db = new Db();
            $totalNum = count($orderHXData);
            if ($totalNum > 0) {
                foreach ($orderHXData as $k => $v) {
                    if ($v['status'] != 2) {
                        $notifying['status'] = 2;
                    }
                    $orderWhere['order_no'] = $v['order_no'];
                    $orderWhere['account'] = $v['account'];
                    $notifying['do_notify'] = 1;
                    $db::table('bsa_order_hexiao')->where($orderWhere)->update($notifying);
                    $orderHXModel->orderNotifyToWriteOff($v);
                    $notifying['do_notify'] = 0;
                    $db::table('bsa_order_hexiao')->where($orderWhere)->update($notifying);
                }
            }
            $output->writeln("Notifynopayhx:订单总数" . $totalNum);
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'NotifynopayhxException');
            $output->writeln("Notifynopayhx:exception");
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'NotifynopayhxError');
            $output->writeln("Notifynopayhx:error");
        }

    }
}