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

class PaymentModel extends Model
{
    protected $table = 'bsa_payment';

    /**
     * 获取支付接口
     * @param $limit
     * @param $where
     * @return array
     */
    public function getPayments($limit, $where)
    {
        $prefix = config('database.prefix');
        try {
            $res = $this->field($prefix . 'payment.*' )->where($where)
            ->order('id,status', 'desc')->paginate($limit);
        }catch (\Exception $e) {
            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $res, 'ok');
    }

    /**
     * 增加支付接口
     * @param $merchant
     * @return array
     */
    public function addPayment($payment)
    {
        try {

            $has = $this->where('payment_name', $payment['payment_name'])->findOrEmpty()->toArray();
            if(!empty($has)) {
                return modelReMsg(-2, '', '支付接口名已经存在');
            }

            $this->insert($payment);
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '添加支付接口成功');
    }

    /**
     * 获取支付方式接口信息
     * @param $id
     * @return array
     */
    public function getPaymentById($id)
    {
        try {

            $info = $this->where('id', $id)->findOrEmpty()->toArray();
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 编辑支付接口
     * @param $payment
     * @return array
     */
    public function editPayment($payment)
    {
        try {
            $payment['update_time'] = time();
            $has = $this->where('payment_name', $payment['payment_name'])->where('id', '<>', $payment['id'])
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return modelReMsg(-2, '', '支付方式名已经存在');
            }

            $this->save($payment, ['id' => $payment['id']]);
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '编辑支付方式成功');
    }

    /**
     * 删除支付接口
     * @param $id
     * @return array
     */
    public function delPayment($id)
    {
        try {
//            if (1 == ayment) {
//                return modelReMsg(-2, '', '测试支付接口不可删除');
//            }

            $this->where('id', $id)->delete();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '删除成功');
    }

    /**
     * 获取所有的支付方式
     * @return array
     */
    public function getAllPayments()
    {
        try {

            $res = $this->select()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, [], $e->getMessage());
        }

        return modelReMsg(0, $res, 'ok');
    }
    /**
     * 获取支付接口信息
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
    public function updateAdminInfoById($id, $param)
    {
        try {

            $this->where('admin_id', $id)->update($param);
        } catch (\Exception $e) {

        }
    }

    /**
     * 根据角色id 获取支付接口信息
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