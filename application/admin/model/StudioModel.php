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

class StudioModel extends Model
{
    protected $table = 'bsa_studio';

    /**
     * 获取工作室
     * @param $limit
     * @param $where
     * @return array
     */
    public function getStudios($limit, $where)
    {
        $prefix = config('database.prefix');

        try {

            $res = $this->field($prefix . 'studio.*')->where($where)
//                ->leftJoin($prefix . 'role', $prefix . 'admin.role_id = ' . $prefix . 'role.role_id')
                ->order('studio_id', 'desc')->paginate($limit);

        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $res, 'ok');
    }

    /**
     * 增加工作室
     * @param $admin
     * @return array
     */
    public function addStudio($studio)
    {
        try {

            $has = $this->where('studio', $studio['studio'])->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '工作室已经存在');
            }

            $this->insert($studio);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '添加工作室成功');
    }

    /**
     * 获取工作室信息
     * @param $studioId
     * @return array
     */
    public function getStudioById($studioId)
    {
        try {

            $info = $this->where('studio_id', $studioId)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 编辑工作室
     * @param $studio
     * @return array
     */
    public function editStudio($studio)
    {
        try {

            $has = $this->where('studio', $studio['studio'])->where('studio_id', '<>', $studio['studio_id'])
                ->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '工作室名已经存在');
            }

            $this->save($studio, ['studio_id' => $studio['studio_id']]);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '编辑工作室成功');
    }

    /**
     * 删除工作室
     * @param $studioId
     * @return array
     */
    public function delStudio($studioId)
    {
        try {
//            if (1 == $adminId) {
            return modelReMsg(-2, '', '工作室暂时不支持删除');
//            }

            $this->where('studio_id', $studioId)->delete();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '删除成功');
    }

    /**
     * 获取工作室信息
     * @param $studioName
     * @return array
     */
    public function getStudioByName($studioName)
    {
        try {

            $info = $this->where('studio_name', $studioName)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 获取工作室信息
     * @param $id
     * @return array
     */
    public function getStudioInfo($id)
    {
        try {

            $info = $this->where('studio_id', $id)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

}