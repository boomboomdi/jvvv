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

class PayapiModel extends Model
{
    protected $table = 'bsa_payapi';

    /**
     * 获取支付接口
     * @param $limit
     * @param $where
     * @return array
     */
    public function getPayapis($limit, $where)
    {
        $prefix = config('database.prefix');
        try {
            $res = $this->field($prefix . 'payapi.*' )->where($where)
            ->order('api_name', 'desc')->paginate($limit);
        }catch (\Exception $e) {
            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $res, 'ok');
    }

    /**
     * 增加支付接口
     * @param $payapi
     * @return array
     */
    public function addPayapi($payapi)
    {
        try {

            $has = $this->where('api_name', $payapi['api_name'])->findOrEmpty()->toArray();
            if(!empty($has)) {
                return modelReMsg(-2, '', '支付接口名已经存在');
            }

            $this->insert($payapi);
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '添加支付接口成功');
    }

    /**
     * 获取支付接口信息
     * @param $id
     * @return array
     */
    public function getPayapiById($id)
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
     * @param $payapi
     * @return array
     */
    public function editPayapi($payapi)
    {
        try {
            $payment['update_time'] = time();
            $has = $this->where('api_name', $payapi['api_name'])->where('id', '<>', $payapi['id'])
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return modelReMsg(-2, '', '支付接口名已经存在');
            }

            $this->save($payapi, ['id' => $payapi['id']]);
        }catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '编辑支付接口成功');
    }

    /**
     * 删除支付接口
     * @param $id
     * @return array
     */
    public function delPayapi($id)
    {
        try {
            $this->where('id', $id)->delete();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '删除成功');
    }


    /**
     * 获取所有的支付通道
     * @return array
     */
    public function getAllPayApis()
    {
        try {

            $res = $this->where('status', 1)->select()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, [], $e->getMessage());
        }

        return modelReMsg(0, $res, 'ok');
    }
}