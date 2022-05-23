<?php

namespace app\api\validate;

use think\Validate;

class NotifyjdurlValidate extends Validate
{

    //预拉订单号，官方订单号，金额，支付链接，
    protected $rule = [
        'prepare_status' => 'require',   //预拉结果  1 之外按预拉失败处理
        'ck_status' => 'require',        //ck_status  1 之外按停用处理
        'order_me' => 'require',         //平台单号
        'order_pay' => 'require',        //京东单号
        'amount' => "require",           //订单金额   不要小数点元
        'qr_url' => "require|activeUrl",  //支付链接  activeUrl
    ];

    protected $message = [
        'prepare_status.require' => 'require prepare_status',
        'ck_status.require' => 'require ck_status',
        'order_me.require' => 'require order_me',
        'order_pay.require' => 'require.order_pay',
        'amount.require' => 'require.amount',
        'qr_url.require' => 'require.qr_url',
        'qr_url.activeUrl' => 'qr_url format error',
    ];


}