<?php

namespace app\common\model;

use think\Db;
use think\facade\Log;
use think\Model;

class CammyModel extends Model
{
    protected $table = 'bsa_cammy';

    //增加一个卡密
    public function addCammy($cammy)
    {
        try {
            $has = $this->where('card_name', $cammy['card_name'])->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(0, '', '卡密已存在');
            }

            $addRes = $this->create($cammy);
            if (!$addRes) {
                return modelReMsg(-2, '', '添加失败');
            }
            return modelReMsg(0, '', '添加卡密成功');
        } catch (\Exception $e) {
            return modelReMsg(-11, '', $e->getMessage());
        }

    }
}