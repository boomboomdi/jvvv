<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/10/8
 * Time:  15:54
 */
namespace app\admin\validate;

use think\Validate;

class DeviceValidate extends Validate
{
    protected $rule =   [
        'account'  => 'require',
        'account_password'  => 'require',
        'studio'   => 'require',
        'thumbnail'   => 'require',
        'device_desc' => 'require',
        'status' => 'require'
    ];

    protected $message  =   [
        'account.require' => '收款账户不能为空',
        'account_password.require' => '账户密码不能为空',
        'studio.require'   => '工作室不能为空',
        'thumbnail.require'   => '二维码不能为空',
        'device_desc.require'   => '描述不能为空',
        'status.require'   => '状态不能为空'
    ];

    protected $scene = [
        'edit'  =>  ['account']
    ];
}