<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/3/17
 * Time: 4:48 PM
 */

namespace app\admin\model;

use GatewayWorker\Lib\Db;
use think\Model;

class CammyModel extends Model
{
    protected $table = 'bsa_cammy';

    /**
     * 预产列表
     * @param $limit
     * @param $where
     * @return array
     */
    public function getLists($limit, $where)
    {
        $prefix = config('database.prefix');

        try {

            $res = $this->where($where)
                ->order('id', 'desc')->paginate($limit);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $res, 'ok');
    }


}