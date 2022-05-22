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

class CookieValidate extends Validate
{
    protected $rule =   [
        'cookie_sign'  => 'require',
        'cookie_contents'  => 'require',
    ];

    protected $message  =   [
        'cookie_sign.require' => 'cookie商不能为空',
        'cookie_contents.require' => 'cookie不能为空',
    ];

}