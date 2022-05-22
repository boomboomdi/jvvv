<?php
/**
 * Created by PhpStorm.
 * User: xh
 * Date: 2019/1/5
 * Time: 15:47
 */
namespace app\api\controller;

use app\common\Redis;
use think\Db;
use think\exception\ErrorException;
use think\Request;
use \GatewayWorker\Lib\Gateway;

class Order extends Base{


    //接收商户数据接口
    public function index(Request $request){
        restore_error_handler();
        $message = $request->param();
        //echo 'testok';
        //exit();
        if(!isset($message['merchant_id']) || empty($message['merchant_id'])){
            $returnData['code'] = '100001';
            $returnData['msg'] = '';//'缺少必要参数:merchant_id';
            $returnData['data'] = '';
            return json_encode($returnData);
        }
        if(!isset($message['order_no']) ||empty($message['order_no'])){
            $returnData['code'] = '100002';
            $returnData['msg'] = '';//'缺少必要参数:order_no';
            $returnData['data'] = '';
            return json_encode($returnData);
        }
        if(!isset($message['amount']) ||empty($message['amount'])){
            $returnData['code'] = '100003';
            $returnData['msg'] = '';//'缺少必要参数:amount';
            $returnData['data'] = '';
            return json_encode($returnData);
        }
        if(!isset($message['notify_url']) ||empty($message['notify_url'])){
            $returnData['code'] = '100004';
            $returnData['msg'] = '';//'缺少必要参数:notify_url';
            $returnData['data'] = '';
            return json_encode($returnData);
        }
        if(!isset($message['time']) ||empty($message['time'])){
            $returnData['code'] = '100005';
            $returnData['msg'] = '';//'缺少必要参数:time';
            $returnData['data'] = '';
            return json_encode($returnData);
        }
        if(!isset($message['sig']) ||empty($message['sig'])){
            $returnData['code'] = '100006';
            $returnData['msg'] = '';//'缺少必要参数:sig';
            $returnData['data'] = '';
            return json_encode($returnData);
        }
        logMessage( json_encode([ 'message'=>$message],512), 'v4_order_in');
        $merchant_id = $message['merchant_id']; //商户id
        $order_no = $message['order_no'];  //订单id  （商户）
        $amount = $message['amount'];  //支付金额
        $time = $message['time'];  //时间
        $sig = $message['sig'];  //签名;
        $notify_url=$message['notify_url'];

        $redis = new Redis();

        //获取商户信息
        //$tstart=microtime(true);
        $merchantData=getMerchantById($redis, $merchant_id);
        //var_dump(microtime(true)-$tstart);die();
        $merchantToken = isset($merchantData['token'])?$merchantData['token']:'';
        if(empty($merchantToken)){
            $returnData['code'] = '100007';
            $returnData['msg'] = '';//'商户不存在:merchant_id';
            $returnData['data'] = '';
            return json_encode($returnData);
        }
        $shid_userid=@$message['shid_userid']; if(is_null($shid_userid)) $shid_userid='';
        $receiver_ali_lids=@$message['receiver_ali_lids']; if(is_null($receiver_ali_lids)) $receiver_ali_lids='';
        $user_ali_uids=@$message['user_ali_uids']; if(is_null($user_ali_uids)) $user_ali_uids='';
        //验证签名
        $byOurSign = md5($merchant_id.$merchantToken.$order_no.$amount.$time.$shid_userid.$receiver_ali_lids.$user_ali_uids);
        if($sig!=$byOurSign){
            $returnData['code'] = '100008';
            $returnData['msg'] = '';//'签名失败';
            $returnData['data'] = '';
            return json_encode($returnData);
        }

        if(isset($message['amount'])) $message['amount']=sprintf('%.2f', floatval($message['amount']));
        if(isset($message['payable_amount'])) $message['payable_amount']=sprintf('%.2f', floatval($message['payable_amount']));

        $money=$message['amount']; $order_no=$message['order_no']; $order_no=substr($order_no, 3);
        $order_data=[];
        $insertOk=false; $inserted=0; $errmsg='创建订单失败!!';

        ////lock begin
        $lockfile=fopen("/home/wwwroot/shoukuan2/application/api/controller/Order.lock","r");

        $channel=$merchant_id;

        $device = getOneDeviceV2($redis, $channel,$payable_amount,'');
//        $ali=\getOneNewDevice($redis, $channel, $payable_amount);
////        var_dump($ali);exit;
        if(!$device){
            return apiJsonReturn('100006', "设备不足");
        }
        try{
            if($lockfile===false) throw Exception('open lockfile fail');
            if(!flock($lockfile,LOCK_EX)) throw Exception('lock lockfile fail');
            $channel=$merchant_id;
            $amount=$message['amount'];
            $payable_amount=$amount;
            $slids=$receiver_ali_lids;
            $used_lids=[]; if($slids!='') $used_lids=explode(',', $slids);
//            $ali=\getOneDeviceV2($redis, $channel, $payable_amount, $used_lids);

            $payerusername=null; if(isset($message['payname'])) $payerusername=$message['payname'];

            $order_data=array(
                'merchant_id'=>$merchant_id, 'amount'=>$amount, 'payable_amount'=>$payable_amount,
                'channel'=>$merchant_id, 'order_no'=>$order_no,
                'notify_url'=>$notify_url,
                'account'=>"",
                'add_time'=>$time, 'time_update'=>0,
                'payerusername'=>$payerusername,
                'shid_userid'=>$shid_userid,
            );

            $table="shoukuan.s_order";
            $db=new \think\Db;
            try{
                $inserted=$db::table($table)->insert($order_data);
                $lastSql = $db::table($table)->getLastSql();
                $insertOk=true;
            }catch(\Exception $ex){
                $msg=$ex->getMessage(). " ".$ex->getFile()." ".$ex->getLine();
                logMessage( json_encode([ 'message'=>$message,'order_data'=>$order_data, 'msg'=>$msg,],512), 'v3_order_insert_exception');
            }catch(\Error $er){
                $msg=$er->getMessage(). " ".$er->getFile()." ".$er->getLine();
                logMessage( json_encode([ 'message'=>$message,'order_data'=>$order_data, 'msg'=>$msg,],512), 'v3_order_insert_error');
            }

        }catch(\Exception $ex){
            $msg=$ex->getMessage(). " ".$ex->getFile()." ".$ex->getLine();
            logMessage( json_encode([ 'message'=>$message,'order_data'=>$order_data, 'msg'=>$msg,],512), 'v3_Order_Exception');
        }catch(\Error $er){
            $msg=$er->getMessage(). " ".$er->getFile()." ".$er->getLine();
            logMessage( json_encode([ 'message'=>$message,'order_data'=>$order_data, 'msg'=>$msg,],512), 'v3_Order_Error');
        }

        if($lockfile){
            flock($lockfile,LOCK_UN);
            fclose($lockfile);
        }
        ////lock end

        if(!$inserted){
            logMessage( json_encode([ 'message'=>$message,'order_data'=>$order_data, 'device'=>$device, 'inserted'=>$inserted, 'lastsql'=>Db::getLastSql(), ],512), 'v3_order_insert_fail');
            return json_encode(code_msg_data('100001', $errmsg, '' ));
        }
        if(!$insertOk){
            logMessage( json_encode([ 'message'=>$message,'order_data'=>$order_data, 'device'=>$device, 'insertOk'=>$insertOk, 'lastsql'=>Db::getLastSql(), ],512), 'v3_order_insert_fail');
            return json_encode(code_msg_data('100001', $errmsg, '' ));
        }

        $redis->set('order|'.$order_no, json_encode($order_data), 30*60*60); $redis->set('state|order|active|'.$order_no,1, 15*60 );

        $time=time(); $sign=generate_order_no_sign($order_no, $time );

        $baseurl = request()->root(true).'/api/pay2'; $orderUrl=$baseurl."/first?order_no={$order_no}&sign={$sign}&t={$time}";

        $ret=code_msg_data('100000','订单创建成功:SUCCESS', $orderUrl);
//        $ret['payable_amount']=$order_data['payable_amount'];

        logMessage( json_encode(['ip'=>request()->ip(),'message'=>$message, 'order_data'=>$order_data, 'device'=>$device, 'ret'=>$ret,'inserted'=>$inserted, ],512), 'v3_CreateOrder_out');
        return json_encode($ret);

    }

    /**
     * 回调发送消息
     * @param Request $request
     * @return bool
     */
    public function sendPayBackMessage(Request $request)
    {
        $message = $request->param();
        try{
            if(!isset($message['action'])){
                return apiJsonReturn('10001','发送失败');
            }
            if(!isset($message['account'])||empty($message['account'])){
                return apiJsonReturn('10002','发送失败');
            }
            if(!isset($message['userid'])||empty($message['userid'])){
                return apiJsonReturn('10003','发送失败');
            }
            if(!isset($message['msg_id'])){
                return apiJsonReturn('10004','发送失败');
            }
            if(!isset($message['message_data'])){
                return apiJsonReturn('10005','发送失败');
            }
            //查找client_id
            logMessage(json_encode(['message' => $message], 512), 'sendPayBackMessage_log');
            $toClientId = Db::table('shoukuan.s_ali_device')->where('account', '=', $message['account'])->find()['client_id'];
            if(empty($toClientId)){
                logMessage(json_encode(['message' => $message], 512), 'sendPayBackMessage_fail');
                return apiJsonReturn('10006','发送失败');
            }else{
                Gateway::sendToClient($toClientId, json_encode($message));
                return apiJsonReturn('10000','已发送');
            }
        }catch (\exception $exception){
            logMessage(json_encode(['message' => $message,'exception'=>$exception->getMessage()], 512), 'sendPayBackMessage_exception');
            return apiJsonReturn('10099','发送异常！');
        }catch (ErrorException $errorException){
            logMessage(json_encode(['message' => $message,'exception'=>$errorException->getMessage()], 512), 'sendPayBackMessage_ErrorException');
            return apiJsonReturn('10099','发送错误！');
        }
    }

}


