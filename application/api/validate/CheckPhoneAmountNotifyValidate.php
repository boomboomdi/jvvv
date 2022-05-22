<?php

namespace app\api\validate;

use think\Validate;

class CheckPhoneAmountNotifyValidate extends Validate
{

    protected $rule = [
        'phone' => 'require',
        'order_no' => 'require',
        'amount' => "require",
        'check_status' => "require|integer",
    ];

    protected $message = [
        'phone.require' => 'phone require',
        'order_no.require' => 'order_no.require',
        'amount.require' => 'amount.require',
        'check_status.require' => 'check_status.require',
        'check_status.integer' => 'check_status格式有误！'
    ];
//    protected $scene = [
//
//    ];


}