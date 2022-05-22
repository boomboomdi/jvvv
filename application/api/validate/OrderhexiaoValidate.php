<?php

namespace app\api\validate;

use think\Validate;

class OrderhexiaoValidate extends Validate
{
    protected $rule = [
        'write_off_sign' => 'require|max:25',
//        'client_ip'  => 'require|ip',
        'order_no' => 'require|max:32',
        'order_type' => 'require|max:32',
        'account' => 'require|max:32',
        'operator' => 'require|max:32',
        'order_amount' => 'require|float',
        'limit_time' => 'require|integer|length:10',
        'notify_url' => 'require',
        'sign' => 'require|length:32',
    ];

    protected $message = [
        'write_off_sign.require' => 'require write_off_sign',
        'write_off_sign.max' => 'format error write_off_sign',
        'order_no.require' => 'require order_no',
        'order_no.max' => 'format error order_no',
        'order_type.require' => 'require order_type',
        'account.require' => 'require account',
        'account.max' => 'format error account',
        'operator.require' => 'require operator',
        'operator.max' => 'format error operator',
        'notify_url.require' => 'notify_url format error',
//        'notify_url.activeUrl' => 'notify_url format error',
        'total_amount.require' => 'require total_amount',
        'total_amount.float' => 'total_amount format float',
        'limit_time.require' => 'require limit_time',
        'limit_time.integer' => 'limit_time format error',
        'limit_time.length' => 'limit_time format length error',
        'sign.require' => 'require sign',
        'sign.length' => 'sign format error',
    ];
    protected $scene = [
        'uploadOrder' => ['write_off_sign', 'order_no', 'order_type', 'account', 'order_amount', 'notify_url', 'limit_time', 'sign'],
        'orderInfo' => ['write_off_sign', 'account', 'order_no']
    ];


}