<?php
namespace app\api\validate;

use think\Validate;

class OrderaaValidate extends Validate
{
    protected $rule =   [
        'merchant_sign'  => 'require|max:25',
        'client_ip'  => 'require|ip',
        'order_no'   => 'require|length:32',
        'notify_url'   => 'require|url',
//        'order_pay'   => 'require|length:32',
        'payment' => 'require',
        'amount' => 'require|float',
        'actual_amount' => 'require|float',
//        'pay_time' => 'require|Length:11',
        'sign' => 'require|length:32',
    ];

    protected $message  =   [
        'merchant_sign.require' => 'require merchant_sign',
        'merchant_sign.max' => 'merchant_sign format error',
        'client_ip.require' => 'require client_ip',
        'client_ip.ip' => 'client_ip format error',
        'notify_url.require' => 'notify_url format error',
        'notify_url.url' => 'notify_url format error',
        'order_no.require'     => 'require order_no',
        'order_no.length'     => 'order_no format error',
//        'order_pay.require'     => 'require order_pay',
//        'order_pay.length'     => 'order_pay format error',
        'payment.require'   => 'require number',
        'amount.require'   => 'require amount',
        'amount.float'   => 'amount format float',
        'actual_amount.require'   => 'require actual_amount',
        'actual_amount.float'   => 'actual_amount format error',
//        'pay_time.require'   => 'require pay_time',
//        'pay_time.integer'   => 'pay_time format error',
        'sign.require' => 'require sign',
        'sign.length' => 'sign format error',
    ];

}