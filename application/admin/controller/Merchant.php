<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/2/28
 * Time: 8:23 PM
 */

namespace app\admin\controller;

use app\admin\model\MerchantModel;
use app\admin\validate\MerchantValidate;
use app\common\model\OrderModel;
use think\Db;
use tool\Log;

class Merchant extends Base
{
    // 商户列表
    public function index()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
            $merchantName = input('param.merchant_name'); //商户名称

            $where = [];
            if (!empty($merchantName)) {
                $where[] = ['merchant_name', 'like', $merchantName . '%'];
            }
            $studio = session("admin_role_id");
            if ($studio == 9) {
                $where[] = ['merchant_sign', '=', session("admin_user_name")];//默认情况下 登录名就是 工作室标识
            }
            $admin = new MerchantModel();
            $list = $admin->getMerchants($limit, $where);
            $data = empty($list['data']) ? array() : $list['data'];
            foreach ($data as $key => $vo) {
                $data[$key]['add_time'] = date('Y-m-d H:i:s', $data[$key]['add_time']);
                $data[$key]['update_time'] = date('Y-m-d H:i:s', $data[$key]['update_time']);
                //查询商户订单量 总
                $order_total_amount = (new \app\admin\model\OrderModel())->getAllOrderTotalAmountByMerchantSign($vo['merchant_sign']);
                $data[$key]['order_total_amount'] = (new \app\admin\model\OrderModel())->getAllOrderTotalAmountByMerchantSign($vo['merchant_sign'])['data'];

                $orderCountWhere[] = ['merchant_sign', "=", $vo['merchant_sign']];
                //查询商户订单量
                $data[$key]['order_total'] = Db::table("bsa_order")->where($orderCountWhere)->count();
//                $data[$key]['order_total'] = (new \app\admin\model\OrderModel())->getAllOrderNumberByMerchantSign($vo['merchant_sign'])['data'];
                //成功数量 支付成功量

                $successCountWhere[] = ['order_status', "in", [1, 5]];
                $data[$key]['success_order_total'] = Db::table("bsa_order")->where($successCountWhere)->count();
                //成功率
                $data[$key]['success_order_rate'] = makeSuccessRate((int)$data[$key]['success_order_total'], (int)$data[$key]['order_total']);
//                logs(json_encode(['order_total' => $data[$key]['order_total'], 'success_total' => $data[$key]['success_order_total'], "last_sql" => Db::table('bsa_order')->getLastSql()]), 'merchantIndex_log_3');

                $startTime = time() - 300;
                $data[$key]['order_total5'] = (new \app\admin\model\OrderModel())->getAllOrderNumberByMerchantSign($data[$key]['merchant_sign'], $startTime)['data'];
                //查询商户订单量 支付成功量 30分钟
                $data[$key]['success_order_total5'] = (new \app\admin\model\OrderModel())->getAllOrderSuccessNumberByMerchantSign($data[$key]['merchant_sign'], $startTime)['data'];
                $data[$key]['success_order_rate5'] = makeSuccessRate((int)$data[$key]['success_order_total'], (int)$data[$key]['order_total5']);

            }

            $list['data'] = $data;
            if (0 == $list['code']) {
                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 添加商户
    public function addMerchant()
    {
        if (request()->isPost()) {

            $param = input('post.');

            $validate = new MerchantValidate();
            if (!$validate->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }

            $param['merchant_password'] = makePassword($param['merchant_password']);
            $param['merchant_validate_password'] = makePassword($param['merchant_validate_password']);
            $param['token'] = makePassword($param['merchant_validate_password']);

            $admin = new MerchantModel();
            $res = $admin->addMerchant($param);

            Log::write("添加商户：" . $param['merchant_name']);

            return json($res);
        }

//        $this->assign([
//            'roles' => (new \app\admin\model\Role())->getAllRoles()['data']
//        ]);

        return $this->fetch('add');
    }

    // 编辑商户
    public function editMerchant()
    {
        if (request()->isAjax()) {

            $param = input('post.');

            $validate = new MerchantValidate();
            if (!$validate->scene('edit')->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }

            if (isset($param['merchant_password'])) {
                $param['merchant_password'] = makePassword($param['merchant_password']);
            }

            $merchant = new MerchantModel();
            $res = $merchant->editMerchant($param);

            Log::write("编辑商户：" . $param['merchant_name']);

            return json($res);
        }
        $merchantId = input('param.merchant_id');
//        var_dump($merchantId);exit;
        $merchant = new MerchantModel();

        $this->assign([
            'merchant' => $merchant->getMerchantById($merchantId)['data'],
//            'roles' => (new \app\admin\model\Role())->getAllRoles()['data']
        ]);

        return $this->fetch('edit');
    }

    /**
     * 删除商户
     * @return \think\response\Json
     */
    public function delMerchant()
    {
        if (request()->isAjax()) {

            $merchantId = input('param.merchantId');
            $admin = new MerchantModel();
            $res = $admin->delMerchant($merchantId);

            Log::write("删除商户：" . $merchantId);

            return json($res);
        }
    }
}