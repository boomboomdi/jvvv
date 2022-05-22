<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/2/28
 * Time: 8:23 PM
 */

namespace app\admin\controller;

use app\admin\model\OrderModel;
use app\admin\model\Orderhexiaomodel;

//use app\admin\validate\WriteoffValidate;
//use app\common\model\OrderModel;
use app\admin\model\WriteoffModel;
use tool\Log;

//统计
class Statistics extends Base
{
    //
    public function index()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
            $writeOffSign = input('param.write_off_sign'); //核销名称
            $operator = input('param.operator'); //核销名称
            $where = [];
            if (empty($writeOffSign)) {
                return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
            }

            $where[] = ['write_off_sign', '=', $writeOffSign];

            $startTime = input('param.startTime'); //开始时间
            if (empty($startTime)) {
                $startTime = date("Y-m-d", time());
            }
            $writeOffModel = new WriteoffModel();
            $writeOff = $writeOffModel->getWriteOffBySign($writeOffSign);

            if (empty($writeOff)) {
                return json(['code' => -1, 'msg' => '核销商不存在！', 'count' => 0, 'data' => []]);
            }
            if (!empty($operator)) {
                $where[] = ['operator', '=', input('param.operator')];
            } else {
                $operator = "三网";
            }

            $orderHxModel = new Orderhexiaomodel();
            $list = $orderHxModel->field("order_amount,write_off_sign")
                ->where($where)
                ->group("order_amount")
                ->order("order_amount desc")
                ->paginate($limit);

            if (0 < count($list)) {
                $data = $list;
                $orderTotalNum = 0; //总推单数量
                $totalOrderAmount = 0; //推单总额
                $totalPayOrderAmount = 0; //总支付金额
                $totalPayOrderAmountNum = 0; //总支付数量
                $canOrderAmountNum = 0; //可以下单数量
                $k = 0;
                foreach ($data as $key => $vo) {
                    $k++;
                    $data[$key]['operator'] = $operator;
                    //推单数量（每个金额）
                    $data[$key]['orderTotalNum'] = $orderHxModel
                        ->where($where)
                        ->where('add_time', ">", strtotime($startTime))
                        ->where('add_time', "<", (strtotime($startTime) + 86400))
                        ->where("order_amount", "=", $vo['order_amount'])
                        ->count();
                    $orderTotalNum += $data[$key]['orderTotalNum'];

                    //推单总额（每个金额）
                    $data[$key]['totalOrderAmount'] = $orderHxModel
                        ->field("SUM(order_amount) as totalOrderAmount")
                        ->where($where)
                        ->where('add_time', ">", strtotime($startTime))
                        ->where('add_time', "<", (strtotime($startTime) + 86400))
                        ->where("order_amount", "=", $vo['order_amount'])
                        ->find()['totalOrderAmount'];
                    $totalOrderAmount += $data[$key]['totalOrderAmount'];

                    //总支付数量（每个金额）
                    $data[$key]['totalPayOrderAmountNum'] = $orderHxModel
                        ->where($where)
                        ->where('pay_time', ">", strtotime($startTime))
                        ->where('pay_time', "<", (strtotime($startTime) + 86400))
                        ->where("pay_status", '=', 1)
                        ->where("order_amount", "=", $vo['order_amount'])
                        ->count();
                    $totalPayOrderAmountNum += $data[$key]['totalPayOrderAmountNum'];
                    //总支付金额（每个金额）
                    $data[$key]['totalPayOrderAmount'] = $orderHxModel
                        ->field("SUM(pay_amount) as totalPayOrderAmount")
                        ->where($where)
                        ->where('pay_time', ">", strtotime($startTime))
                        ->where('pay_time', "<", (strtotime($startTime) + 86400))
                        ->where('pay_status', "=", 1)
                        ->where("order_amount", "=", $vo['order_amount'])
                        ->find()['totalPayOrderAmount'];
                    $totalPayOrderAmount += $data[$key]['totalPayOrderAmount'];

                    //剩余可用单量
                    $data[$key]['canOrderAmountNum'] = $orderHxModel
                        ->where($where)
                        ->where("pay_status", '=', 0)
                        ->where("order_status", '=', 0)
                        ->where("status", '=', 0)
                        ->where("order_amount", "=", $vo['order_amount'])
                        ->where('limit_time', '>', time() + 420)
                        ->count();
                    $canOrderAmountNum += $data[$key]['canOrderAmountNum'];
                }
                $total['order_amount'] = '总统计';
                $total['write_off_sign'] = $writeOffSign;
                $total['operator'] = $operator;
                $total['orderTotalNum'] = $orderTotalNum;
                $total['totalOrderAmount'] = $totalOrderAmount;
                $total['totalPayOrderAmount'] = $totalPayOrderAmount;
                $total['totalPayOrderAmountNum'] = $totalPayOrderAmountNum;
                $total['canOrderAmountNum'] = $canOrderAmountNum;

                $data[] = $total;
                $list = $data;
                return json(['code' => 0, 'msg' => 'ok', 'count' => $list->total(), 'data' => $list->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }


    //
    public function order()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
            $merchantSign = input('param.merchant_sign'); //核销名称

            $where = [];
            if (empty($merchantSign)) {
                return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
            }

            $startTime = input('param.startTime'); //开始时间
            if (empty($startTime)) {
                $startTime = date("Y-m-d", time());
            }
            $where[] = ['merchant_sign', '=', $merchantSign];
            $where[] = ['pay_time', '>', strtotime($startTime)];
            $where[] = ['pay_time', '<', (strtotime($startTime) + 86400)];
//            if (!empty($writeOffSign)) {
//                $where[] = ['write_off_sign', '=', $writeOffSign . '%'];
//            }
//            $writeOffModel = new WriteoffModel();
//            $writeOff = $writeOffModel->getWriteOffBySign($writeOffSign);

//            if (empty($writeOff)) {
//                return json(['code' => -1, 'msg' => '核销商标识输入有误！', 'count' => 0, 'data' => []]);
//            }
            $orderModel = new OrderModel();
//            $orderHxModel = new Orderhexiaomodel();
            $list = $orderModel->field("amount,merchant_sign")
                ->where($where)
                ->group("amount")
                ->order("amount desc")
//                ->select();
//                ->limit($limit)
                ->paginate($limit);
//            $totalCount = $list->count();
//            $res = $this->field($prefix . 'order.*')
//                ->where($where)
//                ->order('id', 'desc')
//                ->paginate($limit);
//            $list = $orderModel->getStatistics($limit, $where);
            if (0 < count($list)) {
                $data = $list;
                $orderTotalNum = 0; //总订单数量
                $totalOrderAmount = 0; //推订单总额
                $totalPayOrderAmount = 0; //订单总支付金额
                $totalPayOrderAmountNum = 0; //订单总支付数量
                $k = 0;
                foreach ($data as $key => $vo) {
                    $k++;
//                    $data[$key]['order_amount'] = $vo['order_amount'];
                    //订单数量（每个金额）
                    $data[$key]['orderTotalNum'] = $orderModel
                        ->where('merchant_sign', "=", $merchantSign)
                        ->where('add_time', ">", strtotime($startTime))
                        ->where('add_time', "<", (strtotime($startTime) + 86400))
                        ->where("amount", "=", $vo['amount'])
                        ->count();
                    $orderTotalNum += $data[$key]['orderTotalNum'];

                    //订单总额（每个金额）
                    $data[$key]['totalOrderAmount'] = $orderModel
                        ->field("SUM(amount) as totalOrderAmount")
                        ->where('merchant_sign', "=", $merchantSign)
                        ->where('add_time', ">", strtotime($startTime))
                        ->where('add_time', "<", (strtotime($startTime) + 86400))
                        ->where("amount", "=", $vo['amount'])
                        ->find()['totalOrderAmount'];
                    $totalOrderAmount += $data[$key]['totalOrderAmount'];

                    //总支付数量（每个金额）
                    $data[$key]['totalPayOrderAmountNum'] = $orderModel
                        ->where($where)
                        ->where('pay_status', '=', 1)
                        ->where("amount", "=", $vo['amount'])
                        ->count();
                    $totalPayOrderAmountNum += $data[$key]['totalPayOrderAmountNum'];
                    //总支付金额（每个金额）
                    $data[$key]['totalPayOrderAmount'] = $orderModel
                        ->field("SUM(actual_amount) as totalPayOrderAmount")
                        ->where($where)
                        ->where("amount", "=", $vo['amount'])
                        ->find()['totalPayOrderAmount'];
                    $totalPayOrderAmount += $data[$key]['totalPayOrderAmount'];

                }
                $total['amount'] = '总统计';
                $total['merchant_sign'] = $merchantSign;
                $total['orderTotalNum'] = $orderTotalNum;
                $total['totalOrderAmount'] = $totalOrderAmount;
                $total['totalPayOrderAmount'] = $totalPayOrderAmount;
                $total['totalPayOrderAmountNum'] = $totalPayOrderAmountNum;

                $data[] = $total;
                $list = $data;
//                var_dump(count($list));
////                exit;
//                $orderTotalNum = 0; //总推单数量
//                $totalOrderAmount = 0; //推单总额
//                $totalPayOrderAmount = 0; //总支付金额
//                $totalPayOrderAmountNum = 0; //总支付数量
                $list = $data;
//                var_dump($list->all());exit;
//                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
                return json(['code' => 0, 'msg' => 'ok', 'count' => $list->total(), 'data' => $list->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }
}