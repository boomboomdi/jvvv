<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/3/17
 * Time: 4:48 PM
 */
namespace app\merchant\model;

use think\Model;

class MerchantModel extends Model
{
    protected $table = 'bsa_merchant';

    /**
     * 获取商户
     * @param $limit
     * @param $where
     * @return array
     */
    public function getMerchants($limit, $where)
    {
        $prefix = config('database.prefix');
        try {

            $res = $this->field($prefix . 'merchant.*' )->where($where)
                ->order('merchant_id', 'desc')->paginate($limit);

        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $res, 'ok');
    }

    /**
     * 增加商户
     * @param $merchant
     * @return array
     */
    public function addMerchant($merchant)
    {
        try {

            $has = $this->where('merchant_name', $merchant['merchant_name'])->findOrEmpty()->toArray();
            if(!empty($has)) {
                return modelReMsg(-2, '', '商户名已经存在');
            }

            $this->insert($merchant);
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '添加商户成功');
    }

    /**
     * 获取商户信息
     * @param $merchantId
     * @return array
     */
    public function getMerchantById($merchantId)
    {
        try {

            $info = $this->where('merchant_id', $merchantId)->findOrEmpty()->toArray();
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 编辑商户
     * @param $merchant
     * @return array
     */
    public function editMerchant($merchant)
    {
        try {

            $has = $this->where('merchant_name', $merchant['merchant_name'])->where('merchant_id', '<>', $merchant['merchant_id'])
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return modelReMsg(-2, '', '商户名已经存在');
            }

            $this->save($merchant, ['merchant_id' => $merchant['merchant_id']]);
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '编辑商户成功');
    }

    /**
     * 删除商户
     * @param $merchantId
     * @return array
     */
    public function delMerchant($merchantId)
    {
        try {
            if (1 == $merchantId) {
                return modelReMsg(-2, '', '测试商户不可删除');
            }

            $this->where('merchantId', $merchantId)->delete();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '删除成功');
    }

    /**
     * 获取商户信息
     * @param $name
     * @return array
     */
    public function getMerchantByName($name)
    {
        try {

            $info = $this->where('merchant_username', $name)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 获取商户信息
     * @param $id
     * @return array
     */
    public function getAdminInfo($id)
    {
        try {

            $info = $this->where('admin_id', $id)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 更新登录时间
     * @param $id
     */
    public function updateMerchantInfoById($id, $param)
    {
        try {

            $this->where('merchant_id', $id)->update($param);
        } catch (\Exception $e) {

        }
    }

    /**
     * 根据角色id 获取商户信息
     * @param $roleId
     * @return array
     */
    public function getAdminInfoByRoleId($roleId)
    {
        try {

            $info = $this->where('role_id', $roleId)->select()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, [], $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }
}