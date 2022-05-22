<?php

namespace app\common\model;

use think\Db;
use think\facade\Log;
use think\Model;
use think\Request;
use app\common\validate\JdmsyValidate;

class Jdpay extends Model
{
    protected $platformNotifyUrl = "http://43.154.106.82/api/orderinfo/notify";
    protected $notifyClientIP = '120.77.145.184';
    protected $clientId = 4;
    protected $apiSign = 'P_JD_OIL_WX';
//    protected $tradeType = 'jd_m_sy';
    protected $tradeType = 'jdsy';
    protected $notifyUrl = '/api/order/notify';
    protected $queryUrl = 'http://120.77.145.184:2345/api/orders/query';
    protected $clientSecret = 'c459444ec6c87712399f01fd4acc6150';
    protected $createUrl = 'http://120.77.145.184:2345/api/orders';

    /**
     * 通道下单
     * @param $getPayUrlData   接口请求参数
     * @param $apiData   接口参数(数据库)
     * @return mixed{ "code": 200, "message": "SUCCESS", "data": { "trade_no": "13932323243", "pay_url": "https://www.baidu.com" } }
     * @return mixed{ "code": 200, "message": "SUCCESS", "data": { "trade_no": "13932323243", "pay_url": "https://www.baidu.com" } }
     */
    public function createOrder($getPayUrlData, $apiData = "")
    {

//        try {
        $data = ["client_id" => $this->clientId,
            "out_trade_no" => $getPayUrlData['order_me'],
            "total_amount" => $getPayUrlData['amount'],
            "trade_type" => $this->tradeType,
            "random_str" => guid12(),
            "notify_url" => $this->platformNotifyUrl
        ];
        ksort($data);
        $sign = urldecode(http_build_query($data));
        $sign = md5($sign . "&key=" . $this->clientSecret);
        $data['sign'] = $sign;
        //$apiData['api_url']
        $notifyResult = json_decode(curlPost($this->createUrl, $data), true);

        Log::write($this->apiSign . "/n/order create: /n" . json_encode($data) . "result :/n/t" . json_encode($notifyResult), "info");
        var_dump($notifyResult);
        exit;

        if (!isset($notifyResult['code']) || $notifyResult['code'] != 200) {
            Log::write($this->apiSign . "/n/order create: /n/t" . json_encode($data) . "result :/n/t" . json_encode($notifyResult), "info");
        }
        return $notifyResult;

//        } catch (\Exception $e) {
//            Log::error($this->apiSign . "/n/createOrder error : /n" . json_encode($getPayUrlData), $e->getError());
//            return modelReMsg("-2", "", $e->getError());
//        }
    }

    /**
     * 查询订单
     * http://120.77.145.184:2345/api/orders/query
     * @return void
     */
    public function queryOrder($param)
    {
        $baseurl = request()->root(true);
        $data = ["client_id" => "4",
            "out_trade_no" => $param['order_me'],  //out_trade_no
            "total_amount" => "100.00",
            "trade_type" => "jd_m_sy",
            "random_str" => guid12(),
            "notify_url" => $baseurl . $this->notifyUrl
        ];
        ksort($data);
        $sign = urldecode(http_build_query($data));
        $sign = md5($sign . "&signature=" . $this->clientSecret);
        $data['signature'] = $sign;
        $queryResult = json_decode(curlPost($this->queryUrl, $data), true);

        Log::write($this->apiSign . "/n/t order query: /n" . json_encode($data), "info");

        if (!isset($queryResult['code']) || $queryResult['code'] != 200) {
            Log::write($this->apiSign . "/n/t order query: /n/t" . json_encode($data) . "result :/n/t" . json_encode($queryResult), "info");
        }
        return $queryResult;
    }

    //接受通道回调
    //client_id	1	必填	String	商户ID
    //out_trade_no	202104020057311	必填	String	商户订单号
    //trade_no	202104020057311	必填	String	我方订单号  通道
    //total_amount	100.00	必填	String	订单金额
    //random_str	mD7yIDuygHrc	必填	String	随机字符串
    //status	1	必填	Int	支付状态 1:已支付 0:未支付
    public function orderNotify()
    {
        try {
            $data = @file_get_contents('php://input');
            $message = json_decode($data, true);
            Log::info("/n/order notify log: /n" . json_encode($data), $message);
            $validate = new JdmsyValidate();
            if (!$validate->check($message)) {
                Log::info("/n/order notify log: /n" . json_encode($data), $validate->getError());

                return "param error!" . $validate->getError();
//                $returnMsg['code'] = 1001;
//                $returnMsg['msg'] = "param error!" . $validate->getError();
//                $returnMsg['data'] = date("Y-m-d h:i:s", time());
//                return json_encode($returnMsg);
            }
            //@todo
            //判断回调通道ip\

            $clientIp = Request()->ip();
            if ($clientIp != $this->notifyClientIP) {
                Log::info("/n/order notify error : /n" . json_encode($data), $clientIp . "/" . $this->notifyClientIP);
                return "param error! notify client error";
            }
            Log::write($this->apiSign . "/n/order notify: /n" . json_encode($data), "info");
            $where['order_pay'] = $data['trade_no'];
            $where['order_pay'] = $data['trade_no'];
            $orderData = Db::table("bsa_order")->where($where)->find();
            if (!empty($orderData) && $orderData['order_status'] == 1) {
                return "success";
            }
            $orderModel = new OrderModel();
            $orderModel->orderNotify($orderData);
            return "success";
        } catch (\Exception $e) {
            Log::error("/n/order notify error : /n" . json_encode($data), $e->getError());
            return "param error:1009!" . $e->getError();
        }
    }

}