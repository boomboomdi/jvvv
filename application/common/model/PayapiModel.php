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
use think\facade\Log;
use think\Model;
use app\common\model\Jdpay;
use app\common\model\DeviceModel;

class PayapiModel extends Model
{
    protected $table = 'bsa_payapi';


    /**
     * 获取收款链接
     * @return void
     */
    public function getQrUrl()
    {



    }

    /**
     * 获取支付接口
     * @param $where array  payment
     * @param $getPayUrlData   array $getPayUrlData['amount']
     * @return array|void
     */
    public function getPayApisForMerchant($where, $getPayUrlData)
    {
        $prefix = config('database.prefix');
//        try {

        $where['status'] = 1;
        $apiData = $this->field($prefix . 'payapi.*')->where($where)->order('api_weights desc')->find();
        if (empty($apiData)) {
            return modelReMsg(-1, '', "NO PAY API");
        }
        //接口数据更新
        Db::table("bsa_payapi")->where("api_sign", $apiData['api_sign'])->update(
            ['order_number' => Db::raw("order_number +1")]
        );
        //请求支付接口 P_JD_OIL_WX
        if ($apiData['api_sign'] == "P_JD_OIL_WX") {
            $orderModel = new Jdpay();
            $getOrderUrl = $orderModel->createOrder($getPayUrlData, $apiData);
            //下单失败
            if ($getOrderUrl['code'] != 200) {
                Db::table("bsa_payapi")->where("api_sign", $apiData['api_sign'])->update(
                    ['create_fail_number' => Db::raw("create_fail_number +1")]
                );
                return modelReMsg(-1, '', "create order fail P_JD_OIL_WX");
            }
            //"data": { "trade_no": "13932323243", "pay_url": "https://www.baidu.com"
            $getOrderUrlResData = json_decode($getPayUrlData['data']);
            $returnData['order_pay'] = $getOrderUrlResData['trade_no'];
            $returnData['qr_url'] = $getOrderUrlResData['pay_url'];
            $returnData['api_sign'] = $apiData['api_sign'];
            return modelReMsg(0, $returnData, "create order success");
        }
//        } catch (\Exception $e) {
//            Log::error("/n/order create: /n" . json_encode($where) . json_encode($getPayUrlData), $getPayUrlData);
//            return modelReMsg(-1, '', $e->getMessage());
//        }
    }

    //如果存在apiSign
    public function getPayApiForOrder($apiSign = "")
    {

    }

}