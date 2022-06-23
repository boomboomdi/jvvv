<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/3/17
 * Time: 4:48 PM
 */

namespace app\admin\model;

use think\Db;
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
//                $cookie['last_use_time'] = time();
                $cookie['order_desc'] = '上传更新';
                $cookie['error_times'] = 0;
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
        Db::startTrans();
        try {
            $where["status"] = 1;
            if (!empty($account)) {
                $where['account'] = $account;
            }
            $info = $this->where($where)->order("use_times,last_use_time asc")->lock(true)->find();
            if (!empty($info)) {
                $update['last_use_time'] = time();
                $update['use_times'] = $info['use_times'] + 1;
                $updateRes = Db::table('bsa_cookie')
                    ->where('id','=',$info['id'])
                    ->update($update);
                if (!$updateRes){
                    logs(json_encode([
                        "time" => date("Y-m-d H:i:s", time()),
                        'getUseCookie' => $info,
                        "updateRes" => $updateRes
                    ]), 'getUseCookieUpdateFail');
                }
                Db::commit();
                return modelReMsg(0, $info, 'ok');
            }

            Db::rollback();
            return modelReMsg(-2, "", '无可用下单COOKIE');
        } catch (\Exception $exception) {
            Db::rollback();
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'getUseCookie_exception');
            return modelReMsg(-11, '', $exception->getMessage());
        } catch (\Error $error) {
            Db::rollback();
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
            $update['error_times'] = $has['error_times'] + 1;
            $update['order_desc'] = '失效(预拉错误' . ($has['error_times'] + 1) . ')';
            $update['status'] = 1;
            if ($has['error_times'] > 2) {
                $update['status'] = 2;
                $update['order_desc'] = '禁用(预拉失败超过三次)';
            }
            $this->where($where)->update($update);
        } catch (\Exception $e) {

            return modelReMsg(-1, '', $e->getMessage());
        }

        return modelReMsg(0, '', '更新成功');
    }

}