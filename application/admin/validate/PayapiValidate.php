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

class PayapiValidate extends Validate
{
    protected $rule =   [
        'api_name'  => 'require',
        'api_sign'   => 'require',
        'payment'   => 'require',
    ];

    protected $message  =   [
        'api_name.require' => '接口名称不能为空',
        'api_sign.require'   => '接口标识不能为空',
        'payment.require'   => '接口方式标识不能为空'
    ];
}