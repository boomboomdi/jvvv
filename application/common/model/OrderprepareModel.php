<?php

namespace app\common\model;

use app\admin\model\CookieModel;
use app\api\model\OrderLog;
use app\api\validate\OrderinfoValidate;
use app\common\model\AsyncModel;
use think\Db;
use think\facade\Log;
use think\Model;

class OrderprepareModel extends Model
{
    protected $table = 'bsa_order_prepare';

    //** 156975286加十位时间戳
    //** 156+3位随机字符串+加13位时间戳

    /**
     * 生成一个流水号
     * @return string
     */
    public function getOrderSerial()
    {
        $where = [];
//        $orderSerial = "156" . getRandString(1, 3) . getMillisecond();
        $orderSerial = "156" . createRandNum(3) . getMillisecond();
        $where[] = ['order_serial', "=", $orderSerial];
        $isHas = $this->where($where)->find();
        if (!empty($isHas)) {
            return createOrderSerial();
        }
        return $orderSerial;
    }


    /**
     * 增加预拉单
     * @param $where
     * @param $addParam
     * @return array
     */
    public function addOrder($where, $addParam)
    {
        try {
            $has = $this->where($where)->findOrEmpty()->toArray();
            if (!empty($has)) {
                return modelReMsg(-2, '', '预拉已经存在');
            }
            $this->insert($addParam);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, "", '添加推单成功');
    }

    //修改预拉单
    public function updatePrepare($where, $update)
    {
        try {
            $has = $this->where($where)->find();
            if (empty($has)) {
                return modelReMsg(-1, '', '预拉单不存在！');
            }
            $this->where($where)->update($update);
        } catch (\Exception $e) {
            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, "", '修改成功');

    }

    /**
     * @param $amount
     * @param $getUrlStatus 3获取中  1获取成功  2获取失败
     * @param $orderStatus 3未匹配  1匹配成功  2 匹配失败
     * @return array
     */
    public function getPrepareOrderNum($amount = "", $getUrlStatus = 1, $orderStatus = 3)
    {
        $where = [];
        $returnCount = 0;
        try {
            if (!empty($amount)) {
                $where[] = ['order_amount', '=', $amount];
            }
            $where[] = ['get_url_status', '=', $getUrlStatus];
            $where[] = ['order_status', '=', $orderStatus];

            $returnCount = $this->where($where)->count();
        } catch (\Exception $e) {
            return modelReMsg(-1, $returnCount, $e->getMessage());
        }
        return modelReMsg(0, $returnCount, '获取成功！');
    }


    /**
     * 根据金额生成 对核销淡定预拉单
     * @return void
     */
    public function createPrepareOrder($amount, $prepareNum = 1)
    {
        $successNum = 0;
        $errorNum = 0;
        $msg = "预拉单处理成功！";
        try {
            //获取CK
            $cookieModel = new CookieModel();
            $cookieWhere["status"] = 1;
            $getCookie = $cookieModel->where($cookieWhere)->order("last_use_time desc")->find();
            if (empty($getCookie)) {
                return modelReMsg(-1, $successNum, "无可用ck");
            }
            for ($len = $prepareNum; $len > 0; --$len) {
                //获取ck
                $cookie = $cookieModel->getUseCookie();
                if (!isset($cookie['code']) || $cookie['code'] != 0) {
                    $len = 0;
                    break;
                } else {
                    $addParam['order_me'] = md5(uniqid() . getMillisecond());
                    $addParam['ck_account'] = $cookie['data']['account'];
                    $addParam['status'] = 2;  //默认停用
                    $addParam['order_serial'] = $this->getOrderSerial();
                    $addParam['order_amount'] = $amount;
                    $addParam['order_desc'] = "预拉中" . date("Y-m-d H:i:s", time());
                    $addParam['add_time'] = time();
                    $insertRes = Db::table("bsa_order_prepare")->insert($addParam);
                    if (!$insertRes) {
                        $errorNum++;
                        logs(json_encode([
                            'param' => $addParam,
                            "insertRes" => $insertRes
                        ]), 'curlGetJDOrderUrlInsertOrderFail');
                    } else {
                        $param['cookie'] = $cookie['data']['cookie'];
                        $param['order_me'] = $addParam['order_me'];
                        $param['amount'] = $addParam['order_amount'];
                        $checkStartTime = date('Y-m-d H:i:s', time());
                        $curlRes = $this->getJDOrderUrl($param);
                        if (!isset($curlRes['code']) || $curlRes['code'] != 0) {
                            $errorNum++;
                            $where['order_me'] = $addParam['order_me'];
                            $update['order_desc'] = "预拉单失败|" . $curlRes['data'];
                            Db::table("bsa_order_prepare")
                                ->where($where)
                                ->update($update);
                            logs(json_encode([
                                "startTime" => $checkStartTime,
                                "endTime" => date("Y-m-d H:i:s", time()),
                                'param' => $param,
                                "curlLocalRes" => $curlRes
                            ]), 'curlGetJDOrderUrlFail');
                            return modelReMsg(-2, "", $curlRes['msg']);
                        } else {
                            $successNum++;
                        }
                    }
                }

            }

            return modelReMsg(0, $successNum, $msg);
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'PrepareorderCreateOrderException_log');
            return modelReMsg('-11', $successNum, "预产单失败" . $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'PrepareorderCreateOrderError_log');
            return modelReMsg('-22', $successNum, "预产单失败" . $error->getMessage());
        }
    }


    /**
     * 京东预拉单
     * @param $checkParam
     * @param $orderNo
     * @return array
     */
    public function getJDOrderUrl($checkParam)
    {
        try {
            $checkStartTime = date('Y-m-d H:i:s', time());
            $notifyResult = curlPostJson("http://127.0.0.1:23942/createOrderAppstore", $checkParam);

            logs(json_encode([
                'param' => $checkParam,
                "startTime" => $checkStartTime,
                "endTime" => date("Y-m-d H:i:s", time()),
                "checkAmountResult" => $notifyResult
            ]), 'curlGetJDOrderUrl');

            if (!is_string($notifyResult) || $notifyResult != "success") {
                return modelReMsg(-1, json_encode($notifyResult), "预拉请求失败！");
            }
//            $notifyResult = json_decode($notifyResult['data'], true);
//            if (!isset($notifyResult['code']) || $notifyResult['code'] != 0) {
//                return modelReMsg(-1, json_encode($notifyResult), $notifyResult['msg']);
//            }
            return modelReMsg(0, $notifyResult, '请求成功！');
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]),
                'getJDOrderUrlException');
            return modelReMsg(-11, '', $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]),
                'getJDOrderUrlError');
            return modelReMsg(-22, "", $error->getMessage());
        }
    }

    /**
     * 京东预拉单
     * @param $checkParam
     * @param $orderNo
     * @return array
     */
    public function checkOrderStatus($checkParam)
    {
        try {
            $checkStartTime = date('Y-m-d H:i:s', time());
            $notifyResult = curlPostJson("http://127.0.0.1:23942/queryAppstore", $checkParam);

            logs(json_encode([
                'param' => $checkParam,
                "startTime" => $checkStartTime,
                "endTime" => date("Y-m-d H:i:s", time()),
                "checkAmountResult" => $notifyResult
            ]), 'curlCheckOrderStatus');

            if (!is_string($notifyResult) || $notifyResult != "success") {
                return modelReMsg(-1, json_encode($notifyResult), "预拉请求失败！");
            }
//            $notifyResult = json_decode($notifyResult['data'], true);
//            if (!isset($notifyResult['code']) || $notifyResult['code'] != 0) {
//                return modelReMsg(-1, json_encode($notifyResult), $notifyResult['msg']);
//            }
            return modelReMsg(0, $notifyResult, '请求成功！');
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]),
                'getJDOrderUrlException');
            return modelReMsg(-11, '', $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]),
                'getJDOrderUrlError');
            return modelReMsg(-22, "", $error->getMessage());
        }
    }


    /**
     * 京东预拉单
     * @param $checkParam
     * @param $orderNo
     * @return array
     */
    public function checkOrderStatusNow($checkParam)
    {
        try {
            $checkStartTime = date('Y-m-d H:i:s', time());
            $notifyResult = curlPostJson("http://127.0.0.1:23943/queryBlance", $checkParam);

            logs(json_encode([
                'param' => $checkParam,
                "startTime" => $checkStartTime,
                "endTime" => date("Y-m-d H:i:s", time()),
                "checkAmountResult" => $notifyResult
            ]), 'curlCheckOrderStatus');

            if (!is_string($notifyResult) || $notifyResult != "success") {
                return modelReMsg(-1, json_encode($notifyResult), "预拉请求失败！");
            }
//            $notifyResult = json_decode($notifyResult['data'], true);
//            if (!isset($notifyResult['code']) || $notifyResult['code'] != 0) {
//                return modelReMsg(-1, json_encode($notifyResult), $notifyResult['msg']);
//            }
            return modelReMsg(0, $notifyResult, '请求成功！');
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]),
                'getJDOrderUrlException');
            return modelReMsg(-11, '', $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]),
                'getJDOrderUrlError');
            return modelReMsg(-22, "", $error->getMessage());
        }
    }

    /**
     * //接口地址：http://127.0.0.1:23943/queryBlance
     * //post请求参数：
     * //{"phone":"13283544163"}
     * //成功返回：
     * //{'code': 0, 'msg': 'SUCCESS', 'data': {'phone': '13283544163', 'amount': 469.19}, 'sign': '488864C0AB51AEA0AF551074446FBCEC'}
     * //失败返回：
     * //{"code":9999,"msg":"余额获取失败","data":null,"sign":null}
     * 查询手机余额
     * @param $checkParam --订单id  查询单号（四方）
     * @param $orderNo --核销order_no
     * @return array
     */
    public function checkPhoneAmount($checkParam, $orderNo)
    {
        try {
            $checkStartTime = date('Y-m-d H:i:s', time());
            $notifyResult = curlPostJson("http://127.0.0.1:23943/queryBlance", $checkParam);
//            $notifyResult = curlPostJson("http://www.baidu.com", $checkParam);

            logs(json_encode([
                'writeOrderNo' => $orderNo,  //四方订单 order_no
                'param' => $checkParam,
                "startTime" => $checkStartTime,
                "endTime" => date("Y-m-d H:i:s", time()),
                "checkAmountResult" => $notifyResult
            ]), 'curlCheckPhoneAmount_log');
            if (isset($checkParam['action']) && $checkParam['action'] == "other") {
                return $notifyResult;
            }
            $notifyResult = json_decode($notifyResult, true);
            //查询成功

//            $notifyResultData = json_decode($notifyResult['data'], true);
            //{"code":0,"msg":"SUCCESS","data":{"phone":"13333338889","amount":469.19},"sign":"488864C0AB51AEA0AF551074446FBCEC"}
            if (!isset($notifyResult['code']) || $notifyResult['code'] != 0) {
                return modelReMsg(-1, "", $notifyResult['msg']);
            }
            return modelReMsg(0, $notifyResult['data']['amount'], '查询成功！');
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]),
                'checkPhoneAmountException');
            return modelReMsg(-11, '', $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]),
                'checkPhoneAmountError');
            return modelReMsg(-22, "", $error->getMessage());
        }
    }

    public function checkPhoneAmountNew($checkParam, $orderNo)
    {
        try {
//            $ceshiDemoReturn['phone'] = "13283544164";
//            $ceshiDemoReturn['amount'] = 0.00;
//            return modelReMsg(0, 0.00, '查询成功！');
            $checkStartTime = date('Y-m-d H:i:s', time());
            $notifyResult = curlPostJson("http://127.0.0.1:23943/queryBlance", $checkParam);
//            $notifyResult = doSocket("http://127.0.0.1:23943/queryBlance", $checkParam);
//            $notifyResult = doSocket("http://www.baidu.com", $checkParam);
//            $notifyResult = curlPostJson("http://www.baidu.com", $checkParam);

            logs(json_encode([
                'writeOrderNo' => $orderNo,  //order_no
                'param' => $checkParam,
                "startTime" => $checkStartTime,
                "endTime" => date("Y-m-d H:i:s", time()),
                "checkAmountResult" => $notifyResult
            ]), 'curlcheckPhoneAmountNew');
            if (isset($checkParam['action']) && $checkParam['action'] == "other") {
                return $notifyResult;
            }
            $notifyResult = json_decode($notifyResult, true);
            //查询成功

//            $notifyResultData = json_decode($notifyResult['data'], true);
            //{"code":0,"msg":"SUCCESS","data":{"phone":"13333338889","amount":469.19},"sign":"488864C0AB51AEA0AF551074446FBCEC"}
            if (!isset($notifyResult['code']) || $notifyResult['code'] != 0) {
                return modelReMsg(-1, json_encode($notifyResult), $notifyResult['msg']);
            }
            return modelReMsg(0, $notifyResult['data']['amount'], '查询成功！');
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]),
                'checkPhoneAmountNewException');
            return modelReMsg(-11, '', $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]),
                'checkPhoneAmountNewError');
            return modelReMsg(-22, "", $error->getMessage());
        }
    }

    /**
     * {
     * "write_off_sign":"lisi", //string
     * "order_no":"e10adc3949ba59abbe56e057f20f883e",  //推单单号|string
     * "account":"13388888888",      //充值账号|string
     * "total_amount":"1.00",        //金额|float保留两位
     * "success_amount":"1.00",        //充值金额|float保留两位
     * "pay_time":"Y-m-d H:i:s",    //支付时间|2022-4-1 12:21:12
     * "sign":"" |string
     * }
     * 本地更新  bsa_order    bsa_order_hexiao
     * @param $orderDataNo
     * @param $amount
     * @param $orderStatus
     * @return array
     */
    public function orderLocalUpdate($orderDataNo, $orderStatus = 1, $amount = "")
    {

        $db = new Db();
        $db::startTrans();
        try {
            if ($orderStatus == 2) {
                $updateHXData['check_result'] = "查单回调" . session('admin_user_name');
                $updateOrderData['check_result'] = "查单回调" . session('admin_user_name');
            }
            //更新核销表  start
            $orderHxWhere['order_no'] = $orderDataNo['order_pay'];
            $orderHxWhere['account'] = $orderDataNo['account'];
            $orderHxWhere['pay_status'] = 0;
//            $orderWhere['account'] = $orderHxData['account'];
            $payTime = time();
            $lockHxOrderRes = $db::table("bsa_order_hexiao")->where($orderHxWhere)->lock(true)->find();
            if (!$lockHxOrderRes) {
                $db::rollback();
                logs(json_encode(['file' => $orderDataNo,
                    'time' => date("Y-m-d H:i:s", time()),
                    'lockHxOrderRes' => $lockHxOrderRes,
                    'lastSql' => $db::table("bsa_order_hexiao")->getLastSql(),
                ]), 'orderLocalUpdate');
                return modelReMsg(-1, "", "update fail rollback");
            }
            $amount = $orderDataNo['amount'];
            $updateHXData['pay_amount'] = (float)$amount;
            $updateHXData['pay_time'] = $payTime;
            $updateHXData['status'] = 2;
            $updateHXData['pay_status'] = 1;
            $updateHXRes = $db::table("bsa_order_hexiao")->where($orderHxWhere)
                ->update($updateHXData);
            if (!$updateHXRes) {
                $db::rollback();
                return modelReMsg(-2, "", "update fail rollback");
            }
            //更新核销表  end

            //更新订单表
            $updateOrderWhere['order_no'] = $orderDataNo['order_no'];
            $updateOrderWhere['order_me'] = $orderDataNo['order_me'];
            $orderData = $db::table('bsa_order')->where($updateOrderWhere)->find();   //订单
            $lockOrderRes = $db::table('bsa_order')
                ->where('id', '=', $orderData['id'])
                ->lock(true)->find();
            if (!$lockOrderRes) {
                $db::rollback();
                return modelReMsg(-3, "", "update lock order fail rollback");
            }

            $updateOrderData['actual_amount'] = (float)$amount;
            $updateOrderData['pay_status'] = 1;
            $updateOrderData['pay_time'] = $payTime;
            $updateOrderData['order_status'] = 1;
            $updateOrderData['check_status'] = 2;
            $updateOrderRes = $db::table('bsa_order')->where($updateOrderWhere)
                ->update($updateOrderData);
            logs(json_encode([
                'orderWhere' => $updateOrderWhere,
                'updateOrderData' => $updateOrderData,
                'updateOrderRes' => $updateOrderRes,
                'sql' => $db::table('bsa_order')->getLastSql()
            ]), 'updateOrderRes');
            if (!$updateOrderRes) {
                $db::rollback();
                return modelReMsg(-4, "", "update order fail rollback");
            }
            $db::commit();
            return modelReMsg(0, "", "更新成功");
        } catch (\Exception $exception) {
            $db::rollback();
            logs(json_encode(['file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()
            ]), 'orderLocalUpdateException');
            return modelReMsg(-11, "", "回调失败" . $exception->getMessage());
        } catch (\Error $error) {
            $db::rollback();
            logs(json_encode(['file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()
            ]), 'orderLocalUpdateError');
            return modelReMsg(-22, "", "回调失败" . $error->getMessage());
        }
    }


    /**
     * 重新匹配的时候 使用
     * 本地更新  bsa_order    bsa_order_hexiao
     * @param $orderDataNo
     * @param $amount
     * @param $orderStatus
     * @return array
     */
    public function loseOrderLocalUpdateNew($orderDataNo, $orderStatus = 1, $checkAmount = "")
    {

        $db = new Db();
        $db::startTrans();
        try {
            if ($orderStatus != 3) {
                return modelReMsg(-1, "", "update fail rollback");
            }
            $updateHXData['check_result'] = "发现掉单：" . $orderDataNo['order_no'] . "-" . $checkAmount;
            $updateOrderData['check_result'] = "发现掉单：" . $orderDataNo['order_no'] . "-" . $checkAmount;
            //更新核销表  start
            $orderHxWhere['order_no'] = $orderDataNo['order_pay'];
            $orderHxWhere['account'] = $orderDataNo['account'];
            $orderHxWhere['pay_status'] = 0;
//            $orderWhere['account'] = $orderHxData['account'];
            $payTime = time();
            $lockHxOrderRes = $db::table("bsa_order_hexiao")->where($orderHxWhere)->lock(true)->find();
            if (!$lockHxOrderRes) {
                $db::rollback();
                logs(json_encode(['file' => $orderDataNo,
                    'time' => date("Y-m-d H:i:s", time()),
                    'lockHxOrderRes' => $lockHxOrderRes,
                    'lastSql' => $db::table("bsa_order_hexiao")->getLastSql(),
                ]), 'orderLocalUpdate');
                return modelReMsg(-1, "", "update fail rollback");
            }
            $amount = $orderDataNo['amount'];
            $updateHXData['pay_amount'] = (float)$amount;
            $updateHXData['order_me'] = $orderDataNo['order_me'];
            $updateHXData['pay_time'] = $payTime;
            $updateHXData['status'] = 2;
            $updateHXData['pay_status'] = 1;
            $updateHXRes = $db::table("bsa_order_hexiao")->where($orderHxWhere)
                ->update($updateHXData);
            if (!$updateHXRes) {
                $db::rollback();
                return modelReMsg(-2, "", "update fail rollback");
            }
            //更新核销表  end

            //更新订单表
            $updateOrderWhere['order_no'] = $orderDataNo['order_no'];
            $updateOrderWhere['order_me'] = $orderDataNo['order_me'];
            $orderData = $db::table('bsa_order')->where($updateOrderWhere)->find();   //订单
            $lockOrderRes = $db::table('bsa_order')
                ->where('id', '=', $orderData['id'])
                ->lock(true)->find();
            if (!$lockOrderRes) {
                $db::rollback();
                return modelReMsg(-3, "", "update lock order fail rollback");
            }

            $updateOrderData['actual_amount'] = (float)$amount;
            $updateOrderData['pay_status'] = 1;
            $updateOrderData['pay_time'] = $payTime;
            $updateOrderData['order_status'] = 1;
            $updateOrderData['check_status'] = 2;
            $updateOrderRes = $db::table('bsa_order')->where($updateOrderWhere)
                ->update($updateOrderData);
            logs(json_encode([
                'orderWhere' => $updateOrderWhere,
                'updateOrderData' => $updateOrderData,
                'updateOrderRes' => $updateOrderRes,
                'sql' => $db::table('bsa_order')->getLastSql()
            ]), 'updateOrderRes');
            if (!$updateOrderRes) {
                $db::rollback();
                return modelReMsg(-4, "", "update order fail rollback");
            }
            $db::commit();
            return modelReMsg(0, "", "更新成功");
        } catch (\Exception $exception) {
            $db::rollback();
            logs(json_encode(['file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()
            ]), 'orderLocalUpdateException');
            return modelReMsg(-11, "", "回调失败" . $exception->getMessage());
        } catch (\Error $error) {
            $db::rollback();
            logs(json_encode(['file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()
            ]), 'orderLocalUpdateError');
            return modelReMsg(-22, "", "回调失败" . $error->getMessage());
        }
    }

    /**
     *
     * 获取可用付款抖音话单支付链接
     * @param $where
     * @return array
     */
    public function getUseHxOrder($order, $getTimes = 1)
    {
        $db = new Db();
        $db::startTrans();
        try {
            $bsaWriteOff = $db::table("bsa_write_off")->where('status', '=', 1)->column('write_off_sign');
            if (empty($bsaWriteOff)) {
                $db::rollback();
                return modelReMsg(-1, '', '无可用下单！-0');
            }

            $hxOrderInfo = $db::table("bsa_order_hexiao")
                ->field("bsa_order_hexiao.*")
                ->where('order_amount', '=', $order['amount'])
                ->where('order_me', '=', null)
                ->where('use_time', '=', 0)
                ->where('status', '=', 0)
                ->where('order_status', '=', 0)
                ->where('write_off_sign', 'in', $bsaWriteOff)
                ->where('order_limit_time', '=', 0)
                ->where('check_status', '=', 0)  //是否查单使用中
                ->where('limit_time', '>', time() + 420) //当前时间-420s 仍然<limit_time
                ->order("add_time  asc")
                ->lock(true)
                ->find();
            logs(json_encode(['action' => 'getUseHxOrder',
                'orderNo' => $order['order_no'],
                'hxOrderInfo' => $hxOrderInfo,
                'lastSql' => $db::table("bsa_order_hexiao")->getLastSql(),
            ]), 'getUseHxOrder_log');

            if (!$hxOrderInfo || $hxOrderInfo['order_no'] != null) {
                $db::rollback();
                return modelReMsg(-1, '', '无可用下单！-1');
            }

            $orderWhere['id'] = $hxOrderInfo['id'];
            $orderWhere['account'] = $hxOrderInfo['account'];
            $checking['order_status'] = 1;  //使用中
            $checking['check_status'] = 1;   //查询余额中
            $checking['last_check_time'] = time();   //查询上次查询时间
            $checkRes = $db::table("bsa_order_hexiao")->where($orderWhere)->update($checking);
            if (!$checkRes) {
                $db::rollback();
                return modelReMsg(-1, '', '无可用下单！-1');
            }
            $orderWhere['id'] = $hxOrderInfo['id'];
            $checkParam['phone'] = $hxOrderInfo['account'];
            $checkParam['order_no'] = $hxOrderInfo['account'];
            $checkParam['action'] = 'first';
            $db::commit();  //表事务结束
            $checkRes = $this->checkPhoneAmountNew($checkParam, $hxOrderInfo['order_no']);

            if ($checkRes['code'] != 0) {
                //停用该核销单
                $updateHxWhereForStop['id'] = $hxOrderInfo['id'];
                $updateHxDataForStop['status'] = 2;
                $updateHxDataForStop['limit_time'] = time();
                $updateHxDataForStop['last_use_time'] = time();
                $updateHxDataForStop['order_status'] = 2;
                $updateHxDataForStop['check_status'] = 0;
                $updateHxDataForStop['check_result'] = $checkRes['data'];
                $updateHxDataForStop['order_desc'] = "不可查单，立即回调" . json_encode($checkRes);
                $updateHxDataForStopRes = $db::table("bsa_order_hexiao")->where($updateHxWhereForStop)->update($updateHxDataForStop);
                logs(json_encode([
                    'action' => 'updateHxWhereForStop',
                    'orderWhere' => $updateHxWhereForStop,
                    'updateHxDataForStop' => $updateHxDataForStop,
                    'checkPhoneAmountNewRes' => $checkRes,
                    'updateHxDataForStopRes' => $updateHxDataForStopRes,
                    'getLastSql' => $db::table("bsa_order_hexiao")->getLastSql(),
                ]), 'ADONTMatchHxDataCheckResFAIL');
//                if (!$updateHxDataForStopRes) {
//                    $db::rollback();
//                }
                return modelReMsg(-4, '', '下单频繁，请稍后再下-4！');
            }
//            $db::startTrans();
//            $db::table("bsa_order_hexiao")
//                ->where("id", "=", $hxOrderInfo['id'])
//                ->lock(true);
            //查询成功更新余额order_hexiao $order order_hexiao
            $orderWhere['id'] = $hxOrderInfo['id'];
            $updateMatch['last_check_amount'] = (float)$checkRes['data'];
            $updateMatch['check_status'] = 0;
            $updateMatch['status'] = 1;   //使用中
            $updateMatch['last_check_time'] = time();  //上次查询余额时间
            $updateMatch['use_time'] = time();   //使用时间
            $updateMatch['use_times'] = $hxOrderInfo['use_times'] + 1;   //使用次数+1
            $updateMatch['last_use_time'] = time();
            $updateMatch['order_limit_time'] = time() + 3600;  //匹配成功后锁定3600s 后没支付可以重新解锁匹配
            $updateMatch['order_status'] = 1;
            $updateMatch['order_me'] = $order['order_me'];
            $updateMatch['order_desc'] = "匹配成功！当前余额:" . $checkRes['data'];

            $updateMatchSuccessRes = $db::table("bsa_order_hexiao")->where($orderWhere)->update($updateMatch);
            logs(json_encode([
                'action' => 'getUseHxOrderUpdateMatch',
                'orderWhere' => $orderWhere,
                'updateMatch' => $updateMatch,
                'updateMatchSuccessRes' => $updateMatchSuccessRes,
            ]), 'AAAMatchSuccessRes');
            if (!$updateMatchSuccessRes) {
                return modelReMsg(-5, '', '下单频繁，请稍后再下-5！');
            }
            $hxOrderInfo = $db::table("bsa_order_hexiao")->where($orderWhere)->find();
            return modelReMsg(0, $hxOrderInfo, "匹配成功！");

        } catch (\Exception $exception) {

            $db::rollback();
            logs(json_encode(['orderNo' => $order['order_no'],
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()]), 'getUseHxOrderException');
            return modelReMsg(-11, '', $exception->getMessage());
        } catch (\Error $error) {

            $db::rollback();
            logs(json_encode(['orderNo' => $order['order_no'],
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()]), 'getUseHxOrderError');
            return modelReMsg(-11, '', $error->getMessage());
        }

    }


    /**
     *
     * 获取可用付款抖音话单支付链接
     * @param $where
     * @return array
     */
    public function getUseHxOrderNew($order, $getTimes = 1)
    {
        $db = new Db();
        $db::startTrans();
        try {
            if (empty($order['order_me'])) {
                return modelReMsg(-1, '', '错误订单！-0');
            }
            $bsaWriteOff = $db::table("bsa_write_off")->where('status', '=', 1)->column('write_off_sign');
            if (empty($bsaWriteOff)) {
                $db::rollback();
                return modelReMsg(-2, '', '无可匹配订单！-2');
            }

            $hxOrderInfo = $db::table("bsa_order_hexiao")
                ->where("order_me", "=", $order['order_me'])
                ->where("account", "=", $order['account'])
                ->lock(true)
                ->find();
//            logs(json_encode(['action' => 'getUseHxOrder',
//                'orderNo' => $order['order_no'],
//                'hxOrderInfo' => $hxOrderInfo,
//                'lastSql' => $db::table("bsa_order_hexiao")->getLastSql(),
//            ]), 'getUseHxOrder_log');

            if (!$hxOrderInfo || $hxOrderInfo['order_me'] == null) {
                $db::rollback();
                return modelReMsg(-3, '', '无可用下单！-3');
            }

            $orderWhere['id'] = $hxOrderInfo['id'];
            $orderWhere['account'] = $hxOrderInfo['account'];
            $checking['order_status'] = 1;  //使用中
            $checking['check_status'] = 1;   //查询余额中
            $checking['last_check_time'] = time();   //查询上次查询时间
            $checkRes = $db::table("bsa_order_hexiao")->where($orderWhere)->update($checking);
            if (!$checkRes) {
                $db::rollback();
                return modelReMsg(-4, '', '无可用下单！-1');
            }
            $orderWhere['id'] = $hxOrderInfo['id'];
            $checkParam['phone'] = $hxOrderInfo['account'];
            $checkParam['order_no'] = $order['order_no'];
            $checkParam['action'] = 'first';
            $db::commit();  //表事务结束
            $checkRes = $this->checkPhoneAmountNew($checkParam, $hxOrderInfo['order_no']);

            if ($checkRes['code'] != 0) {
                //停用该核销单
                $updateHxWhereForStop['id'] = $hxOrderInfo['id'];
                $updateHxDataForStop['status'] = 2;
                $updateHxDataForStop['limit_time'] = time();
                $updateHxDataForStop['last_use_time'] = time();
                $updateHxDataForStop['order_status'] = 2;
                $updateHxDataForStop['check_status'] = 0;
                $updateHxDataForStop['check_result'] = "查询失败，立即回调" . $checkRes['data'];
                $updateHxDataForStop['order_desc'] = "查询失败，立即回调|" . json_encode($checkRes);
                $updateHxDataForStopRes = $db::table("bsa_order_hexiao")->where($updateHxWhereForStop)->update($updateHxDataForStop);
                logs(json_encode([
                    'action' => 'updateHxWhereForStop',
                    'orderWhere' => $updateHxWhereForStop,
                    'updateHxDataForStop' => $updateHxDataForStop,
                    'checkPhoneAmountNewRes' => $checkRes,
                    'updateHxDataForStopRes' => $updateHxDataForStopRes,
                    'getLastSql' => $db::table("bsa_order_hexiao")->getLastSql(),
                ]), 'ADONTMatchHxDataCheckResFAIL');
//                if (!$updateHxDataForStopRes) {
//                    $db::rollback();
//                }
                return modelReMsg(-5, "查询失败，立即回调" . $checkRes['data'], '查询余额失败-5！');
            }
            //查询成功更新余额order_hexiao $order order_hexiao
            $orderWhere['id'] = $hxOrderInfo['id'];
            $orderWhere['order_me'] = $hxOrderInfo['order_me'];
            $orderWhere['account'] = $order['account'];
            $updateMatch['last_check_amount'] = (float)$checkRes['data'];
            $updateMatch['check_status'] = 0;
            $updateMatch['status'] = 1;   //使用中
            $updateMatch['last_check_time'] = time();  //上次查询余额时间
            $updateMatch['use_time'] = time();   //使用时间
            $updateMatch['use_times'] = $hxOrderInfo['use_times'] + 1;   //使用次数+1
            $updateMatch['last_use_time'] = time();
            $updateMatch['order_status'] = 1;
            $updateMatch['order_desc'] = "匹配成功！当前余额:" . $checkRes['data'];

            $updateMatchSuccessRes = $db::table("bsa_order_hexiao")->where($orderWhere)->update($updateMatch);
            logs(json_encode([
                'action' => 'getUseHxOrderUpdateMatch',
                'orderWhere' => $orderWhere,
                'updateMatch' => $updateMatch,
                'updateMatchSuccessRes' => $updateMatchSuccessRes,
            ]), 'AAAMatchSuccessRes');
            if (!$updateMatchSuccessRes) {
                return modelReMsg(-6, '', '下单频繁，请稍后再下-6！');
            }
            $hxOrderInfo = $db::table("bsa_order_hexiao")->where($orderWhere)->find();
            return modelReMsg(0, $hxOrderInfo, "匹配成功！");

        } catch (\Exception $exception) {

            $db::rollback();
            logs(json_encode(['orderNo' => $order['order_no'],
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()]), 'getUseHxOrderException');
            return modelReMsg(-11, '', $exception->getMessage());
        } catch (\Error $error) {

            $db::rollback();
            logs(json_encode(['orderNo' => $order['order_no'],
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()]), 'getUseHxOrderError');
            return modelReMsg(-11, '', $error->getMessage());
        }

    }

    /**
     * {
     * "write_off_sign":"lisi", //string
     * "order_no":"e10adc3949ba59abbe56e057f20f883e",  //推单单号|string
     * "account":"13388888888",      //充值账号|string
     * "order_amount":"1.00",        //金额|float保留两位
     * "success_amount":"1.00",        //充值金额|float保留两位
     * "pay_time":"Y-m-d H:i:s",    //支付时间|2022-4-1 12:21:12
     * "sign":"" |string
     * }
     * 回调核销后台
     * @param $tOrderData
     * @return void
     */
    public function orderNotifyToWriteOff($orderHXData, $orderStatus = 1)
    {
        $db = new Db();
        $db::startTrans();
        try {
            $db = new Db();
            $writeWhere['write_off_sign'] = $orderHXData['write_off_sign'];
            $writeOff = $db::table("bsa_write_off")->where($writeWhere)->find();
            if (empty($writeOff)) {
                $db::rollback();
                return modelReMsg(-1, "", "notify hexiao lock fail");
            }
//            $orderHXWhere['order_no'] = (string)$orderHXData['order_no'];
            $lockOrderHXData = $db::table("bsa_order_hexiao")->where('id', $orderHXData['id'])->lock(true)->find();
            if (!$lockOrderHXData) {
                $db::rollback();
                return modelReMsg(-2, "", "notify hexiao lock fail");
            }

            $notifyParam['write_off_sign'] = $orderHXData['write_off_sign'];
            $notifyParam['order_no'] = $orderHXData['order_no'];
            $notifyParam['account'] = $orderHXData['account'];
            $notifyParam['order_type'] = $orderHXData['order_type'];
            $notifyParam['order_amount'] = $orderHXData['order_amount'];
            $notifyParam['pay_amount'] = $orderHXData['pay_amount'];
            $notifyParam['pay_status'] = $orderHXData['pay_status'];
            $notifyParam['order_serial'] = $orderHXData['order_serial'];  //流水号
            if ($notifyParam['pay_status'] != 1) {
                $notifyParam['pay_status'] = 2;
            }
            if ($orderHXData['pay_time'] != 0) {
                $notifyParam['time'] = $orderHXData['pay_time'];
            } else {
                $notifyParam['time'] = time();
            }
            $md5Sting = $notifyParam['write_off_sign'] . $notifyParam['order_no'] . $notifyParam['account'] . $notifyParam['pay_status'] . $notifyParam['order_amount'] . $notifyParam['pay_amount'] . $notifyParam['time'] . $writeOff['token'];
            $notifyParam['sign'] = md5($md5Sting);
            $startTime = date("Y-m-d H:i:s", time());
            //回调核销  已经收到款项
            $notifyResult = curlPostJson($orderHXData['notify_url'], $notifyParam);
            logs(json_encode([
                "startTime" => $startTime,
                "endTime" => date("Y-m-d H:i:s", time()),
                'notifyParam' => $notifyParam,
                'notifyUrl' => $orderHXData['notify_url'],
                "paramAddTime" => date("Y-m-d H:i:s", $orderHXData['add_time']),
                "notifyResult" => $notifyResult
            ]), 'curlPostJsonToWriteOff_log');
            $notifyResultLog = "第" . ($orderHXData['notify_times'] + 1) . "次回调:" . json_encode($notifyResult) . "(" . date("Y-m-d H:i:s") . ")";

            //通知结果不为success
            if ($notifyResult != "success") {
                $db::rollback();
                $db::table('bsa_order_hexiao')->where('id', $orderHXData['id'])
                    ->update([
                        'notify_time' => time(),
                        'notify_times' => $orderHXData['notify_times'] + 1,
                        'notify_result' => $notifyResultLog,
                        'order_desc' => "回调失败:" . $notifyResult
                    ]);
                return modelReMsg(-3, "", "回调结果失败！");

            }
            $db::table('bsa_order_hexiao')->where('id', $orderHXData['id'])
                ->update([
                    'notify_time' => time(),
                    'notify_status' => 1,
                    'notify_times' => $orderHXData['notify_times'] + 1,
                    'notify_result' => $notifyResultLog,
                    'order_desc' => "回调成功:" . $notifyResult
                ]);
            $db::commit();
            return modelReMsg(0, "", json_encode($notifyResult));
        } catch (\Exception $exception) {
            $db::rollback();
            logs(json_encode(['file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()
            ]), 'orderNotifyToWriteOffException');
            return modelReMsg(-11, "", "回调失败" . $exception->getMessage());
        } catch (\Error $error) {
            $db::rollback();
            logs(json_encode(['file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()
            ]), 'orderNotifyToWriteOffError');
            return modelReMsg(-22, "", "回调失败" . $error->getMessage());

        }
    }


    /**
     * 支付超时订单修改
     * @param $where
     * @param $updateData
     * @return array
     */
    public function localUpdateHXOrder($where, $updateData)
    {
        $db = new Db();
        $db::startTrans();
        try {
            $orderHxInfo = $db::table("bsa_order_hexiao")->where($where)->lock(true)->find();
            if (!$orderHxInfo) {
                $db::rollback();
                logs(json_encode([
                    'orderWhere' => $where,
                    'updateData' => $updateData,
                    'orderHxInfo' => $orderHxInfo
                ]), 'localUpdateHXOrderFail_log');
                return modelReMsg(-1, "", "更新失败!");
            }
            $updateData['order_desc'] = "订单冻结.等待第" . $orderHxInfo['use_times'] . "使用!";
            $updateRes = $db::table("bsa_order_hexiao")->where($where)->update($updateData);
            if (!$updateRes) {
                $db::rollback();
                logs(json_encode([
                    'orderWhere' => $where,
                    'updateData' => $updateData,
                    'updateRes' => $updateRes,
                    'updateSql' => $db::table("bsa_order_hexiao")->getLastSql(),
                ]), 'localUpdateHXOrderFail_log');
                return modelReMsg(-2, "", "更新失败");
            }
            logs(json_encode([
                'orderWhere' => $where,
                'updateData' => $updateData,
                'updateRes' => $updateRes
            ]), 'localhostUpdateHxOrder');

            $db::commit();
            return modelReMsg(0, "", "更新成功");

        } catch (\Exception $exception) {
            $db::rollback();
            logs(json_encode(['file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()
            ]), 'localUpdateHXOrderException');
            return modelReMsg(-11, "", $exception->getMessage());
        } catch (\Error $error) {
            $db::rollback();
            logs(json_encode([
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()
            ]), 'localUpdateHXOrderError');
            return modelReMsg(-22, "", $error->getMessage());
        }
    }
}