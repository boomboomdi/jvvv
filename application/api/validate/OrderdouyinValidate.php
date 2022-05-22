<?php
namespace app\api\validate;

use think\Validate;

class OrderdouyinValidate extends Validate
{
    protected $rule =   [
        'write_off_sign'  => 'require|max:25',
//        'client_ip'  => 'require|ip',
        'order_no'   => 'require|max:32',
        'account'   => 'require|max:32',
        'total_amount' => 'require|float',
        'limit_time' => 'require',
        'notify_url' => 'require',
        'sign' => 'require|length:32',
    ];

    protected $message  =   [
        'write_off_sign.require' => 'require write_off_sign',
        'write_off_sign.max' => 'format error write_off_sign',
        'order_no.require' => 'require order_no',
        'order_no.max' => 'format error order_no',
        'account.require' => 'require account',
        'account.max' => 'format error account',
//        'client_ip.require' => 'require client_ip',
//        'client_ip.ip' => 'client_ip format error',
        'notify_url.require' => 'notify_url format error',
//        'notify_url.url' => 'notify_url format error',
        'payment.require'   => 'require number',
        'total_amount.require'   => 'require total_amount',
        'total_amount.float'   => 'total_amount format float',
        'limit_time.require'   => 'require limit_time',
//        'limit_time.time'   => 'limit_time format error',
        'sign.require' => 'require sign',
        'sign.length' => 'sign format error',
    ];
    protected $scene = [
        'ping'=>['account','studio','device_desc','status','time'],
        'order_info'=>['write_off_sign','account','order_no']
    ];



}