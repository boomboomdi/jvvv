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

class PrepareValidate extends Validate
{
    protected $rule =   [
        'order_amount'  => 'require|integer',
        'prepare_num'   => 'require|integer',
        'status' => 'require'
    ];

    protected $message  =   [
        'order_amount.require' => '金额不能为空',
        'order_amount.integer' => '金额请输入整型',
        'prepare_num.require'   => '预拉单数不能为空',
        'prepare_num.integer'   => '预拉单数为整型',
    ];

}