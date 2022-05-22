<?php

namespace app\admin\model;

use think\Db;
use think\facade\Log;
use think\Model;

class TorderModel extends Model
{
    protected $table = 'bsa_torder';

    /**
     * 获取订单
     * @param $limit
     * @param $where
     * @return array
     */
    public function getTorders($limit, $where)
    {
        $prefix = config('database.prefix');
        try {
            $res = $this->field($prefix . 'torder.*')->where($where)
                ->order('t_id', 'desc')->paginate($limit);
        } catch (\Exception $e) {
            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $res, 'ok');
    }

    /**
     * 增加订单
     * @param $Order
     * @return array
     */
    public function addOrder($Order)
    {
        try {

            $has = $this->where('Order_name', $Order['Order_name'])->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '订单名已经存在');
            }

            $this->insert($Order);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '添加订单成功');
    }

    /**
     * 获取订单信息
     * @param $OrderId
     * @return array
     */
    public function getOrderById($OrderId)
    {
        try {

            $info = $this->where('order_no', $OrderId)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 编辑订单
     * @param $Order
     * @return array
     */
    public function editOrder($Order)
    {
        try {

            $has = $this->where('Order_name', $Order['Order_name'])->where('order_no', '<>', $Order['order_no'])
                ->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '订单名已经存在');
            }

            $this->save($Order, ['order_no' => $Order['order_no']]);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '编辑订单成功');
    }

    /**
     * 删除订单
     * @param $OrderId
     * @return array
     */
    public function delTorder($tId)
    {
        try {

            $this->where('t_id', $tId)->delete();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '删除成功');
    }

    /**
     * 获取订单信息
     * @param $name
     * @return array
     */
    public function getAdminByName($name)
    {
        try {

            $info = $this->where('Order_name', $name)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 获取订单信息
     * @param $id
     * @return array
     */
    public function getAdminInfo($id)
    {
        try {

            $info = $this->where('admin_id', $id)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
    }

    /**
     * 更新登录时间
     * @param $id
     */
    public function updateAdminInfoById($id, $param)
    {
        try {

            $this->where('admin_id', $id)->update($param);
        } catch (\Exception $e) {

        }
    }

    /**
     * 根据角色id 获取订单信息
     * @param $roleId
     * @return array
     */
    public function getAdminInfoByRoleId($roleId)
    {
        try {

            $info = $this->where('role_id', $roleId)->select()->toArray();
        } catch (\Exception $e) {

            return modelReMsg(-1, [], $e->getMessage());
        }

        return modelReMsg(0, $info, 'ok');
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
    public function tOrderNotifyForSuccess($apiMerchantOrderNo)
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
                    'orderStatus' => 6,
                    'apiMerchantOrderOfficialMsg' => $has['apiMerchantOrderOfficialMsg'],
                    'apiNotifyTimes' => $has['apiNotifyTimes'] + 1,
                    'orderExpireDate' => time()
                ]);
            $updatetMerchantRes = $db::table('bsa_tmerchant')->where("merchant_sign",$has['apiMerchantNo'])
                ->update([
                    'merchant_anount' => $tMerchant['merchant_amount'] + $has['apiMerchantOrderAmount']
                ]);
            if (!$updateTorderRes || !$updatetMerchantRes) {
                $db::rollback();
                return modelReMsg(-1, '', "系统错误！system sql error");
            }
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

            Log::write(1, "notify order pay result" . json_encode($nitifyResult));
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
        return modelReMsg(0, '', '支付回调成功!');
    }

}