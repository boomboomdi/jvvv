<?php

namespace app\shellOld;

use app\common\model\OrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\OrderdouyinModel;
use app\common\model\SystemConfigModel;
use think\Db;

class DestroytorderUrl extends Command
{
    protected function configure()
    {
        $this->setName('Destorytorderurl')->setDescription('销毁已拉单但失效推单！');
    }

    /**
     * 销毁已拉单未支付链接
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        $limitTime = 180;
        $now = time();
        $totalNum = 0;
        $successNum = 0;
        $errorNum = 0;
        $lockLimit = $now - $limitTime;  //更新锁定时间
        $addLockTime = time() - 600;
        $orderdouyinModel = new OrderdouyinModel();
//        $LimitStartTime = time() - $limitTime;
        $db = new Db();
        try {
            //查询下单之前600S
            //没有匹配订单（order_me =null）的
            //不管预拉与否 url_status = 1
            //录入时间小于当前时间600s之前 add_time
            //禁用
            $orderData = $orderdouyinModel
                ->where('order_status', '<>', 1)
                ->where('notify_status', '=', 0)
//                ->where('url_status', '=', 2)
                ->where('order_me', '=', null)
                ->where('get_url_time', '<', $lockLimit)
                ->where('get_url_time', '>', 0)
                ->where('prepare_limit_time', '>', time())
//                ->where('add_time', '>', $addLockTime)
                ->select();
            logs(json_encode(['orderData' => $orderData, "sql" => Db::table("bsa_torder_douyin")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'Distorytorderurl_log');

            $totalNum = count($orderData);
            if ($totalNum > 0) {
                foreach ($orderData as $k => $v) {
//
                    //支付链接不可用  改为初始状态
                    //status = 0  get_url_time = 0 url_status_=0   use_times +1 weight = 0

//                    $useTimes = $v['use_times'];
//                    if ($v['use_times'] == 0) {
//                        $useTimes = $useTimes + 2;
//                    } else {
//                        $useTimes = $useTimes++;
//                    }
                    $torderDouyinWhere['t_id'] = $v['t_id'];
                    $torderDouyinUpdate['url_status'] = 0;   //订单已失效 以停止查询
                    $torderDouyinUpdate['cookie'] = " ";   //订单已失效 以停止查询
                    $torderDouyinUpdate['weight'] = 0;   //订单已失效 以停止查询
//                    $torderDouyinUpdate['use_times'] = $useTimes;   //订单已失效 以停止查询
                    $torderDouyinUpdate['status'] = 0;       //推单改为最终结束状态 等待自动回调核销支付失败
                    $torderDouyinUpdate['get_url_time'] = 0;  ///推单改为最终结束状态 等待自动回调核销支付失败
                    $torderDouyinUpdate['order_desc'] = "等待第" . ($v['use_times'] + 1) . "次预拉|";

                    logs(json_encode([
                        "doTime" => date("Y-m-d H:i:s", time()),
                        'order_no' => $v['order_no'],
                        'account' => $v['account'],
                        'oldParam' => $v,
                        'doUpdateParam' => $torderDouyinUpdate,
                    ]), 'destroyTOrderUrl_log');
                    $updateTorderRes = $db::table("bsa_torder_douyin")->where($torderDouyinWhere)
                        ->update($torderDouyinUpdate);
                    if (!$updateTorderRes) {
                        $errorNum += 1;
                        logs(json_encode([
                            "doFailTime" => date("Y-m-d H:i:s", time()),
                            "doUpdateRes" => $updateTorderRes,
                            'order_no' => $v['order_no'],
                            'account' => $v['account'],
                            "updateTOrderRes" => $updateTorderRes,
                        ]), 'destroyTOrderUrl_log');
                    } else {
                        logs(json_encode([
                            "doSuccessTime" => date("Y-m-d H:i:s", time()),
                            'order_no' => $v['order_no'],
                            'account' => $v['account'],
                            "doUpdateRes" => $updateTorderRes,
                            "updateTOrderRes" => $updateTorderRes,
                        ]), 'destroyTOrderUrl_log');
                    }

                }
                $output->writeln("DestroyTOrderUrl:超时预产单处理成功" . "总失效单" . $totalNum . "成功处理:" . $successNum . "失败:" . $errorNum);

            }
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'destroyTOrderUrl_log');

            $output->writeln("DestroyTOrderUrl:超时预产单处理exception：" . $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'destroyTOrderUrl_log');

            $output->writeln("DestroyTOrderUrl:超时预产单处理error" . $error->getMessage());
        }

    }
}