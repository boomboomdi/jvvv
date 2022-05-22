<?php

namespace app\shellOld;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\OrderdouyinModel;
use app\common\model\SystemConfigModel;
use think\Db;

class Prepareorder extends Command
{
    protected function configure()
    {
        $this->setName('Prepareorder')->setDescription('预先生成！');
    }

    /**
     * 定时生成订单
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        $totalNum = 0;
        $successNum = 0;
        $errorNum = 0;
        $msg = "预拉开始";
        $db = new Db();
        try {
            //时间差  话单时间差生成订单时间差
//            $limitTime = SystemConfigModel::getTorderLimitTime();
            $limitTime = 900;
            $now = time();

            $orderDouYinModel = new OrderdouyinModel();
            //下单金额
            $prepareAmountList = $db::table("bsa_prepare_set")
                ->where("status", "=", 1)
                ->select();
//            if (count($prepareAmountList) > 0) {
            if (!is_array($prepareAmountList) || count($prepareAmountList) == 0) {
                $output->writeln("Prepareorder:无预产任务");
            } else {
                foreach ($prepareAmountList as $k => $v) {
                    $canUseNum = $db::table("bsa_torder_douyin")
                        ->where('status', '=', 1)
                        ->where('url_status', '=', 1)
                        ->where('total_amount', '=', $v['order_amount'])
                        ->where('get_url_time', '>', time() - 180)
                        ->where('get_url_time', '<', time())
//                    ->where('get_url_time', '>', 0)
//                    ->where('get_url_time', '<', time() + 180)
                        ->where('limit_time_1', '>', time())
                        ->count();
                    $doPrepareNum = $db::table("bsa_torder_douyin")
                        ->where('status', '=', 0)
//                        ->where('url_status', '=', 1)
                        ->where('total_amount', '=', $v['order_amount'])
                        ->where('weight', '=', 1)
                        ->where('get_url_time', '=', 0)
//                        ->where('limit_time_1', '>', time())
                        ->order("add_time asc")
                        ->count();
                    $canUseNum = $canUseNum + $doPrepareNum;
                    $doNum = $v['prepare_num'] - $canUseNum;
                    logs(json_encode(["total" => $v['prepare_num'], 'canUseNum' => $canUseNum, 'doNum' => $doNum, 'amount' => $v['order_amount'], "sql" => $db::table("bsa_torder_douyin")->getLastSql()]), 'prepareorderapicreateindex_log');

                    if (($doNum > 0) && $v['status'] == 1) {
                        $res = $orderDouYinModel->createOrder($v, $doNum);
                        if ($res['code'] == 0 && $res['data'] > 0) {
                            $msg .= "金额:" . $v['order_amount'] . $res['msg'] . "(" . $res['data'] . "个)||--";
                        } else {
                            $msg .= "失败金额:" . $v['order_amount'] . $res['msg'] . "(" . $res['data'] . "个)||--";
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