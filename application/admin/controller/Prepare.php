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
use app\common\model\SystemConfigModel;
use think\Db;
use app\admin\validate\PrepareValidate;
use app\common\model\OrderdouyinModel;
use tool\Log;

class Prepare extends Base
{
    //预拉任务
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

            $orderHxLockTime = SystemConfigModel::getOrderHxLockTime();
            $orderHxCanUseTime = SystemConfigModel::getOrderHxCanUseTime();
            $list = $model->getPrepareLists($limit, $where);
            $data = empty($list['data']) ? array() : $list['data'];
            foreach ($data as $key => $vo) {
                $data[$key]['add_time'] = date('Y-m-d H:i:s', $data[$key]['add_time']);
                $canUseNum = $db::table("bsa_order_prepare")
                    ->where('status', '<>', 2)
                    ->where('get_url_status', '=', 1)
                    ->where('order_status', '=', 3)   //等待匹配
                    ->where('order_amount', '=', $vo['order_amount'])
                    ->where('get_url_time', '>', time() - $orderHxCanUseTime)
                    ->count();

                $doPrepareNum = $db::table("bsa_order_prepare")
                    ->where('order_amount', '=', $vo['order_amount'])
                    ->where('get_url_status', '=', 3)
                    ->count();
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