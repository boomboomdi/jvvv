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

class MerchantApiModel extends Model
{
    protected $table = 'bsa_merchant_api';

    /**
     * 获取商户
     * @param $limit
     * @param $where
     * @return array
     */
    public function getMerchantApis($limit, $where)
    {
        $prefix = config('database.prefix');
        try {

            $res = $this->where($where)
            //->leftJoin($prefix . 'role', $prefix . 'admin.role_id = ' . $prefix . 'role.role_id')
            ->order('id', 'desc')->paginate($limit);

        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $res, 'ok');
    }

    /**
     * 增加商户
     * @param $param
     * @return array
     */
    public function addMerchantapi($param)
    {
        try {
            $has = $this->where($param)->findOrEmpty()->toArray();
            if(!empty($has)) {
                return modelReMsg(-2, '', '商户名已经存在');
            }

            $param['add_time'] = time();
            $param['update_time'] = time();
            $this->insert($param);
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '开通道成功');
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


}