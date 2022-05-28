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
use app\common\model\DoRedis;
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
            $cookieModel = new CookieModel();
            $list = $cookieModel->getCookies($limit, $where);

            $data = empty($list['data']) ? array() : $list['data'];
            foreach ($data as $key => $vo) {
                $list[$key]['cookie'] = substr($vo['cookie'], 0, 30);
//                $list[$key]['add_time'] = date('Y-m-d H:i:s', $vo['add_time']);
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
            $cookieContentsArray = explode(PHP_EOL, $param['cookie_contents']);
            if (is_array($cookieContentsArray)) {
                foreach ($cookieContentsArray as $key => $v) {
                    $getCookieAccount = getJdCookieAccount($v);
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
//                if ($updateNum >= 0) {
//                     $redis = new DoRedis();
//                     $redis->setCreatePrepareOrderNumByAmount()
//                }
            }
            Log::write($param['cookie_sign'] . ',添加COOKIES：总：' . $total . "其中新增：" . $newNum . "覆盖：" . $updateNum);

            return json(modelReMsg(0, '', '总：' . $total . "其中新增：" . $newNum . "覆盖：" . $updateNum));
        }

        return $this->fetch('add');
    }
}