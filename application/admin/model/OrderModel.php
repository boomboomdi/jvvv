<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/3/17
 * Time: 4:48 PM
 */

namespace app\admin\model;

use think\Db;
use think\Model;

class OrderModel extends Model
{
    protected $table = 'bsa_order';

    /**
     * 获取订单
     * @param $limit
     * @param $where
     * @return array
     */
    public function getOrders($limit, $where)
    {
        $prefix = config('database.prefix');

        try {
            $res = $this->field($prefix . 'order.*')
                ->where($where)
                ->order('id', 'desc')
                ->paginate($limit);

        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $res, 'ok');
    }

    /**
     * 获取统计数据
     * @param $limit
     * @param $where
     * @return array
     */
    public function getStatistics($limit, $where)
    {
        $prefix = config('database.prefix');

        try {
            $res = $this->field($prefix . 'order.*')
                ->where($where)
                ->order('id', 'desc')
                ->paginate($limit);

        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $res, 'ok');
    }

    /**
     * 获取订单信息
     * @param $OrderId
     * @return array
     */
    public function getOrderById($OrderId)
    {
        try {

            $info = $this->where('order_no', $OrderId)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 编辑订单
     * @param $Order
     * @return array
     */
    public function editOrder($Order)
    {
        try {

            $has = $this->where('Order_name', $Order['Order_name'])->where('order_no', '<>', $Order['order_no'])
                ->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '订单名已经存在');
            }

            $this->save($Order, ['order_no' => $Order['order_no']]);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '编辑订单成功');
    }

    /**
     * 删除订单
     * @param $OrderId
     * @return array
     */
    public function delOrder($OrderId)
    {
        try {
            if (1 == $OrderId) {
                return modelReMsg(-2, '', '测试订单不可删除');
            }

            $this->where('OrderId', $OrderId)->delete();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '删除成功');
    }

    /**
     * 获取订单信息
     * @param $merchantSign
     * @return array
     */
    public function getAllOrderNumberByMerchantSign($merchantSign, $startTime = "", $endTime = "")
    {
        try {
            $where = [];
            if (!empty($startTime)) {
                $where['add_time'] = ['>', $startTime];
            }
            if (!empty($endTime)) {
                $where['add_time'] = ['<', $endTime];
            }
            $info = $this->where('merchant_sign', $merchantSign)->where($where)->count();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 获取商户成功订单金额
     * @param $merchantSign
     * @return array
     */
    public function getAllOrderTotalAmountByMerchantSign($merchantSign, $startTime = "", $endTime = "")
    {
        try {
            $where = [];
            if (!empty($startTime)) {
                $where['add_time'] = ['>', $startTime];
            }
            if (!empty($endTime)) {
                $where['add_time'] = ['<', $endTime];
            }
            $where['merchant_sign'] = $merchantSign;
            $info = 0;
            $handTotalAmount = $this->field('sum(actual_amount) as totalAmount')->where($where)->where("order_status", 5)->find();
            if ($handTotalAmount) {
                $info = $info + $handTotalAmount['totalAmount'];
            }
            $where['order_status'] = 1;
            $totalAmount = $this->field('sum(actual_amount) as totalAmount')->where($where)->find();
//
            if ($totalAmount) {
                $info = $info + $totalAmount['totalAmount'];
            }

        } catch (\Exception $e) {

            return modelReMsg(-1, 0, $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 获取商户成功订单数量
     * @param $merchantSign
     * @return array
     */
    public function getAllOrderSuccessNumberByMerchantSign($merchantSign, $startTime = "", $endTime = "")
    {
        try {
            $where = [];
            if (!empty($startTime)) {
                $where['add_time'] = ['>', $startTime];
            }
            if (!empty($endTime)) {
                $where['add_time'] = ['<', $endTime];
            }
            $info = 0;
            $where['merchant_sign'] = $merchantSign;
            $handNum = $this->where($where)->where("status", 5)->count();
            $info = $info + $handNum;
            //回调中的
            $postingNum = $this->where($where)->where("status", 6)->count();
            $info = $info + $postingNum;

            $where['order_status'] = 1;
            if (!empty($this->where($where)->count()) || is_int($this->where($where)->count())) {
                $info = $info + $this->where($where)->count();
            }
        } catch (\Exception $e) {
            return modelReMsg(-1, 0, $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 获取订单信息
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
     * 根据角色id 获取订单信息
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