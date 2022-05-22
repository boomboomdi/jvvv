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

class WriteoffModel extends Model
{
    protected $table = 'bsa_write_off';

    /**
     * 获取核销商
     * @param $limit
     * @param $where
     * @return array
     */
    public function getWriteoffs($limit, $where)
    {
        $prefix = config('database.prefix');
        try {
            $res = $this->field($prefix . 'write_off.*' )->where($where)
            ->order('write_off_id', 'desc')->paginate($limit);
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $res, 'ok');
    }

    /**
     * 增加核销商
     * @param $merchant
     * @return array
     */
    public function addWriteoff($writeoff)
    {
        try {

            $has = $this->where('write_off_username', $writeoff['write_off_username'])->findOrEmpty()->toArray();
            if(!empty($has)) {
                return modelReMsg(-2, '', '商户名已经存在');
            }

            $writeoff['add_time'] = date("Y-m-d H:i:s",time());
            $this->insert($writeoff);
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '添加核销商成功');
    }

    /**
     * 获取核销商信息
     * @param $writeoffId
     * @return array
     */
    public function getWriteoffById($writeoffId)
    {
        try {

            $info = $this->where('write_off_id', $writeoffId)->findOrEmpty()->toArray();
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 编辑核销商
     * @param $writeoff
     * @return array
     */
    public function editWriteoff($writeoff)
    {
        try {

            $has = $this->where('write_off_username', $writeoff['write_off_username'])->where('write_off_id', '<>', $writeoff['write_off_id'])
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return modelReMsg(-2, '', '商户名已经存在');
            }
            $writeoff['last_update_time'] = date("Y-m-d H:i:s",time());
            $this->save($writeoff, ['write_off_id' => $writeoff['write_off_id']]);
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '编辑核销商户成功');
    }

    /**
     * 删除商户
     * @param $merchantId
     * @return array
     */
    public function delWriteoff($writeOffId)
    {
        try {
            if (1 == $writeOffId) {
                return modelReMsg(-2, '', '测试核销商户不可删除');
            }

            $this->where('write_off_id', $writeOffId)->delete();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '删除成功');
    }

    /**
     * 获取核销商信息
     * @param $writeoffId
     * @return array
     */
    public function getWriteOffBySign($writeoffSign)
    {
        try {

            $info = $this->where('write_off_sign', $writeoffSign)->findOrEmpty()->toArray();
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }
}