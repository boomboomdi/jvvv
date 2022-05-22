<?php
/**
 * Created by PhpStorm.
 * User: bl
 * Email: bl@qq.com
 * Date: 2020/10/8
 * Time:  15:54
 */
namespace app\admin\validate;

use think\Validate;

class PaymentValidate extends Validate
{
    protected $rule =   [
        'payment_name'  => 'require',
        'payment_sign'   => 'require',
    ];

    protected $message  =   [
        'payment_name.require' => '支付方式名称不能为空',
        'payment_sign.require'   => '支付方式标识不能为空'
    ];
}