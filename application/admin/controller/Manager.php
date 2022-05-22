<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/2/28
 * Time: 8:23 PM
 */
namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\validate\AdminValidate;
use tool\Log;

class Manager extends Base
{
    // 商户列表
    public function index()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');
            $adminName = input('param.admin_name');

            $where = [];
            if (!empty($adminName)) {
                $where[] = ['admin_name', 'like', $adminName . '%'];
            }

            $admin = new Admin();
            $list = $admin->getAdmins($limit, $where);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 添加商户
    public function addAdmin()
    {
        if(request()->isPost()) {

            $param = input('post.');

            $validate = new AdminValidate();
            if(!$validate->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }

            $param['admin_password'] = makePassword($param['admin_password']);
            $param['add_time'] = date("Y-m-d H:i:s",time());

            $admin = new admin();
            $res = $admin->addAdmin($param);

            Log::write("添加商户：" . $param['admin_name']);

            return json($res);
        }

        $this->assign([
            'roles' => (new \app\admin\model\Role())->getAllRoles()['data']
        ]);

        return $this->fetch('add');
    }

    // 编辑商户
    public function editAdmin()
    {
        if(request()->isPost()) {

            $param = input('post.');

            $validate = new AdminValidate();
            if(!$validate->scene('edit')->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }

            if(isset($param['admin_password'])) {
                $param['admin_password'] = makePassword($param['admin_password']);
            }

            $admin = new admin();
            $res = $admin->editAdmin($param);

            Log::write("编辑商户：" . $param['admin_name']);

            return json($res);
        }

        $adminId = input('param.admin_id');
        $admin = new admin();

        $this->assign([
            'admin' => $admin->getAdminById($adminId)['data'],
            'roles' => (new \app\admin\model\Role())->getAllRoles()['data']
        ]);

        return $this->fetch('edit');
    }

    /**
     * 删除商户
     * @return \think\response\Json
     */
    public function delAdmin()
    {
        if(request()->isAjax()) {

            $adminId = input('param.id');

            $admin = new admin();
            $res = $admin->delAdmin($adminId);

            Log::write("删除商户：" . $adminId);

            return json($res);
        }
    }
}