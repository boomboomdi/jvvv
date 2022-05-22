<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/3/17
 * Time: 4:48 PM
 */

namespace app\common\model;

use think\Db;
use think\facade\Log;
use think\Model;

class TorderModel extends Model
{
    protected $table = 'bsa_torder';

    /**
     * 获取推单信息
     * @param $MerchantOrderNo
     * @return array
     */
    public function getTorderByMerchantOrderNo($MerchantOrderNo)
    {
        try {
            $has = $this->where('apiMerchantOrderNo', $MerchantOrderNo)->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '订单号已经存在!');
            }
        } catch (\Exception $e) {
            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, "", 'ok');
    }

    /**
     * 获取推单信息
     * @param $where
     * @return array
     */
    public function getTorderByWhere($where, $field = "")
    {
        try {
            if (!empty($field)) {
                $has = $this->field($field)->where($where)->findOrEmpty()->toArray();
            } else {
                $has = $this->where($where)->findOrEmpty()->toArray();
            }
            if (!empty($has)) {
                return modelReMsg(0, $has, '订单号存在!');
            }
        } catch (\Exception $e) {
            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(-2, "", '订单号不存在!');
    }

    /**
     * 获取推单信息
     * @param $where
     * @return array
     */
    public function getTorderForGet($where, $field = "")
    {
        try {
            $has = $this->field($field)->where($where)->select();
            if (!empty($has)) {
                return modelReMsg(0, $has, '订单号存在!');
            }
        } catch (\Exception $e) {
            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(-2, "", '订单号不存在!');
    }

    /**
     * 增加商户
     * @param $torder
     * @return array
     */
    public function addTorder($torder)
    {
        try {

            $has = $this->where('apiMerchantOrderNo', $torder['apiMerchantOrderNo'])->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '商户名已经存在');
            }

            $this->insert($torder);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '添加推单成功');
    }

    //话单异步通知 通知方式：得到充值结果后立即回调，如回调失败，每隔6秒回调一次，总共回调10次。
    //充值异步通知
    //参数名	类型	可为null	签名	说明
    //apiMerchantNo	string	否	是	卡商编号
    //apiMerchantOrderNo	string	否	是	推单编号
    //apiMerchantOrderAmount	int	否	是	订单金额
    //apiMerchantOrderStatus	int	否	是	充值结果状态码
    //apiMerchantOrderCardNo	string	否	是	充值油卡号
    //apiMerchantOrderDate	string	否	是	推单时间
    //apiMerchantOrderExpireDate	string	否	是	过期时间
    //apiMerchantOrderOfficialNo	String	是	是	京东官方单号（为null时不参与签名)
    //apiMerchantOrderOfficialMsg	String	是	是	官方信息（详见说明）
    //apiMerchantOrderType	string	否	是	推单类型
    //venderName	String	是	是	京东店铺全称
    //apiMerchantOrderDiscount	float	否	是	充值折扣
    //cardId	String	是	否	无业务意义，固定null
    //cardFileName	String	是	否	无业务意义，固定null
    //sign	string	否	否	签名（详见签名算法）

    //充值结果异步回调示例
    /*{
        "apiMerchantNo": "76153933",
        "apiMerchantOrderNo": "TA1626894229631",
        "apiMerchantOrderAmount": 500,
        "apiMerchantOrderStatus": 1,
        "apiMerchantOrderCardNo": "1000111100008422803",
        "apiMerchantOrderDate": "2021-07-22 03:03:49",
        "apiMerchantOrderExpireDate": "2021-07-22 03:23:49",
        "apiMerchantOrderOfficialNo": "214026501437",
        "apiMerchantOrderType": "1002H5",
        "venderName": "梵轩油卡充值专营店",
        "apiMerchantOrderDiscount": 0.9500,
        "sign": "C4EE73C8CB4E9846CED59A6702CB4FE9",
        "cardId": null,
        "cardFileName": null
    }
    */
    //apiMerchantOrderStatus
    //0	处理中	处理中
    //1	充值成功	充值已到账
    //2	充值失败	充值失败
    #### 通知结果反馈

    //平台通过【apiMerchantOrderNotifyUrl】通知商户，商户处理后，需要以字符串的形式反馈处理结果，内容如下
    //
    //|返回结果|结果说明|
    //|:-----  |-----                           |
    //|SUCCESS    |处理成功，平台收到此结果后不再进行后续通知,否则固定时长内固定频率尝试重新通知  |
    public function tOrderNotify($apiMerchantOrderNo)
    {
        $db = new Db();
        $db::startTrans();//开启事务
        try {
            $where['apiMerchantOrderNo'] = $apiMerchantOrderNo;
            $has = $this->where('apiMerchantOrderNo', $apiMerchantOrderNo)->findOrEmpty()->toArray();
            if (empty($has)) {
                return modelReMsg(-2, '', '订单不存在，the order is null!');
            }
            if ($has['orderStatus'] == 1) {
                return modelReMsg(-2, '', '订单已回调，the order is notify success!');
            }
            //修改订单状态
            $db::table('bsa_torder')->where('apiMerchantOrderNo', $apiMerchantOrderNo)->lock();
            $db::table('bsa_tmerchant')->where('apiMerchantNo', $has['apiMerchantNo'])->lock();
            $tMerchant = $db::table('bsa_tmerchant')->where($where)->findOrEmpty()->toArray();
            $updateTorderRes = $db::table('bsa_torder')->where($where)
                ->update([
                    'orderStatus' => 1,
                    'apiNotifyTimes' => $has['apiNotifyTimes'] + 1,
                    'orderExpireDate' => time()
                ]);

            $updatetMerchantRes = $db::table('bsa_tmerchant')->where($where)
                ->update([
                    'apiMerchantOrderAmount' => $tMerchant['merchant_amount'] + $has['apiMerchantOrderAmount']
                ]);
            if (!$updateTorderRes || !$updatetMerchantRes) {
                $db::rollback();
                return modelReMsg(-1, '', "系统错误！system sql error");
            }
            //sign
            $notifyData['apiMerchantNo'] = $has['apiMerchantNo'];
            $notifyData['apiMerchantOrderNo'] = $has['apiMerchantOrderNo'];
            $notifyData['apiMerchantOrderAmount'] = $has['apiMerchantOrderAmount'];
            $notifyData['apiMerchantOrderStatus'] = $has['orderStatus'];
            $notifyData['apiMerchantOrderCardNo'] = $has['apiMerchantOrderCardNo'];
            $notifyData['apiMerchantOrderDate'] = date('Y-m-d H:i:s', $has['apiMerchantOrderDate']);
            $notifyData['apiMerchantOrderExpireDate'] = date('Y-m-d H:i:s', $has['apiMerchantOrderExpireDate']);
            $notifyData['apiMerchantOrderOfficialNo'] = $has['apiMerchantOrderOfficialNo'];  //为空时不参与签名
            $notifyData['apiMerchantOrderOfficialMsg'] = $has['apiMerchantOrderOfficialMsg'];  //官方信息（详见说明）
            $notifyData['apiMerchantOrderType'] = $has['apiMerchantOrderType'];  //推单类型
            $notifyData['venderName'] = $has['venderName'];  //京东店铺全称
            $notifyData['apiMerchantOrderDiscount'] = $has['orderDiscount'];  //充值折扣
            $signNotifyData = $notifyData;
            if (empty($signNotifyData['apiMerchantOrderOfficialNo'])) {
                unset($signNotifyData['apiMerchantOrderExpireDate']);
            }
            ksort($signNotifyData);
            $sign = urldecode(http_build_query($signNotifyData));
            $sign = strtoupper(md5($sign . "&key=" . $tMerchant['token']));
            $notifyData['cardId'] = "null";  //无业务意义，固定null
            $notifyData['cardFileName'] = "null";  //无业务意义，固定null
            $notifyData['sign'] = $sign;  //$sign
            $nitifyResult = curlPost($has['apiMerchantOrderNotifyUrl'], $notifyData);

            Log::log('1', "notify merchant order ", $nitifyResult);
            $result = json_decode($nitifyResult, true);
            //通知失败
            if($result !="SUCCESS"){
                $db::table('bsa_torder')->where($where)
                    ->update([
                        'notifyStatus' => 2
                    ]);
            }
            $db::table('bsa_torder')->where($where)
                ->update([
                    'notifyStatus' => 1
                ]);
        } catch (\Exception $e) {
            $db::rollback();
            return modelReMsg(-1, '', $e->getMessage());
        }

        $db::commit();
        return modelReMsg(0, '', '回调成功');
    }


    //话单异步通知 通知方式：得到充值结果后立即回调，如回调失败，每隔6秒回调一次，总共回调10次。
    //充值异步通知
    //参数名	类型	可为null	签名	说明
    //apiMerchantNo	string	否	是	卡商编号
    //apiMerchantOrderNo	string	否	是	推单编号
    //apiMerchantOrderAmount	int	否	是	订单金额
    //apiMerchantOrderStatus	int	否	是	充值结果状态码
    //apiMerchantOrderCardNo	string	否	是	充值油卡号
    //apiMerchantOrderDate	string	否	是	推单时间
    //apiMerchantOrderExpireDate	string	否	是	过期时间
    //apiMerchantOrderOfficialNo	String	是	是	京东官方单号（为null时不参与签名)
    //apiMerchantOrderOfficialMsg	String	是	是	官方信息（详见说明）
    //apiMerchantOrderType	string	否	是	推单类型
    //venderName	String	是	是	京东店铺全称
    //apiMerchantOrderDiscount	float	否	是	充值折扣
    //cardId	String	是	否	无业务意义，固定null
    //cardFileName	String	是	否	无业务意义，固定null
    //sign	string	否	否	签名（详见签名算法）

    //充值结果异步回调示例
    /*{
        "apiMerchantNo": "76153933",
        "apiMerchantOrderNo": "TA1626894229631",
        "apiMerchantOrderAmount": 500,
        "apiMerchantOrderStatus": 1,
        "apiMerchantOrderCardNo": "1000111100008422803",
        "apiMerchantOrderDate": "2021-07-22 03:03:49",
        "apiMerchantOrderExpireDate": "2021-07-22 03:23:49",
        "apiMerchantOrderOfficialNo": "214026501437",
        "apiMerchantOrderType": "1002H5",
        "venderName": "梵轩油卡充值专营店",
        "apiMerchantOrderDiscount": 0.9500,
        "sign": "C4EE73C8CB4E9846CED59A6702CB4FE9",
        "cardId": null,
        "cardFileName": null
    }
    */
    //apiMerchantOrderStatus
    //0	处理中	处理中
    //1	充值成功	充值已到账
    //2	充值失败	充值失败
    #### 通知结果反馈

    //平台通过【apiMerchantOrderNotifyUrl】通知商户，商户处理后，需要以字符串的形式反馈处理结果，内容如下
    //
    //|返回结果|结果说明|
    //|:-----  |-----                           |
    //|SUCCESS    |处理成功，平台收到此结果后不再进行后续通知,否则固定时长内固定频率尝试重新通知  |
    public function tOrderNotifyForFail($apiMerchantOrderNo)
    {
        $db = new Db();
        $db::startTrans();//开启事务
        try {
            $where['apiMerchantOrderNo'] = $apiMerchantOrderNo;
            $has = $db::table('bsa_torder')->where('apiMerchantOrderNo', $apiMerchantOrderNo)->find();
            if (empty($has)) {
                return modelReMsg(-2, '', '订单不存在，the order is null!');
            }
            if ($has['orderStatus'] == 1) {
                return modelReMsg(-2, '', '订单已回调，the order is notify success!');
            }
            //修改订单状态
            $db::table('bsa_torder')->where('apiMerchantOrderNo', $apiMerchantOrderNo)->lock();
            $db::table('bsa_tmerchant')->where('merchant_sign', $has['apiMerchantNo'])->lock();
            $tMerchant = $db::table('bsa_tmerchant')->where('merchant_sign', $has['apiMerchantNo'])->find();
            $updateTorderRes = $db::table('bsa_torder')->where($where)
                ->update([
                    'orderStatus' => 5,
                    'apiMerchantOrderOfficialMsg' => "充值异常",
                    'apiNotifyTimes' => $has['apiNotifyTimes'] + 1,
                    'orderExpireDate' => time()
                ]);
//            $updatetMerchantRes = $db::table('bsa_tmerchant')->where("merchant_sign",$has['apiMerchantNo'])
//                ->update([
//                    'apiMerchantOrderAmount' => $tMerchant['merchant_amount'] + $has['apiMerchantOrderAmount']
//                ]);
//            if (!$updateTorderRes || !$updatetMerchantRes) {
//                $db::rollback();
//                return modelReMsg(-1, '', "系统错误！system sql error");
//            }
            $has = $db::table('bsa_torder')->where('apiMerchantOrderNo', $apiMerchantOrderNo)->find();
            //sign
            $notifyData['apiMerchantNo'] = $has['apiMerchantNo'];
            $notifyData['apiMerchantOrderNo'] = $has['apiMerchantOrderNo'];
            $notifyData['apiMerchantOrderAmount'] = $has['apiMerchantOrderAmount'];
            $notifyData['apiMerchantOrderStatus'] = $has['orderStatus'];
            $notifyData['apiMerchantOrderCardNo'] = $has['apiMerchantOrderCardNo'];
            $notifyData['apiMerchantOrderDate'] = date('Y-m-d H:i:s', $has['apiMerchantOrderDate']);
            $notifyData['apiMerchantOrderExpireDate'] = date('Y-m-d H:i:s', $has['apiMerchantOrderExpireDate']);
            $notifyData['apiMerchantOrderOfficialNo'] = $has['apiMerchantOrderOfficialNo'];  //为空时不参与签名
            $notifyData['apiMerchantOrderOfficialMsg'] = $has['apiMerchantOrderOfficialMsg'];  //官方信息（详见说明）
            $notifyData['apiMerchantOrderType'] = $has['apiMerchantOrderType'];  //推单类型
            $notifyData['venderName'] = $has['venderName'];  //京东店铺全称
            $notifyData['apiMerchantOrderDiscount'] = $has['orderDiscount'];  //充值折扣
            $signNotifyData = $notifyData;
            if (empty($signNotifyData['apiMerchantOrderOfficialNo'])) {
                unset($signNotifyData['apiMerchantOrderExpireDate']);
            }
//            var_dump($tMerchant);exit;
            ksort($signNotifyData);
            $sign = urldecode(http_build_query($signNotifyData));
            $sign = strtoupper(md5($sign . "&key=" . $tMerchant['token']));
            $notifyData['cardId'] = "null";  //无业务意义，固定null
            $notifyData['cardFileName'] = "null";  //无业务意义，固定null
            $notifyData['sign'] = $sign;  //$sign
            $nitifyResult = curlPost($has['apiMerchantOrderNotifyUrl'], $notifyData);

            Log::write(1, "notify order  pay fail" . json_encode($nitifyResult));
            $result = json_decode($nitifyResult, true);
            //通知失败
            if ($result != "SUCCESS") {
                Log::write(1, "notify merchant order " . $result);
                $db::table('bsa_torder')->where($where)
                    ->update([
                        'notifyStatus' => 2
                    ]);
            }
            $db::table('bsa_torder')->where($where)
                ->update([
                    'notifyStatus' => 1
                ]);
        } catch (\Exception $e) {
            $db::rollback();
            return modelReMsg(-1, '', $e->getMessage());
        }

        $db::commit();
        return modelReMsg(0, '', '失败回调成功!');
    }
}