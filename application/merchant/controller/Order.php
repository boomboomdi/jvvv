<?php
/**
 * Created by PhpStorm.
 * User: bl
 * Date: 2020/12/20
 * Time: 12:57
 */
namespace app\merchant\controller;

use app\common\model\OrderModel;
use think\Db;
use tool\Log;

class Order extends Base
{
    //订单列表
    public function index()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');
            $orderId = input('param.order_id');
            $startTime = input('param.start_time');
            $endTime = input('param.end_time');

            $where = [];
            if (!empty($orderId)) {
                $where[] = ['order_id', 'like', $orderId . '%'];
            }
            if (!empty($startTime)) {
                $where[] = ['add_time', '>', strtotime($startTime)];
            }
            if (!empty($endTime)) {
                $where[] = ['add_time', '<', strtotime($endTime)];
            }
            $Order = new OrderModel();
            $list = $Order->getOrders($limit, $where);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

}