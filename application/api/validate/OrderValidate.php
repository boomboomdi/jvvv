<?php
namespace app\api\validate;

use think\Validate;

class OrderValidate extends Validate
{
    protected $rule =   [
        'apiMerchantNo'  => 'require',
        'apiMerchantOrderNo'   => 'require',
        'apiMerchantOrderCardNo'   => 'require',
        'apiMerchantOrderAmount'   => 'require',
        'apiMerchantOrderType'   => 'require',
        'apiMerchantOrderNotifyUrl'   => 'require',
        'apiMerchantOrderDate'   => 'require',
//        'apiMerchantOrderExpireDate'   => 'require',
        'sign' => 'require'
    ];
//    protected $rule = [
//        ['apiMerchantNo','require|string', 'BankCardNo require!|BankCardNo must be string!'],   //渠道分配的API商户编号
//        ['apiMerchantOrderNo','require', 'apiMerchantOrderNo  require'],           //API商户唯一订单号
//        ['apiMerchantOrderCardNo','require', 'apiMerchantOrderCardNo require!'], //充值油卡号
//        ['apiMerchantOrderAmount','require|int', 'apiMerchantOrderAmount require!|apiMerchantOrderAmount must be int'], //充值金额(单位：元)
//        ['apiMerchantOrderType','require', 'apiMerchantOrderType require!'], //充值类型：（1001H5：石油卡；1002H5：石化卡）
//        ['apiMerchantOrderNotifyUrl','require', 'apiMerchantOrderNotifyUrl require!'], //异步回调地址（不需要转义符），接收充值结果
//        ['apiMerchantOrderDate','require|dateFormat', 'apiMerchantOrderDate require!|apiMerchantOrderDate must be datetime'], //订单时间（格式：yyyy-MM-dd hh:mm:ss
//        ['apiMerchantOrderExpireDate','require', 'apiMerchantOrderNotifyUrl require! but can be null'], //订单过期时间（格式：yyyy-MM-dd hh:mm:ss
//        ['sign','require', 'sign require!'], //签名
//
//    ];

    protected $message  =   [
        'apiMerchantNo.require' => 'apiMerchantNo require!',
        'apiMerchantOrderNo.require' => 'apiMerchantOrderNo require!',
        'apiMerchantOrderCardNo.require' => 'apiMerchantOrderCardNo require!',
        'apiMerchantOrderAmount.require' => 'apiMerchantOrderAmount require!',
//        'apiMerchantOrderAmount.int' => 'apiMerchantOrderAmount must be int!',
        'apiMerchantOrderType.require' => 'apiMerchantOrderType require!',
        'apiMerchantOrderNotifyUrl.require' => 'apiMerchantOrderNotifyUrl require!',
        'apiMerchantOrderDate.require' => 'apiMerchantOrderNotifyUrl require!',
//        'apiMerchantOrderDate.dateFormat' => 'apiMerchantOrderDate must be datetime!',
//        'apiMerchantOrderExpireDate.require'   => 'apiMerchantOrderNotifyUrl require! but can be null!',
        'sign.require'   => 'sign require!',
    ];

}