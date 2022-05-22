<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/2/28
 * Time: 8:23 PM
 */

namespace app\admin\controller;

use app\admin\model\PrepareModel;
use think\Db;
use app\admin\validate\PrepareValidate;
use app\common\model\OrderdouyinModel;
use tool\Log;

class Prepare extends Base
{
    // 商户列表
    public function index()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
            $adminName = input('param.admin_name');

            $where = [];
            if (!empty($adminName)) {
                $where[] = ['admin_name', 'like', $adminName . '%'];
            }
            $db = new Db();
            $model = new PrepareModel();
            $list = $model->getPrepareLists($limit, $where);
            $data = empty($list['data']) ? array() : $list['data'];
            foreach ($data as $key => $vo) {
                $orderdouyinModel = new OrderdouyinModel();
//                $data[$key]['canUseNum'] = 0;
//                $data[$key]['canUseNum'] = $orderdouyinModel
//                    ->where('total_amount', '=', $vo['order_amount'])
//                    ->where('url_status', '=', 1)
//                    ->where('order_me', '=', null)
//                    ->where('status', '=', 1)
//                    ->where('last_use_time', '>', time() - 180)
//                    ->where('last_use_time', '<', time())->count();
                $data[$key]['add_time'] = date('Y-m-d H:i:s', $data[$key]['add_time']);
                $canUseNum = 0;
                $canUseNum = $db::table("bsa_torder_douyin")
                    ->where('status', '=', 1)
                    ->where('url_status', '=', 1)
                    ->where('total_amount', '=', $vo['order_amount'])
                    ->where('get_url_time', '>', time() - 180)
                    ->where('get_url_time', '<', time())
//                    ->where('get_url_time', '>', 0)
//                    ->where('get_url_time', '<', time() + 180)
                    ->where('limit_time_1', '>', time())
//                    ->order("add_time asc")
                    ->count();
//                logs(json_encode(["sql" => Db::table("bsa_torder_douyin")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'Prepare_log');

                $doPrepareNum = $db::table("bsa_torder_douyin")
//                        ->where('status', '=', 0)
//                        ->where('url_status', '=', 1)
                    ->where('status', '=', 0)
//                        ->where('url_status', '=', 1)
                    ->where('total_amount', '=', $vo['order_amount'])
                    ->where('weight', '=', 1)
                    ->where('get_url_time', '=', 0)
                    ->count();
//                logs(json_encode(["sql" => Db::table("bsa_torder_douyin")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'Prepare_log');

//                $can_use_num = +$doPrepareNum;

//                $data[$key]['totalNum'] = $db::table("bsa_torder_douyin")->count();
                $data[$key]['canUseNum'] = $canUseNum;
                $data[$key]['doPrepareNum'] = $doPrepareNum;
            }
            $list['data'] = $data;
            if (0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 添加预拉单
    public function addPrepare()
    {
        if (request()->isPost()) {

            $param = input('post.');

            $validate = new PrepareValidate();
            if (!$validate->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }

//            $param['admin_password'] = makePassword($param['admin_password']);
            $param['add_time'] = time();

            $model = new PrepareModel();
            $res = $model->addPrepare($param);

            Log::write("添加预拉单：" . $param['order_amount'] . $param['prepare_num'] . "个");

            return json($res);
        }

        return $this->fetch('add');
    }

    // 编辑预拉单
    public function editPrepare()
    {
        if (request()->isPost()) {

            $param = input('post.');

            $validate = new PrepareValidate();
            if (!$validate->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }


            $model = new PrepareModel();
            $res = $model->editPrepare($param);

            Log::write("编辑预拉单：" . $param['order_amount'] . $param['prepare_num'] . "个");

            return json($res);
        }

        $id = input('param.id');
        $model = new PrepareModel();

        $this->assign([
            'prepare' => $model->getPrepareById($id)['data']
        ]);

        return $this->fetch('edit');
    }

    /**
     * 删除预拉单
     * @return \think\response\Json
     */
    public function delPrepare()
    {
        if (request()->isAjax()) {

            $id = input('param.id');

            $model = new PrepareModel();
            $res = $model->delPrepare($id);

            Log::write("删除预拉单：" . $id);

            return json($res);
        }
    }
}