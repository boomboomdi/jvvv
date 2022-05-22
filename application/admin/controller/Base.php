<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/2/28
 * Time: 8:24 PM
 */
namespace app\admin\controller;

////define('DIRECTORY_SEPARATOR', "/");
//define('DS', DIRECTORY_SEPARATOR);
//defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
//defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(APP_PATH)) . DS);
use think\Controller;
use tool\Auth;

class Base extends Controller
{
    public function initialize()
    {
        if(empty(session('admin_user_name'))){

            $this->redirect(url('login/index'));
        }

        $controller = lcfirst(request()->controller());
        $action = request()->action();
        $checkInput = $controller . '/' . $action;

        $authModel = Auth::instance();
        $skipMap = $authModel->getSkipAuthMap();
        if (!isset($skipMap[$checkInput])) {

            $flag = $authModel->authCheck($checkInput, session('admin_role_id'));


            if (!$flag) {
                if (request()->isAjax()) {
                    return json(reMsg(-403, '', '无操作权限'));
                } else {
                    $this->error('无操作权限');
                }
            }
        }

        $this->assign([
            'admin_name' => session('admin_user_name'),
            'admin_id' => session('admin_user_id'),
            'node_title' => session('node_title')
        ]);
    }
}