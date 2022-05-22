<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/10/8
 * Time:  15:54
 */

namespace app\api\validate;

use think\Validate;

class NotifylogValidate extends Validate
{
    protected $rule = [
        'account' => 'require',
        'amount' => 'require',
        'pay_time' => 'require',
//        'pay_name' => 'require',
        'order_pay' => 'require',
//        'notify_log_desc' => 'require',
        'client_id' => 'require',
//        'studio' => 'require',
    ];

    protected $message = [
        'account.require' => 'account.require',
        'amount.require' => 'amount.require',
//        'pay_name.require' => 'pay_name.require',
        'time.require' => 'time.require',
        'client_id.require' => 'client_id.require',
        'order_pay.require' => 'order_pay.require',
//        'studio.require' => 'studio.require',
    ];

//    protected $scene = [];
}