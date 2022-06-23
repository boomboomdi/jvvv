<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/2/28
 * Time: 8:23 PM
 */

namespace app\admin\controller;

use app\admin\model\CammyModel;
use app\admin\model\PrepareModel;
use think\Db;
use app\admin\validate\PrepareValidate;
use app\common\model\OrderdouyinModel;
use tool\Log;

class Cammy extends Base
{
    //预拉任务
    public function index()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
            $adminName = input('param.admin_name');
            $startTime = input('param.startTime');
            $endTime = input('param.endTime');
            $where = [];
            if (!empty($adminName)) {
                $where[] = ['admin_name', 'like', $adminName . '%'];
            }

            if (!empty($startTime)) {
                $where[] = ['add_time', '>', strtotime($startTime)];
            }
            if (!empty($endTime)) {
                $where[] = ['add_time', '<', strtotime($endTime)];
            }
            $db = new Db();
            $model = new CammyModel();
            $list = $model->getLists($limit, $where);

//            logs(json_encode([
//                'last_sql' => Db::table('bsa_cammy')->getLastSql(),
//            ]), 'CammyModel');
            $data = empty($list['data']) ? array() : $list['data'];
            foreach ($data as $key => $vo) {
                $data[$key]['add_time'] = date('Y-m-d H:i:s', $data[$key]['add_time']);
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

    public function export()
    {
        if (request()->isAjax()) {
            $param = input('param.');
            $msg = '导出失败！';
            $startTime = strtotime(date('Y-m-d'));
            if (!isset($param['startTime']) || empty($param['startTime'])) {
                $where[] = ['add_time', '>', $startTime];
                $msg = '卡密' . date('Y-m-d');
            } else {
                $startTime = strtotime($param['startTime']);
                $where[] = ['add_time', '>', $startTime];
                $msg = '卡密' . $param['startTime'];
            }
            if (!isset($param['endTime']) || empty($param['endTime'])) {
                $where[] = ['add_time', '<', $startTime + 86400];
                $msg = $msg . '到' . date('Y-m-d', $startTime + 86400);
            } else {
                //存在截止时间
                $endTime = strtotime($param['endTime']);
                $where[] = ['add_time', '<', $endTime];
                $msg = $msg . '到' . $param['endTime'];
            }

            if(isset($param['order_me'])&&!empty($param['endTime'])){
                $where[] = ['order_me', '=', $param['endTime']];
            }
            if(isset($param['card_name'])&&!empty($param['card_name'])){
                $where[] = ['card_name', '=', $param['card_name']];
            }
            $model = new CammyModel();
            $list = $model->getListsByWhere($where);
            logs(json_encode([
                'param' => $param,
                'SQL' => Db::table('bsa_cammy')->getLastSql(),
                "data" => $list['data']
            ]), 'CammyModel');
            if (0 == $list['code']) {
                return json(['code' => 0, 'msg' => $msg, 'data' => $list['data']->all()]);
            }

            return json(['code' => -2, 'msg' => '导出失败', 'data' => []]);
        }else{
            return json(['code' => -11, 'msg' => '请求失败', 'data' => []]);
        }
    }
}