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

class StudioValidate extends Validate
{
    protected $rule =   [
        'studio_name'  => 'require',
        'studio'   => 'require',
        'username' => 'require',
        'status' => 'require'
    ];

    protected $message  =   [
        'studio_name.require' => '工作室名称不能为空',
        'studio.require'   => '管理员标识不能为空',
        'username.require'   => '登录账号不能为空',
        'status.require'   => '状态不能为空'
    ];

    protected $scene = [
        'edit'  =>  ['studio_name', 'username','studio', 'status']
    ];
}