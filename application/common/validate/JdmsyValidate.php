<?php
namespace app\common\validate;

use think\Validate;

class JdmsyValidate extends Validate
{
    protected $rule =   [
        'client_id'  => 'require',
        'out_trade_no'   => 'require',
        'trade_no'   => 'require',
        'total_amount'   => 'require',
        'random_str'   => 'require',
        'status'   => 'require',
//        'sign' => 'require'
    ];

    protected $message  =   [
        'client_id.require' => 'apiMerchantNo require!',
        'out_trade_no.require' => 'apiMerchantOrderNo require!',
        'trade_no.require' => 'apiMerchantOrderCardNo require!',
        'total_amount.require' => 'apiMerchantOrderAmount require!',
        'random_str.require' => 'apiMerchantOrderNotifyUrl require!',
        'status.require' => 'apiMerchantOrderNotifyUrl require!',
//        'sign.require'   => 'sign require!',
    ];

}