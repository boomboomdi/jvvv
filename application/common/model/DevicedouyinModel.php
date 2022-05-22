<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/3/17
 * Time: 4:48 PM
 */

namespace app\common\model;

use think\Db;
use think\Model;

//**
class DevicedouyinModel extends Model
{
    protected $table = 'bsa_device';

    /**
     * 获取管理员
     * @param $limit
     * @param $where
     * @return array
     */
    public function getDevices($limit, $where)
    {
        $prefix = config('database.prefix');

        try {
            $res = $this->field($prefix . 'device.*')
                ->where($where)
                ->order('device_status asc,status asc')->paginate($limit);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $res, 'ok');
    }

    /**
     * 获取收款设备信息
     * @param $deviceId
     * @return array
     */
    public function getDeviceById($deviceId)
    {
        try {
            $info = $this->where('id', $deviceId)->findOrEmpty()->toArray();
        } catch (\Exception $e) {
            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 更新收款账户收款码
     * @param $device
     * @return array
     */
    public function devicePing($where, $device)
    {
        try {
//            $has = $this->where('account', $where['account'])->where('studio', '<>', $device['studio'])
//                ->findOrEmpty()->toArray();
//            if (!empty($has)) {
//                return modelReMsg(-2, '', '收款账户已经存在');
//            }
            $res = $this->save($device, $where);
            if (!$res) {
                return modelReMsg(-3, '', '心跳处理失败！');
            }
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, '', '心跳成功!');
    }

    /**
     * 更新收款账户收款码
     * @param $device
     * @return array
     */
    public function updateDeviceQrUrl($device)
    {
        try {
            $has = $this->where('account', $device['account'])->where('studio', '<>', $device['studio'])
                ->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '收款账户已经存在');
            }

            $this->save($device, ['account' => $device['account']]);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, '', '更新收款码成功!');
    }

    /**
     * 更新收款账户状态
     * @param $where
     * @param $update
     * @return array
     */
    public function updateDeviceStatus($where, $update)
    {
        try {
//
//            if (!empty($has)) {
//                return modelReMsg(-2, '', '收款账户已经存在');
//            }

            $this->save($update, $where);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, '', '更新收款码成功!');
    }

    /**
     * 编辑收款账户
     * @param $admin
     * @return array
     */
    public function editDevice($device)
    {
        try {
            $has = $this->where('account', $device['account'])->where('id', '<>', $device['id'])
                ->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '收款账户已经存在');
            }

            $this->save($device, ['id' => $device['id']]);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '编辑收款账户成功');
    }


    /**
     * 增加账号/设备
     * @param $admin
     * @return array
     */
    public function addDevice($device)
    {
        try {

            $has = $this->where('account', $device['account'])->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '此账号已经存在');
            }

            $this->insert($device);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '添加账号/设备成功');
    }

    /**
     * 删除账号
     * @param $adminId
     * @return array
     */
    public function delDevice($id)
    {
        try {
            return modelReMsg(-2, '', '删除功能暂时关闭！');

//            if (1 == $adminId) {
//                return modelReMsg(-2, '', 'admin管理员不可删除');
//            }

//            $this->where('admin_id', $adminId)->delete();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '删除成功');
    }

    /**
     * 获取下单设备  时间/金额 无重复
     * @param $orderData
     * @return array
     */
    public function getZfbUseDevice($orderData)
    {
        try {
            $studio = Db::table("bsa_studio")
//                ->field("studio")
                ->where("status", "=", "1")->column('studio');
//                var_dump($studio);exit;
//            $studio = collection($studio)->toArray();
            $info = $this
                ->where([
                    "status" => 1,
                    "device_status" => 1,
                    "order_status" => 1,
                ])->where("studio", "in", $studio)
                ->order("lock_time asc")->find();
//            var_dump($info);
//            exit;
            if (empty($info)) {
                return modelReMsg(-2, '', "");
            }
            $update['order_status'] = 2;
            $update['lock_time'] = time();
            $this->where(['account' => $info['account']])->update($update);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage() . $e->getFile() . $e->getLine());
        }

        return modelReMsg(0, $info, 'ok');
    }


    /**
     * 获取管理员信息
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
     * 根据角色id 获取管理员信息
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