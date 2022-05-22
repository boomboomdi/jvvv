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

class DeviceapiValidate extends Validate
{
    protected $rule = [
        'account' => 'require',
        'type' => 'require',
        'time' => 'require',
        'studio' => 'require',
        'device_desc' => 'require',
        'status' => 'require',
        'qr_img' => 'require'
    ];

    protected $message = [
        'account.require' => 'account.require',
        'type.require' => 'account.require',
        'time.require' => 'time.require',
        'studio.require' => 'studio.require',
        'device_desc.require' => 'device_desc.require',
        'status.require' => 'status.require',
        'qr_img' => 'require'
    ];

    protected $scene = [
        'ping'=>['account','studio','device_desc','status','time'],
        'upload' => ['account', 'studio','type','device_desc', 'qr_img','time']
    ];
}