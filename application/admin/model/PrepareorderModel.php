<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/3/17
 * Time: 4:48 PM
 */

namespace app\admin\model;

use think\Model;

class PrepareorderModel extends Model
{
    protected $table = 'bsa_order_prepare';

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

    /**
     * 增加预产
     * @param $data
     * @return array
     */
    public function addPrepare($data)
    {
        try {

            $has = $this->where('order_amount', $data['order_amount'])->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '预产任务已经存在');
            }

            $this->insert($data);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '添加预产成功');
    }

    /**
     * 获取预产信息
     * @param $id
     * @return array
     */
    public function getPrepareById($id)
    {
        try {
            $info = $this->where('id', $id)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 编辑管理员
     * @param $data
     * @return array
     */
    public function editPrepare($data)
    {
        try {

            $has = $this->where('order_amount', $data['order_amount'])->where('id', '<>', $data['id'])
                ->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '预产请求已经存在');
            }

            $this->save($data, ['id' => $data['id']]);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '编辑预产成功');
    }

    /**
     * 删除预产请求
     * @param $id
     * @return array
     */
    public function delPrepare($id)
    {
        try {
//            if (1 == $id) {
//                return modelReMsg(-2, '', 'admin管理员不可删除');
//            }

            $this->where('id', $id)->delete();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '删除成功');
    }


    /**
     * 更新登录时间
     * @param $id
     */
    public function updatePrepareInfoById($id, $param)
    {
        try {

            $this->where('id', $id)->update($param);
        } catch (\Exception $e) {

        }
    }
}