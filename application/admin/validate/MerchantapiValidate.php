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

class MerchantapiValidate extends Validate
{
    protected $rule =   [
        'merchant_name'  => 'require',
        'merchant_sign'   => 'require',
        'api_sign'   => 'require',
    ];

    protected $message  =   [
        'merchant_name.require' => '商户名称不能为空',
        'merchant_sign.require'   => '商户标识不能为空',
        'api_sign.require'   => '通道标识不能为空',
    ];
}