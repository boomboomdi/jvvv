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

    public function ceshiRedis()
    {
        $redis = new Redis();
//        var_dump($redis);exit;
        $account = "ces1i";
        $ishas = $redis->get($account);
        if (empty($ishas)) {
            echo "重新设置";
            $redis->set($account, $account, 180);
        } else {
            echo($redis->get($account));
        }
    }

}