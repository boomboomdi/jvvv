<?php

namespace app\shell;

use app\common\model\OrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\OrderhexiaoModel;
use app\common\model\SystemConfigModel;
use think\Db;

class Timerestarthxorder extends Command
{
    protected function configure()
    {
        $this->setName('Timerestarthxorder')->setDescription('解冻未支付核销单');
    }

    /**
     * 解冻未支付核销单。以重新使用
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $orderHXModel = new OrderhexiaoModel();
            $orderHXData = $orderHXModel
                ->where('pay_status', '=', 0)
                ->where('notify_status', '=', 0)
                ->where('order_me', '<>', null)
                ->where('do_notify', '=', 0)
                ->where('check_status', '=', 0)
                ->where('order_limit_time', '<', time())
                ->where('limit_time', '>', time() + 3600)     //当前时间> limit_time+3600 才重新启用
                ->select();
            logs(json_encode([
                "time" => date("Y-m-d H:i:s", time()),
                "updateHXOrderRes" => Db::table("bsa_order_hexiao")->getLastSql(),
            ]), 'Timerestarthxorder_log');
            $totalNum = count($orderHXData);
            if ($totalNum > 0) {
                foreach ($orderHXData as $k => $v) {
                    $orderWhere['id'] = $v['id'];
                    $updateHXOrderData['order_status'] = 0;
                    $updateHXOrderData['status'] = 0;
                    $updateHXOrderData['last_use_time'] = time();
                    $updateHXOrderData['order_me'] = null;
                    $updateHXOrderData['use_time'] = 0;
                    $updateHXOrderData['order_limit_time'] = 0;
                    $updateHXOrderData['order_desc'] = "等待第" . ($v['use_times'] + 1) . "次使用";
                    $updateHXOrderRes = $orderHXModel->localUpdateHXOrder($orderWhere, $updateHXOrderData);
                    logs(json_encode([
                        'order_no' => $v['order_no'],
                        "time" => date("Y-m-d H:i:s", time()),
                        "updateHXOrderRes" => $updateHXOrderRes,
                    ]), 'Timerestarthxorder');
                }
            }
            $output->writeln("Timerestarthxorder:订单总数" . $totalNum);
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()]), 'TimerestarthxorderException');
            $output->writeln("TimerestarthxorderException");
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()
            ]), 'TimerestarthxorderError');
            $output->writeln("TimerestarthxorderError");
        }
    }
}