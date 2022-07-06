<?php

namespace app\shell;

use app\common\model\OrderModel;
use app\common\model\OrderprepareModel;
use app\common\Redis;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\SystemConfigModel;
use app\common\model\NotifylogModel;
use think\Db;

class Timecheckcookie extends Command
{
    protected function configure()
    {
        $this->setName('Timecheckcookie')->setDescription('定时查询COOKIE状态!');
    }

    /**
     * 定时查询COOKIE
     * @param Output $output
     * @return int|null|void
     * @todo
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $totalNum = 0;
            $onlineCkData = Db::table("bsa_cookie")
                ->where('use_times','>',8)
                ->where('status','=1')
                ->select();
            if (!is_array($onlineCkData) || count($onlineCkData) == 0) {
                $output->writeln("Timecheckcookie:无可处理cookie");die();
            }
            foreach ($onlineCkData as $k => $v) {
                $redis = new Redis(['index' => 1]);
                $checkCookieKey = "Timecheckcookie" . $v['account'];
                $setRes = $redis->setnx($checkCookieKey, $checkCookieKey, 10);
                if ($setRes) {
                    //查询未支付订单数量
                    //检查订单表
                    $noPayCount = Db::table('bsa_order')
                        ->where('ck_account', '=', $v['account'])
                        ->where('pay_status', '=', 3)
                        ->count();
                    if ($noPayCount > 8) {
                        $totalNum++;
                        $cookieUpdate['order_desc'] = "未支付超过9次支付失败！";
                        $cookieUpdate['status'] = 2;
                        $updateRes = Db::table('bsa_cookie')
                            ->where('ck_account', '=', $v['account'])
                            ->update($cookieUpdate);
                        if(!$updateRes){
                            $redis->delete($checkCookieKey);
                        }
                        logs(json_encode([
                            'ck_account' => $v['account'],
                            'noPayCount' => $noPayCount,
                            'cookieUpdate' => $cookieUpdate,
                            'updateRes' => $updateRes,
                        ]), 'Timecheckcookie');
                        $redis->delete($checkCookieKey);
                    }

                }
            }
            $output->writeln("Timecheckcookie:订单总数" . $totalNum);
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'Timecheckcookie_exception');
            $output->writeln("Timecheckcookie:exception");
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'Timecheckcookie_error');
            $output->writeln("Timecheckcookie:error");
        }

    }
}