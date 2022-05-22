<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/2/28
 * Time: 8:23 PM
 */

namespace app\admin\controller;

use app\admin\model\WriteoffModel;
use app\admin\validate\WriteoffValidate;
use app\common\model\OrderModel;
use app\common\model\SystemConfigModel;
use think\Db;
use tool\Log;

class Writeoff extends Base
{
    // 核销列表
    public function index()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
            $writeOffSign = input('param.write_off_sign'); //核销名称

            $where = [];
            if (!empty($writeOffSign)) {
                $where[] = ['write_off_sign', 'like', $writeOffSign . '%'];
            }

            $orderLimitTime = SystemConfigModel::getOrderLockTime();
            $writeOffModel = new WriteoffModel();
            $list = $writeOffModel->getWriteoffs($limit, $where);
            $data = empty($list['data']) ? array() : $list['data'];
            foreach ($data as $key => $vo) {//推单数量（每个金额）
                $data[$key]['orderTotalNum'] = Db::table("bsa_order_hexiao")
                    ->where("write_off_sign", '=', $vo['write_off_sign'])
                    ->count();
                //推单总额
                $data[$key]['totalOrderAmount'] = Db::table("bsa_order_hexiao")
                    ->field("SUM(order_amount) as totalOrderAmount")
                    ->where("write_off_sign", '=', $vo['write_off_sign'])
                    ->find()['totalOrderAmount'];

                //总支付数量（每个金额）
                $data[$key]['totalPayOrderAmountNum'] = Db::table("bsa_order_hexiao")
                    ->where("write_off_sign", '=', $vo['write_off_sign'])
                    ->where("pay_status", '=', 1)
                    ->count();
                //总支付金额（每个金额）
                $data[$key]['totalPayOrderAmount'] = Db::table("bsa_order_hexiao")
                    ->field("SUM(pay_amount) as totalPayOrderAmount")
                    ->where("write_off_sign", '=', $vo['write_off_sign'])
                    ->find()['totalPayOrderAmount'];

                //剩余单量
                $data[$key]['noUseOrderAmountNum'] = Db::table("bsa_order_hexiao")
                    ->where("write_off_sign", '=', $vo['write_off_sign'])
                    ->where("pay_status", '=', 0)
                    ->where("order_status", '=', 0)
                    ->where("status", '=', 0)
//                    ->where('limit_time', '>', time() + $orderLimitTime)
                    ->count();
                //剩余可用单量
                $data[$key]['canOrderAmountNum'] = Db::table("bsa_order_hexiao")
                    ->where("write_off_sign", '=', $vo['write_off_sign'])
                    ->where("pay_status", '=', 0)
                    ->where("order_status", '=', 0)
                    ->where("status", '=', 0)
                    ->where('limit_time', '>', time() + $orderLimitTime)
                    ->count();
            }
            $list['data'] = $data;
            if (0 == $list['code']) {
                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 添加核销
    public function addwriteoff()
    {
        if (request()->isPost()) {

            $param = input('post.');
//            var_dump($param);exit;
            $validate = new WriteoffValidate();
            if (!$validate->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }

            $param['write_off_username'] = $param['write_off_sign'];
//            $param['merchant_validate_password'] = makePassword($param['merchant_validate_password']);

            $writeOffModel = new WriteoffModel();
            $res = $writeOffModel->addWriteoff($param);

            Log::write("添加核销：" . $param['write_off_username']);

            return json($res);
        }

//        $this->assign([
//            'roles' => (new \app\admin\model\Role())->getAllRoles()['data']
//        ]);

        return $this->fetch('add');
    }

    // 编辑核销
    public function editWriteoff()
    {
        if (request()->isAjax()) {

            $param = input('post.');

            $validate = new WriteoffValidate();
            if (!$validate->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }

            $param['write_off_username'] = $param['write_off_sign'];
            $writeOffModel = new WriteoffModel();
            $res = $writeOffModel->editWriteoff($param);

            Log::write("编辑核销：" . $param['write_off_username']);

            return json($res);
        }
        $writeOffId = input('param.write_off_id');
//        var_dump($merchantId);exit;
        $writeOffModel = new WriteoffModel();
//        $writeOffData = $writeOffModel->get(["write_off_id" => $writeOffId]);
//        $writeOffData = $writeOffData['data'];
        $this->assign([
            'writeOff' => $writeOffModel->getWriteoffById($writeOffId)['data'],
        ]);

        return $this->fetch('edit');
    }

    /**
     * 删除核销
     * @return \think\response\Json
     */
    public function delWriteoff()
    {
        if (request()->isAjax()) {

            $writeOffId = input('param.write_off_id');

            $writeOffModel = new WriteoffModel();
            $res = $writeOffModel->delWriteoff($writeOffId);

            Log::write("删除核销：" . $writeOffId);

            return json($res);
        }
    }

    public function stopOrderHx()
    {
        if (request()->isPost()) {
            $param = input('post.');
            $where = [];
            if (empty(input('post.write_off_sign'))) {
                return json(['code' => -1, 'msg' => '核销商必填']);
            }
            if (empty(input('post.operator'))) {
                return json(['code' => -1, 'msg' => '核销商必填']);
            }
            if (empty(input('post.endTime'))) {
                return json(['code' => -2, 'msg' => '截至时间必选']);
            }
            $where[] = ['write_off_sign', "=", input('post.write_off_sign')];
            $where[] = ['operator', "=", input('post.operator')];
            $where[] = ['add_time', "<", strtotime(input('post.endTime'))];
            $where[] = ['order_limit_time', "=", 0];
            $where[] = ['order_status', "<>", 1];
            $where[] = ['notify_status', "<>", 1];
            $where[] = ['pay_status', "<>", 1];
//            $where[] = ['order_me', "=", null];

//            var_dump($where);
//            exit;
            Db::startTrans();
            $stopOrderAccount = Db::table("bsa_order_hexiao")->where($where)->lock(true)->count();
//            var_dump($stopOrderAccount);
//            var_dump(Db::table("bsa_order_hexiao")->getLastSql());
//            Db::rollback();
//            exit;
            if ($stopOrderAccount == 0) {
                Db::rollback();
                return json(['code' => 0, 'msg' => '支付核销单为零']);
            }

            $update['limit_time'] = time();
            $update['status'] = 2;
            $update['order_status'] = 2;
            $update['order_desc'] = "止付" . date("Y-m-d H:i:s");

            $updateData = Db::table("bsa_order_hexiao")->where($where)->update($update);
//            var_dump();
            $addLog['action_result'] = "处理成功！";
            if (!$updateData) {
                $addLog['action_result'] = "处理失败！";
                Db::rollback();
            }
            Db::commit();
            $addLog['user'] = session("admin_user_name");
            $addLog['order_type'] = "核销单号";
            $addLog['time'] = time();
            $addLog['action'] = "止付核销单";
            $addLog['content'] = serialize($where) . "共计" . $stopOrderAccount . "单";
            $addLog['desc'] = "止付核销商:" . input('post.write_off_sign') .
                ",(" . input('post.operator') . "),截至:" . input('post.endTime') . "止,共计" . $stopOrderAccount . "单)";
            Db::table("bsa_order_exception")->insert($addLog);

            return json(['code' => 0, 'msg' => $addLog['action_result']]);
        }
        $writeOffId = input('param.write_off_id');
        $writeOffModel = new WriteoffModel();
        $this->assign([
            'writeOff' => $writeOffModel->getWriteoffById($writeOffId)['data'],
        ]);
        return $this->fetch('stoporderhx');
    }
}