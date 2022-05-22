<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/2/28
 * Time: 8:23 PM
 */
namespace app\merchant\controller;

use app\merchant\model\MerchantModel;
use app\merchant\validate\MerchantValidate;
use tool\Log;

class Merchant extends Base
{
    // 商户
    public function index()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');
            $merchantName = input('param.merchant_name'); //商户名称

            $where = [];
            if (!empty($merchantName)) {
                $where[] = ['merchant_name', 'like', $merchantName . '%'];
            }

            $admin = new MerchantModel();
            $list = $admin->getMerchants($limit, $where);
            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    public function info()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');
//            $adminName = input('param.admin_name');

            $where = [];
            if (!empty($adminName)) {
//                $where[] = ['admin_name', 'like', $adminName . '%'];
            }

            $admin = new MerchantModel();
            $list = $admin->getMerchantById(session("merchant_user_id"));

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 编辑商户
    public function editMerchant()
    {
        if(request()->isPost()) {

            $param = input('post.');

            $validate = new MerchantValidate();
            if(!$validate->scene('edit')->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }

            if(isset($param['merchant_password'])) {
                $param['merchant_password'] = makePassword($param['merchant_password']);
            }

            $merchant = new MerchantModel();
            $res = $merchant->editMerchant($param);

            Log::write("编辑商户：" . $param['merchant_name']);

            return json($res);
        }

        $merchantId = input('param.merchant_id');
        $merchant = new MerchantModel();

        $this->assign([
            'merchant' => $merchant->getMerchantById($merchantId)['data'],
        ]);

        return $this->fetch('edit');
    }

    /**
     * 删除商户
     * @return \think\response\Json
     */
    public function delMerchant()
    {
        if(request()->isAjax()) {

            $merchantId = input('param.merchantId');

            $admin = new MerchantModel();
            $res = $admin->delMerchant($merchantId);

            Log::write("删除商户：" . $merchantId);

            return json($res);
        }
    }
}