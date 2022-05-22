<?php
/**
 * Created by PhpStorm.
 * User: bl
 * Date: 2020/12/20
 * Time: 12:57
 */

namespace app\admin\controller;

use app\common\model\NotifylogModel;
use tool\Log;
use app\admin\model\OrderModel;
use think\Db;

class Notifylog extends Base
{
    //设备列表
    public function index()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
            $account = input('param.account');

            $where = [];
            if (!empty($account)) {
                $where['account'] = ['=', $account];
            }
            $notifyLogModel = new NotifylogModel();
            $studio = session("admin_role_id");
            if ($studio == 7) {
                $where['studio'] = ['=', session("admin_user_name")];   //默认情况下 登录名就是 工作室标识
//                $where[] = ['studio', "=", session("admin_user_name")];  //默认情况下 登录名就是 工作室标识
            }
            $list = $notifyLogModel->getNotifyLogs($limit, $where);
            $data = empty($list['data']) ? array() : $list['data'];
            foreach ($data as $key => $vo) {
                $data[$key]['add_time'] = date('Y-m-d H:i:s', $data[$key]['add_time']);
                $data[$key]['pay_time'] = date('Y-m-d H:i:s', $data[$key]['pay_time']);
                //status 1匹配成功2未处理3未匹配
                if (!empty($data[$key]['status']) && $data[$key]['status'] == '1') {
                    $data[$key]['status'] = '<button class="layui-btn layui-btn-success layui-btn-xs">匹配成功</button>';
                }
                if (!empty($data[$key]['status']) && $data[$key]['status'] == '2') {
                    $data[$key]['status'] = '<button class="layui-btn layui-btn-disabled layui-btn-xs">正在匹配</button>';
                }
                if (!empty($data[$key]['status']) && $data[$key]['status'] == '3') {
                    $data[$key]['status'] = '<button class="layui-btn layui-btn-primary layui-btn-xs">匹配失败</button>';
                }
                if (!empty($data[$key]['status']) && $data[$key]['status'] == '4') {
                    $data[$key]['status'] = '<button class="layui-btn layui-btn-danger layui-btn-xs">疑似重复</button>';
                }
                if (!empty($data[$key]['qr_update_time']) && $data[$key]['qr_update_time'] != 0) {
                    $data[$key]['update_time'] = date('Y-m-d H:i:s', $data[$key]['qr_update_time']);
                }

            }
            $list['data'] = $data;
            if (0 == $list['code']) {
                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

}