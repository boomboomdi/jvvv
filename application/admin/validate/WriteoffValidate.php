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

class WriteoffValidate extends Validate
{
    protected $rule =   [
        'write_off_sign'  => 'require',
//        'write_off_username'  => 'require',
        'token'   => 'require',
    ];

    protected $message  =   [
        'write_off_sign.require' => '请输入核销商标识',
//        'write_off_username.require'   => '请输入后台对应登录名',
        'token.require'   => '协议密钥不能为空',
//        'merchant_password.require'   => '商户密码不能为空',
//        'merchant_validate_password.require'   => '商户验证密码不能为空',
    ];
}