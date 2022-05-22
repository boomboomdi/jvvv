<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/9/29
 * Time: 10:39 PM
 */
namespace app\api\model;

use think\facade\Log;
use think\Model;
use think\facade\Request;

class OrderLog extends Model
{
    protected $table = 'bsa_order_log';

    /**
     * 写订单日志日志
     * @param $orderData
     * @param $status
     */
    public function writeCreateOrderLog($orderData, $status)
    {
        try {
            $this->insert([
                'merchant_sign' => $orderData['merchant_sign'],
                'login_ip' => request()->ip(),
                'merchant_area' => getLocationByIp(request()->ip()),
                'merchant_user_agent' => Request::header('user-agent'),
                'create_order_time' => date('Y-m-d H:i:s'),
                'order_desc' => $orderData['order_desc'],
                'create_order_status' => $status
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * 写订单日志日志
     * @param $orderData
     * @param $status
     */
    public function writeNotifyOrderLog($orderData, $status)
    {
        try {
            $this->insert([
                'merchant_id' => $orderData['merchant'],
                'login_ip' => request()->ip(),
                'merchant_area' => getLocationByIp(request()->ip()),
                'merchant_user_agent' => Request::header('user-agent'),
                'create_order_time' => date('Y-m-d H:i:s'),
                'order_desc' => $orderData['order_desc'],
                'create_order_status' => $status
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * 下单日志明细
     * @param $limit
     * @return array
     */
    public function createOrderList($limit)
    {
        try {

            $log = $this->order('create_log_id', 'desc')->paginate($limit);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $log, 'msg' => 'ok'];
    }
}