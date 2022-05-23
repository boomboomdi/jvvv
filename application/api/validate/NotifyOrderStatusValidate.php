<?php

namespace app\api\validate;

use think\Validate;

class NotifyOrderStatusValidate extends Validate
{

    //后台订单号，官方订单号，金额，卡密账号，卡密密码，订单状态
    protected $rule = [
        'check_status' => 'require',      //查询状态  1 之外按查询失败处理
        'pay_status' => 'require',        //支付状态  1 之外按支付失败处理
//        'ck_status' => 'require',         //支付状态  1 之外按支付失败处理  暂时不用
        'time' => 'require|integer',      //时间|支付时间
        'order_me' => 'require',          //平台单号
        'order_pay' => 'require',         //京东单号
        'amount' => 'require|float',      //卡密金额
        'card_name' => "require",         //卡密账号
        'card_password' => "require",     //卡密密码
    ];

    protected $message = [
        'check_status.require' => 'require check_status',
        'pay_status.require' => 'require pay_status',
//        'ck_status.require' => 'require ck_status',
        'time.require' => 'require time',
        'time.integer' => 'time format error ',
        'order_me.require' => 'require order_me',
        'order_pay.require' => 'require.order_pay',
        'amount.require' => 'require.amount',
        'amount.float' => 'amount format error ',
        'card_name.require' => 'require.card_name',
        'card_password.require' => 'require.card_password',
    ];

    protected $scene = [
        'notify' => ['check_status', 'pay_status', 'order_me', 'order_pay', 'amount', 'time'],
        'payNotify' => ['card_name', 'card_password']
    ];


}