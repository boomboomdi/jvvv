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
                return modelReMsg(-2, '', '卡密已存在');
            }

            $this->insert($cammy);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, '', '添加卡密成功');

    }
}