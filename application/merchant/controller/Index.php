<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/2/17
 * Time: 11:33 AM
 */
namespace app\merchant\controller;

use think\App;
use tool\Auth;
use app\merchant\model\MerchantModel;

class Index extends Base
{
    public function index()
    {
        $authModel = new Auth();
        $menu = $authModel->getAuthMenuByNodeUser(session('merchant_role_id'));
        $this->assign([
            'menu' => $menu
        ]);

        return $this->fetch();
    }

    /**
     * 商户信息
     * @return \think\response\Json
     */
    public function info()
    {
        $merchant = new MerchantModel();
        $list = $merchant->getMerchantById(session("merchant_user_id"));
        if (0 != $list['code']) {
            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);

        }

//        return json(['code' => 0, 'msg' => 'ok', 'data' => $list['data']->all()]);
        $this->assign([
            'merchantInfo' => $list['data']
        ]);
        return $this->fetch();
    }

    public function index2()
    {
        //baseAdmin!@#-

        $pas = makePassword("admin");
        var_dump($pas);exit;
    }
    public function home()
    {
        $this->assign([
            'tp_version' => App::VERSION
        ]);

        return $this->fetch();
    }

    // 修改密码
    public function editPwd()
    {
        if (request()->isPost()) {

            $param = input('post.');

            if ($param['new_password'] != $param['rep_password']) {
                return json(['code' => -1, 'data' => '', 'msg' => '两次密码输入不一致']);
            }

            // 检测旧密码
            $merchant = new MerchantModel();
            $merchantInfo = $merchant->getMerchantById(session('merchant_user_id'));

            if(0 != $merchantInfo['code'] || empty($merchantInfo['data'])){
                return json(['code' => -2, 'data' => '', 'msg' => '商户不存在']);
            }

            if(!checkPassword($param['password'], $merchantInfo['data']['merchant_password'])){
                return json(['code' => -3, 'data' => '', 'msg' => '旧密码错误']);
            }

            $merchant->updatemerchantInfoById(session('merchant_user_id'), [
                'merchant_password' => makePassword($param['new_password'])
            ]);

            return json(['code' => 0, 'data' => '', 'msg' => '修改密码成功']);
        }

        return $this->fetch('pwd');
    }
}
