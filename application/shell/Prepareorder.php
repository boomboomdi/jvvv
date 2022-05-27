<?php

namespace app\shell;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\OrderprepareModel;
use app\common\model\PreparesetModel;
use think\Db;
use app\common\Redis;

class Prepareorder extends Command
{
    protected function configure()
    {
        $this->setName('Prepareorder')->setDescription('预拉订单！');
    }

    /**
     * 预拉订单
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        $totalNum = 0;
        $msg = "预拉开始";
        $db = new Db();
        try {
            $orderPrepareModel = new OrderprepareModel();
            //下单金额
            $prepareAmountList = $db::table("bsa_prepare_set")
                ->where("status", "=", 1)
                ->where("prepare_num", ">", 0)
                ->select();
            logs(json_encode([
                'param' => $prepareAmountList,
                "prepare_num" => count($prepareAmountList)
            ]), 'Prepareorder');
            $prepareSetModel = new PreparesetModel();
            if (!is_array($prepareAmountList) || count($prepareAmountList) == 0) {
                $output->writeln("Prepareorder:无预产任务");
            } else {
                foreach ($prepareAmountList as $k => $v) {
                    $redis = new Redis(['index' => 1]);
                    $PrepareOrderKey = "prepareOrder" . $v['order_amount'];
                    $setRes = $redis->setnx($PrepareOrderKey, $PrepareOrderKey, 10);
//
                    if ($setRes) {
                        $doNum = $v['prepare_num'];
                        //查询可用订单
                        $canUseNum = $orderPrepareModel->getPrepareOrderNum($v['order_amount']);
                        if (isset($canUseNum['data']) && $canUseNum['data'] > 0) {
                            $doNum -= $canUseNum['data'];
                        }
                        //查询匹配中订单
                        $doPrepareNum = $orderPrepareModel->getPrepareOrderNum($v['order_amount'], 3);
                        if (isset($doPrepareNum['data']) && $doPrepareNum['data'] > 0) {
                            $doNum -= $doPrepareNum['data'];
                        }
                        logs(json_encode([
                            'order_amount' => $v['order_amount'],
                            'canUseNum' => $canUseNum['data'],
                            'doPrepareNum' => $doPrepareNum['data'],
                            'doNum' => $v['prepare_num'],
                            'PrepareOrderKey' => $PrepareOrderKey,
                            'timeout' => $PrepareOrderKey,
                            'setRes' => $setRes,
                        ]), 'curlAmountGetJDOrderUrl');
                        if ($doNum > 0) {
                            $checkStartTime = date('Y-m-d H:i:s', time());
                            $createPrepareOrderRes = $orderPrepareModel->createPrepareOrder($v['order_amount'], $doNum);

                            if (!isset($createPrepareOrderRes['code']) || $createPrepareOrderRes['code'] != 0) {
                                logs(json_encode([
                                    "startTime" => $checkStartTime,
                                    "endTime" => date("Y-m-d H:i:s", time()),
                                    'param' => $v['order_amount'],
                                    'doNum' => $doNum,
                                    "curlLocalRes" => $createPrepareOrderRes
                                ]), 'curlGetJDOrderUrlFail');
                            }
                            $redis->delete($PrepareOrderKey);
                        }
                    }
                }
            }
            $output->writeln("Prepareorder:预产单处理成功！" . $msg);
        } catch (\Exception $exception) {
//            $db::rollback();
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'Prepareorder_exception');
            $output->writeln("Prepareorder:浴场处理失败！" . $totalNum . "exception" . $exception->getMessage());
        } catch (\Error $error) {
//            $db::rollback();
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'Prepareorder_error');
            $output->writeln("Prepareorder:浴场处理失败！！" . $totalNum . "error");
        }

    }
}