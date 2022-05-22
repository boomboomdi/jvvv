<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/2/28
 * Time: 8:23 PM
 */
namespace app\admin\controller;

use app\admin\model\StudioModel;
use app\admin\validate\StudioValidate;
use think\Db;
use tool\Log;

class Studio extends Base
{
    // 商户列表
    public function index()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');
            $studioName = input('param.studio_name');
            $studio = input('param.studio');

            $where = [];
            if (!empty($studioName)) {
                $where[] = ['studio_name', 'like', $studioName . '%'];
            }
            if (!empty($studio)) {
                $where[] = ['studio', 'like', $studio . '%'];
            }

            $studio = new StudioModel();
            $list = $studio->getStudios($limit, $where);
            $db = new Db();
//            $lsatSql = $db::table('bsa_device')->getLastSql();
//            var_dump($lsatSql);exit;
//            var_dump($list['data']);exit;
            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 添加工作室
    public function addStudio()
    {
        if(request()->isPost()) {

            $param = input('post.');
            $validate = new StudioValidate();
            if(!$validate->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }

//            $param['studio_password'] = makePassword($param['studio_password']);

            $studio = new StudioModel();
            $res = $studio->addStudio($param);

            Log::write("添加工作室：" . $param['studio_name']."绑定账号:".$param['studio']);

            return json($res);
        }



        return $this->fetch('add');
    }

    // 编辑工作室
    public function editStudio()
    {
        if(request()->isPost()) {

            $param = input('post.');

            $validate = new StudioValidate();
            if(!$validate->scene('edit')->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }

            $studio = new StudioModel();
            $res = $studio->editStudio($param);

            Log::write("编辑商户：" . $param['admin_name']);

            return json($res);
        }

        $studioId = input('param.studio_id');
        $Studio = new StudioModel();

        $this->assign([
            'studioId' => $Studio->getStudioById($studioId)['data']
        ]);

        return $this->fetch('edit');
    }

    /**
     * 删除工作室
     * @return \think\response\Json
     */
    public function delStudio()
    {
        if(request()->isAjax()) {

            $studioId = input('param.studio_id');

            $studio = new StudioModel();
            $res = $studio->delStudio($studioId);

            Log::write("删除工作室：" . $studioId);

            return json($res);
        }
    }
}