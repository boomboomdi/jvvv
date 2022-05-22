<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/2/28
 * Time: 8:24 PM
 */
namespace app\merchant\controller;

use think\Controller;
use tool\Auth;

class Base extends Controller
{
    /**
     * 角色类型
     * @var \think\View
     */
    protected $node_user;
    public function initialize()
    {
        $this->node_user = "merchant";
        if(empty(session('merchant_user_name'))){

            $this->redirect(url('login/index'));
        }

//        $model = request()->model();
//        var_dump($model);exit;
        $controller = lcfirst(request()->controller());
        $action = request()->action();
        $checkInput = $controller . '/' . $action;
//        var_dump($checkInput);exit;
        $authModel = Auth::instance();
        $skipMap = $authModel->getSkipAuthMap(session('merchant_role_id'));
//        var_dump($checkInput);
//        var_dump($skipMap);exit;
        if (!isset($skipMap[$checkInput])) {
            $flag = $authModel->authCheck($checkInput, session('merchant_role_id'));
            if (!$flag) {
                if (request()->isAjax()) {
                    echo "11";exit;
                    return json(reMsg(-403, '', '无操作权限'));
                } else {
                    echo "22";exit;
                    $this->error('无操作权限');
                }
            }
        }

        $this->assign([
            'merchant_user_name' => session('merchant_username'),
            'merchant_id' => session('merchant_id'),
            'nodeTitle' => "DV商户后台"
        ]);
    }
}