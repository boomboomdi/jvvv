<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'app\shell\Prepareorder',  //预拉单
    'app\shell\Timecheckorder',  //查单订单表等待付款订单
//    'app\shell\Timenotifyhx',  //查本地未回调且已支付支付核销单，回调核销
    'app\shell\Timenotifyorder',  //查本地未回调且已支付支付订单单，回调商户
//    'app\shell\Timeoutorder',  //超时订单修改订单状态
//    'app\shell\Timerestarthxorder',  //解冻核销单，以重新使用
//    'app\shell\Notifynopayhx',  //定时回调核销 支付失败
    'app\shell\Timecheckcookie',  //定时检查支付失败次数过多得ck
];
