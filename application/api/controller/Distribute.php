<?php
/**
 * Created by PhpStorm.
 * User: xh
 * Date: 2019/1/5
 * Time: 16:58
 */
namespace app\api\controller;

use app\common\Redis;
use think\Db;
use think\Request;

class Distribute extends Base {
    public function index(Request $request){
        $index = $request->param('index');
        $redis = new Redis(['index'=>$index]);
        $one = $redis->rpop('Ti_order');
        $message = json_decode($one,true);
        if(!$message){
            exit('Distribute:不存在缓存数据');
        }

        $table_name = 'ali001_order';
//        $this->checkTable($table_name);
//        $check['order_no'] = $message['order_no'];
//        $check['channel'] = $message['channel'];
        $message['add_time'] = $message['time'];
        unset($message['time']);
        unset($message['sig']);
        Db::name($table_name)->insert($message);
//        Db::name('record')->insert($check);
        exit('Distribute:订单入库成功!');
    }

    function checkTable($table){
        $exist = Db::query('show tables like "'.$table.'"');
        if(!$exist){
            $sql = <<<sql
                CREATE TABLE `$table` (
                  `id` int(1) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
                  `merchant_id` varchar(11) DEFAULT NULL COMMENT '商户id',
                  `amount` decimal(11,2) DEFAULT NULL COMMENT '订单金额',
                  `payable_amount` decimal(10,2) DEFAULT NULL COMMENT '应付金额',
                  `actual_amount` decimal(10,2) DEFAULT NULL COMMENT '实际付款金额',
                  `order_no` varchar(255) DEFAULT NULL COMMENT '商户订单号',
                  `orderme` varchar(255) DEFAULT NULL COMMENT '自定订单号',
                  `subject` varchar(255) DEFAULT NULL COMMENT '为空 待定',
                  `info` varchar(255) DEFAULT NULL COMMENT '玩家信息 可为空',
                  `add_time` int(20) DEFAULT NULL COMMENT '订单创建时间',
                  `order_status` tinyint(1) DEFAULT '0' COMMENT '订单状态 1:已付款 2：付款失败3：用户取消0：未付款4：超时未统计',
                  `notify_url` varchar(255) DEFAULT NULL COMMENT '回调地址',
                  `payment` varchar(255) DEFAULT NULL COMMENT '付款方式',
                  `account` varchar(255) DEFAULT NULL COMMENT '收款账号 （ali/wechat）',
                  `payerusername` varchar(255) DEFAULT NULL COMMENT '付款姓名',
                  `payerloginid` varchar(255) DEFAULT NULL COMMENT '收款人登陆id',
                  `payeruserid` varchar(255) DEFAULT NULL COMMENT '付款人id',
                  `payersessionid` varchar(255) DEFAULT NULL COMMENT '付款人session id',
                  `time_update` int(30) DEFAULT NULL COMMENT '最后修改时间',
                  `qr_url` varchar(255) DEFAULT NULL COMMENT '付款地址',
                  `ali_order` varchar(255) DEFAULT NULL COMMENT '支付宝订单号',
                  `msgid` varchar(255) DEFAULT NULL COMMENT '信息id',
                  `card` varchar(255) DEFAULT NULL COMMENT '银行卡号',
                  `channel` varchar(20) DEFAULT NULL COMMENT '渠道标识',
                  `userId` varchar(50) DEFAULT NULL COMMENT '收款账号识别id',
                  PRIMARY KEY (`id`),
                  KEY `idx_payersessionid` (`payersessionid`(191)),
                  KEY `idx_account` (`account`(191)),
                  KEY `idx_order_no` (`order_no`(191))
                ) ENGINE=InnoDB AUTO_INCREMENT=10113 DEFAULT CHARSET=utf8mb4
sql;
            dump(Db::execute($sql));
        }

    }
}