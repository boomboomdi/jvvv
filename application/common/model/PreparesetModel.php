<?php

namespace app\common\model;

use app\admin\model\CookieModel;
use app\api\model\OrderLog;
use app\api\validate\OrderinfoValidate;
use app\common\Redis;
use think\Db;
use think\facade\Log;
use think\Model;

class PreparesetModel extends Model
{
    protected $table = 'bsa_prepare_set';

    public function doPrepare($prepareAmountList)
    {
        try {
            $orderPrepareModel = new OrderprepareModel();
            foreach ($prepareAmountList as $k => $v) {
                $redis = new Redis();
                $PrepareOrderKey = "PrepareOrder" . $v['account'];
                $setRes = $redis->setnx($PrepareOrderKey, $v['account'], 10);
                if ($setRes) {
                    $doNum = $v['prepare_num'];
                    //查询可用订单
                    $canUseNum = $orderPrepareModel->getPrepareOrderNum($v['amount']);
                    $doNum -= $canUseNum;
                    //查询匹配中订单
                    $doPrepareNum = $orderPrepareModel->getPrepareOrderNum($v['amount'], 3);
                    $doNum -= $doPrepareNum;
                    if ($doNum > 0) {
                        $createPrepareOrderRes = $orderPrepareModel->createPrepareOrder($v['amount'], $doNum);
                        if (!isset($createPrepareOrderRes['code']) || $createPrepareOrderRes['code'] != 0) {
                            $redis->delete($PrepareOrderKey);
                        }
                        $redis->delete($PrepareOrderKey);
                    }
                }
            }

        } catch (\Exception $e) {
            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, "", "success");
    }
}