<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/3/17
 * Time: 4:48 PM
 */

namespace app\admin\model;

use think\facade\Log;
use think\Model;

class CookieModel extends Model
{
    protected $table = 'bsa_cookie';

    /**
     * 获取cookie
     * @param $limit
     * @param $where
     * @return array
     */
    public function getCookies($limit, $where)
    {
        $prefix = config('database.prefix');
        try {
            $res = $this->where($where)
                ->order('id', 'desc')->paginate($limit);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }
        return modelReMsg(0, $res, 'ok');
    }

    /**
     * 增加核销商
     * @param $cookie
     * @return array
     */
    public function addCookie($cookie)
    {
        $code = 3;
        try {
            $has = $this->where('account', $cookie['account'])->findOrEmpty()->toArray();
            if (!empty($has)) {
                $code = 1;
                $cookie['last_use_time'] = time();
                $cookie['status'] = 1;
                $this->where('account', $cookie['account'])->update($cookie);
            } else {
                $code = 0;
                $cookie['add_time'] = date("Y-m-d H:i:s", time());
                $this->insert($cookie);
            }
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg($code, '', '添加核销商成功');
    }


    /**
     * 获取可用cookie
     * @param $account
     * @return array
     */
    public function getUseCookie($account = "")
    {
        $where = [];
        try {
            $where["status"] = 1;
            if (!empty($account)) {
                $where['account'] = $account;
            }
            $info = $this->where($where)->order("last_use_time asc")->find();
            if (!empty($info)) {
                $update['last_use_time'] = time();
                $update['use_times'] = $info['use_times'] + 1;
                $this->save($update, ['id' => $info['id']]);
                return modelReMsg(0, $info, 'ok');
            }
            return modelReMsg(-2, "", '无可用下单COOKIE');
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'getUseCookie_exception');
            return modelReMsg(-11, '', $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'getUseCookie_error');
            return modelReMsg(-22, "getUseCookie异常" . $error->getMessage());
        }

    }

    /**
     * 修改状态不可用
     * @param $param
     * @return array
     */
    public function editCookie($where, $update)
    {
        try {

            $has = $this->where($where)
                ->findOrEmpty()->toArray();
            if (empty($has)) {
                return modelReMsg(-2, '', 'ck不存在！');
            }

            $this->where($where)->update($update);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '编辑成功');
    }

}