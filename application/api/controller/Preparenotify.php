<?php

namespace app\api\controller;


use app\admin\model\CookieModel;
use app\common\Redis;
use think\Controller;
use think\Db;
use think\Request;
use Zxing\QrReader;
use app\api\validate\NotifyjdurlValidate;
use app\common\model\OrderprepareModel;

class Preparenotify extends Controller
{
    /**
     * 京东预拉回调
     * @return bool|\think\response\Json
     */
    public function notifyJDUrl0069(Request $request)
    {
        session_write_close();
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);
        try {
            logs(json_encode([
                'param' => $message,
                'ip' => $request->ip(),
                'startTime' => date("Y-m-d H:i:s", time())
            ]), 'notifyJDUrl0069');
            $validate = new NotifyjdurlValidate();
            if (!$validate->check($message)) {
                return json(msg(-1, '', $validate->getError()));
            }
            $orderPrepareModel = new OrderprepareModel();
            $orderWhere['order_me'] = $message['order_me'];  //平台单号
            $orderWhere['order_amount'] = $message['amount'];   //订单金额
            $orderInfo = $orderPrepareModel->where($orderWhere)->find();
            if (empty($orderInfo)) {
                return json(msg(-2, '', '无此预拉任务单！'));
            }
            if ($orderInfo['order_status'] != 3) {
                return json(msg(-3, '', '请勿重复提交！'));
            }
            $redis = new Redis(['index' => 1]);
            $PrepareUrlNotifyKey = "PrepareUrlNotify" . $message['order_me'];
            $setRes = $redis->setnx($PrepareUrlNotifyKey, $message['order_me'], 10);
            if (!$setRes) {
                return json(msg(-4, '', "请勿重复回调！"));
            }
            logs(json_encode([
                'start_time' => date('Y-m-d H:i:s', $orderInfo['add_time']),
                'post_time' => date('Y-m-d H:i:s', time()),
                'message' => $message,
            ]), 'timeNotifyJDUrl0069');

            $update['qr_url'] = $message['qr_url'];
            $update['get_url_time'] = time();
            $update['order_pay'] = $message['order_pay'];
            $update['status'] = 3;  //等待匹配
            $update['get_url_status'] = 1;   //预拉成功
            $update['order_desc'] = "预拉成功！";
            //ck失效

            if ($message['prepare_status'] != 1) {
                $update['get_url_status'] = 2;  //预拉失败
                $update['order_desc'] = "拉单失败！";
            }
            //ck失效
            if ($message['ck_status'] != 1) {
                $update['order_desc'] .= "ck失效";
                $cookieModel = new CookieModel();
                $cookieWhere['account'] = $orderInfo['ck_account'];
                $cookieUpdate['status'] = 2;
                $cookieModel->editCookie($cookieWhere, $cookieUpdate);
            }

            $updateRes = Db::table("bsa_order_prepare")->where($orderWhere)->update($update);
            if (!$updateRes) {
                $redis->delete($PrepareUrlNotifyKey);
                return json(msg(-9, '', '更新异常！'));
            }
            return json(msg(1, '', 'success'));
        } catch (\Exception $exception) {
            logs(json_encode(['param' => $message,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()]), 'notifyJDUrlException');
            return json(msg(-11, '', '接收异常！'));
        } catch (\Error $error) {
            logs(json_encode(['param' => $message,
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()]), 'notifyJDUrl0069Error');
            return json(msg(-22, '', "接收错误！"));
        }
    }
}