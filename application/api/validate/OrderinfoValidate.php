<?php

namespace app\api\validate;

use think\Validate;

class OrderinfoValidate extends Validate
{
    protected $rule = [
        'merchant_sign' => 'require|max:32',
        'order_no' => 'require|max:32',
        'payment' => 'require',
        'amount' => 'require|float',
        'notify_url' => 'require',
        'time' => 'require',
        'sign' => 'require|max:32',
    ];

    protected $message = [
        'merchant_sign.require' => 'require merchant_sign',
        'merchant_sign.max' => 'merchant_sign format error',
        'order_no.require' => 'require order_no',
        'order_no.max' => 'order_no format error',
        'payment.require' => 'require number',
        'notify_url.require' => 'require notify_url',
        'amount.require' => 'require amount',
        'amount.float' => 'amount format float',
        'time.require' => 'require time',
        'time.integer' => 'time format error',
        'sign.require' => 'require sign',
        'sign.max' => 'sign format error',
    ];
    protected $scene = [
        'notify' => ['merchant_sign', 'order_no', 'payment'],
    ];
}