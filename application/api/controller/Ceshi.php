<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/15
 * Time: 19:53
 */

namespace app\api\controller;

use app\common\Redis;
use think\Db;
use think\Controller;
use think\Request;
use app\common\model\OrderModel;
use app\common\model\SystemConfigModel;
use app\common\model\DoRedis;
use tool\Log;

class Ceshi extends Controller
{
    public function demo1()
    {
        try {
            $redis = new DoRedis();
            $res = $redis->getCreateNumByAmount();
            var_dump($res);exit;

        } catch (\Exception $exception) {

            return json(msg(-11, '', 'orderInfo error!' . $exception->getMessage()));
        } catch (\Error $error) {
            return json(msg(-22, '', 'orderInfo error!' . $error->getMessage()));
        }
    }

}