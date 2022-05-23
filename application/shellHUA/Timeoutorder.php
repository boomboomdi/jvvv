<?php

namespace app\shellHUA;

use app\common\model\OrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\OrderhexiaoModel;
use app\common\model\SystemConfigModel;
use think\Db;

class Timeoutorder extends Command
{
    protected function configure()
    {
        $this->setName('Timeoutorder')->setDescription('冻结超时未支付订单');
    }

    /**
     * 冻结超时未支付订单
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {

        $orderModel = new OrderModel();
        try {
            $orderData = $orderModel
                ->where('order_status', '=', 4)
                ->where('notify_status', '=', 0)
                ->where('do_notify', '=', 0)
                ->where('order_me', '<>', null)
                ->where('order_limit_time', '<', time())
                ->select();
            $totalNum = count($orderData);
            if ($totalNum > 0) {
                $orderHXModel = new OrderhexiaoModel();
                foreach ($orderData as $k => $v) {
                    //处理order表
                    $orderWhere['order_me'] = $v['order_me'];
                    $updateOrderData['order_status'] = 2;
                    $updateOrderData['last_use_time'] = time();
                    $updateOrderData['order_desc'] = "支付超时订单冻结!";
                    $updateOrderRes = $orderModel->localUpdateOrder($orderWhere, $updateOrderData);
                    //处理对应order_hexiao表
                    $updateHXOrderData['order_status'] = 2;
                    $updateHXOrderData['last_use_time'] = time();
//                    $updateHXOrderData['order_limit_time'] = time() + 2700;
                    $updateHXOrderData['order_desc'] = "核销单冻结中!";
                    $updateHXOrderRes = $orderHXModel->localUpdateHXOrder($orderWhere, $updateHXOrderData);
                    logs(json_encode(['order_no' => $v['order_no'],
                        "time" => date("Y-m-d H:i:s", time()),
                        "updateOrderRes" => $updateOrderRes,
                        "updateHXOrderRes" => $updateHXOrderRes,
                    ]), 'Timeoutorder');
                }
            }
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()
            ]), 'TimeoutorderException');

            $output->writeln("Timeoutorder:超时预产单处理exception：" . $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()
            ]), 'TimeoutorderError');

            $output->writeln("Timeoutorder:超时预产单处理error" . $error->getMessage());
        }

    }
}