<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/10/11
 * Time:  14:23
 */

namespace app\admin\controller;

use app\admin\model\CookieModel;
use app\admin\validate\CookieValidate;
use think\Validate;
use tool\Log;

class Cookie extends Base
{
    // cookie
    public function index()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
            $account = input('param.account');

            $where = [];
            if (!empty($account)) {
                $where['account'] = ['=', $account];
            }
//            if (!empty($account)) {
//                $where[] = ['account', 'like', $account . '%'];
//            }
            $cookieModel = new CookieModel();
//            var_dump(session("admin_role_id"));
//            exit;
//            $studio = session("admin_role_id");
//            if ($studio == 7) {
//                $where['studio'] = ['=', session("admin_user_name")];   //默认情况下 登录名就是 工作室标识
////                $where[] = ['studio', "=", session("admin_user_name")];  //默认情况下 登录名就是 工作室标识
//            }
            $list = $cookieModel->getCookies($limit, $where);
            $data = empty($list['data']) ? array() : $list['data'];
//            var_dump($data);exit;
            foreach ($data as $key => $vo) {
                $data[$key]['cookie'] = substr($vo['cookie'], 0, 30);
////                $data[$key]['add_time'] = date('Y-m-d H:i:s', $data[$key]['add_time']);
////                $data[$key]['heart_time'] = date('Y-m-d H:i:s', $vo['heart_time']);
////
////                if (!empty($data[$key]['qr_update_time']) && $data[$key]['qr_update_time'] != 0) {
////                    $data[$key]['update_time'] = date('Y-m-d H:i:s', $data[$key]['qr_update_time']);
////                }
//
//                //订单状态 :是否可用1：可用2：不可用（心跳正常且开启情况下是否可下单）
//                //设备状态：是否开启1：开启中2已关闭
//                //心跳2：离线  1在线
            }
            $list['data'] = $data;
            if (0 == $list['code']) {
                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 添加Cookie
    public function addCookie()
    {
        if (request()->isPost()) {

            $param = input('post.');

            $cookie = new CookieModel();
            $validate = new CookieValidate();
            $param['add_time'] = time();
            $param['last_use_time'] = time();
            if (!$validate->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }
            $updateNum = 0;
            $newNum = 0;
            $total = 0;
//            $cookieContentsArray = explode(PHP_EOL, $param['cookie_contents']);
            $cookieContentsArray = explode("\n", $param['cookie_contents']);
            if (is_array($cookieContentsArray)) {
                foreach ($cookieContentsArray as $key => $v) {
                    $getCookieAccount = getCookieAccount($v);
                    if ($getCookieAccount) {
                        $addCookieParam['last_use_time'] = time();
                        $addCookieParam['cookie'] = $v;
                        $addCookieParam['cookie_sign'] = $param['cookie_sign'];
                        $addCookieParam['account'] = $getCookieAccount;
                        $res = $cookie->addCookie($addCookieParam);
                        //更新+1
                        if ($res['code'] == 1) {
                            $updateNum++;
                        }
                        //新增+1
                        if ($res['code'] == 0) {
                            $newNum++;
                        }
                    }
                    $total++;
                }
            }
            Log::write($param['cookie_sign'] . ',添加COOKIES：总：' . $total . "其中新增：" . $newNum . "覆盖：" . $updateNum);

            return json(modelReMsg(0, '', '总：' . $total . "其中新增：" . $newNum . "覆盖：" . $updateNum));
        }

        return $this->fetch('add');
    }
}