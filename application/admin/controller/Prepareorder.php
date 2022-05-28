<?php
/**
 * Created by PhpStorm.
 * User: bl
 * Date: 2020/12/20
 * Time: 12:57
 */

namespace app\admin\controller;

use think\Db;
use tool\Log;

use app\admin\model\PrepareorderModel;

class Prepareorder extends Base
{
    //预拉单列表
    public function index()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
            $startTime = input('param.startTime');
            $endTime = input('param.endTime');
            $payStatus = input('param.pay_status');
            $orderStatus = input('param.order_status');
            $param = input('param.');
            $where = [];
//            if (!empty(input('param.write_off_sign'))) {
//                $where[] = ['write_off_sign', '=', input('param.write_off_sign')];
//            }
            if (!empty(input('param.order_no'))) {
                $where[] = ['order_no', '=', input('param.order_no')];
            }
            if (!empty(input('param.order_me'))) {
                $where[] = ['order_me', '=', input('param.order_me')];
            }
            if (!empty(input('param.order_pay'))) {
                $where[] = ['order_pay', '=', input('param.order_pay')];
            }
            if (!empty(input('param.account'))) {
                $where[] = ['account', '=', input('param.account')];
            }
            if (!empty(input('param.operator'))) {
                $where[] = ['operator', '=', input('param.operator')];
            }
            if (!empty($param['order_status'])) {
                $where[] = ['order_status', '=', $param['order_status']];
            }
            if (!empty($param['pay_status'])) {
                $where[] = ['pay_status', '=', $param['pay_status']];
            }
            if (!empty($param['notify_status'])) {
                $where[] = ['notify_status', '=', $param['notify_status']];
            }
            if (!empty($startTime)) {
                $where[] = ['add_time', '>', strtotime($startTime)];
            }
            if (!empty($endTime)) {
                $where[] = ['add_time', '<', strtotime($endTime)];
            }

            $writeOffNodeId = session("admin_role_id");
            if ($writeOffNodeId == 8) {
                $where[] = ['write_off_sign', '=', session("admin_user_name")];   //默认情况下 登录名就是
            }
            $prepareorderModel = new PrepareorderModel();
            $list = $prepareorderModel->getLists($limit, $where);
//            var_dump(Db::table("bsa_order_hexiao")->getLastSql());
//            exit;
            $data = empty($list['data']) ? array() : $list['data'];
            foreach ($data as $key => $vo) {
                $data[$key]['add_time'] = date('Y-m-d H:i:s', $vo['add_time']);
                $data[$key]['use_time'] = date('Y-m-d H:i:s', $vo['use_time']);
                $data[$key]['pay_time'] = date('Y-m-d H:i:s', $vo['pay_time']);
                $data[$key]['limit_time'] = date('Y-m-d H:i:s', $vo['limit_time']);
                $data[$key]['last_use_time'] = date('Y-m-d H:i:s', $vo['last_use_time']);
                $data[$key]['notify_time'] = date('Y-m-d H:i:s', $vo['notify_time']);
            }
            $list['data'] = $data;
            if (0 == $list['code']) {
                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    /**
     * 删除推单
     * @return \think\response\Json
     */
    public function delTorder()
    {
        if (request()->isAjax()) {
            $tId = input('param.t_id');
            $torderModel = new Torderdouyinmodel();
            $res = $torderModel->delTorder($tId);
            Log::write("删除推单：" . $tId);
            return json($res);
        }
    }

    //止付
    public function notify()
    {
        $id = input('param.id');
        try {
            if (request()->isAjax()) {

                if (empty($id)) {
                    return json(modelReMsg(-1, '', '参数错误!'));
                }
                //查询订单
                $orderHxData = Db::table("bsa_order_hexiao")->where("id", $id)->find();
                if (empty($orderHxData)) {
                    return json(modelReMsg(-2, '', '无此核销单!'));
                }
                //查询是否有匹配订单
                $orderHXModel = new OrderhexiaoModel();
//                $orderData = Db::table("bsa_order")
//                    ->where("account", '=', $orderHxData['account'])
//                    ->where("order_pay", '=', $orderHxData['order_no'])
//                    ->where("order_status", '=', $orderHxData['order_no'])
//                    ->find();
//                if (!empty($orderData) || !empty($orderHxData['order_me'])) {
//                    return json(modelReMsg(-3, '', '已使用核销单不可止付!'));
//                }
                if ($orderHxData['order_limit_time'] != 0) {
                    return json(modelReMsg(-3, '', '匹配订单冻结期间不可止付!'));
                }
                Db::startTrans();
                $lock = Db::table("bsa_order_hexiao")->where("id", $id)->lock(true)->find();
                if (!$lock) {
                    return json(modelReMsg(-4, '', '止付失败！!'));
                }
                $updateHXData['check_result'] = "手动止付" . session('admin_user_name') . date("Y-m-d H:i:s", time());
                $updateHXData['status'] = 2;
                $updateHXData['limit_time'] = time();
                $updateHXData['order_status'] = 2;
                $updateHXData['check_status'] = 0;
                $updateHXData['pay_status'] = 2;
                if ($orderHxData['notify_times'] == 5) {
                    $updateHXData['notify_times'] = 4;
                }
                $localUpdate = Db::table("bsa_order_hexiao")->where("id", $id)->update($updateHXData);
                if (!$localUpdate) {
                    Db::rollback();
                    logs(json_encode(["time" => date("Y-m-d H:i:s", time()),
                        'order_no' => $orderHxData['order_no'],
                        'phone' => $orderHxData['account'],
                        "localUpdateFail" => json_encode($localUpdate)
                    ]), 'orderHXNotify');
                    return json(modelReMsg(-5, '', '回调订单发生错误!'));
                }
                Db::commit();
                return json(modelReMsg(0, '', '止付成功'));
            } else {
                return json(modelReMsg(-99, '', '访问错误'));
            }
        } catch (\Exception $exception) {
            logs(json_encode(['id' => $id, 'file' => $exception->getFile(), 'line' => $exception->getLine(),
                'errorMessage' => $exception->getMessage()]), 'orderHXNotifyException');
            return json(modelReMsg(-11, '', '止付异常'));
        } catch (\Error $error) {
            logs(json_encode(['id' => $id, 'file' => $error->getFile(), 'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()]), 'orderHXNotifyError');
            return json(modelReMsg(-22, '', '止付错误'));

        }


    }

}