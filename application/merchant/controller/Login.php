<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/3/17
 * Time: 4:24 PM
 */
namespace app\merchant\controller;

use app\merchant\model\MerchantModel;
use app\merchant\model\LoginLog;
use think\Controller;

class Login extends Controller
{
    // 登录页面
    public function index()
    {
        $nodeTitle = "DV商户";

        $this->assign([
            'nodeTitle' => $nodeTitle
        ]);
        return $this->fetch();
    }

    // 处理登录
    public function doLogin()
    {
        if(request()->isPost()) {

            $param = input('post.');

            if(!captcha_check($param['vercode'])){
                return reMsg(-1, '', '验证码错误');
            }

            $log = new LoginLog();
            $merchant = new MerchantModel();
            $merchantInfo = $merchant->getMerchantByName($param['merchant_username']);
            if(0 != $merchantInfo['code'] || empty($merchantInfo['data'])){
                $log->writeLoginLog($param['merchant_username'], 2);
                return reMsg(-2, '', '用户名密码错误1!');
            }

            if(!checkPassword($param['merchant_password'], $merchantInfo['data']['merchant_password'])){
                $log->writeLoginLog($param['merchant_username'], 2);
                return reMsg(-3, $param['merchant_password'].$merchantInfo['data']['merchant_password'], '用户名密码错误2!');
            }

            // 设置session标识状态
            session('merchant_user_name', $merchantInfo['data']['merchant_username']);
            session('merchant_user_id', $merchantInfo['data']['merchant_id']);
            session('merchant_role_id', 8);
            session('node_title', "DV商户后台");

            // 维护上次登录时间
            $merchant->updateMerchantInfoById($merchantInfo['data']['merchant_id'], ['last_login_time' => date('Y-m-d H:i:s')]);

            $log->writeLoginLog($param['merchant_username']."(商户)", 1);

            return reMsg(0, url('index/index'), '登录成功');
        }
    }

    public function loginOut()
    {
        session('merchant_user_name', null);
        session('merchant_user_id', null);

        $this->redirect(url('login/index'));
    }
}
