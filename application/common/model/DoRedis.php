<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/3/17
 * Time: 4:48 PM
 */

namespace app\common\model;

use think\Db;
use think\Model;
use app\common\Redis;

class DoRedis extends Model
{

    public function getCreateNumByAmount($amount)
    {
        try {
            $redis = new Redis();
            $key = $amount . "prepareNum" . "status";
            if ($redis->exists($key)) {
                return $redis->get($key);
            }
        } catch (\Exception $exception) {
            return modelReMsg(-11, '', 'exception');
        } catch (\Error $error) {
            return modelReMsg(-22, '', 'error');
        }
    }

    //设置预拉数量
    public function setCreatePrepareOrderNumByAmount($amount, $num)
    {
        try {
            $redis = new Redis();
            $key = $amount . "prepareNum";
            $setNum = $redis->get($key);
            if ($setNum == $num) {
                return modelReMsg(0, '', '已存在相同预拉');
            }
            $redis->set($key, $num);
            return modelReMsg(0, '', '设置成功！');

        } catch (\Exception $exception) {
            return modelReMsg(-11, '', 'exception');
        } catch (\Error $error) {
            return modelReMsg(-22, '', 'error');
        }
    }

    //获取预拉任务 ||开启的
    public function getPrepareList()
    {
        try {
            $redis = new Redis();
            $key = "prepareList" . "1";
//            $setNum = $redis->get($key);
//            if ($setNum == $num) {
//                return modelReMsg(0, '', '已存在相同预拉任务');
//            }
        } catch (\Exception $exception) {
            return modelReMsg(-11, '', 'exception');
        } catch (\Error $error) {
            return modelReMsg(-22, '', 'error');
        }
    }



}