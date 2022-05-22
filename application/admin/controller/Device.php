<?php
/**
 * Created by PhpStorm.
 * User: bl
 * Date: 2020/12/20
 * Time: 12:57
 */

namespace app\admin\controller;

use app\common\model\DeviceModel;
use app\admin\validate\DeviceValidate;
use tool\Log;
use app\admin\model\OrderModel;
use think\Db;
use Zxing\QrReader;

class Device extends Base
{
    //设备列表
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
            $deviceModel = new DeviceModel();
//            var_dump(session("admin_role_id"));
//            exit;
            $studio = session("admin_role_id");
            if ($studio == 7) {
                $where['studio'] = ['=', session("admin_user_name")];   //默认情况下 登录名就是 工作室标识
//                $where[] = ['studio', "=", session("admin_user_name")];  //默认情况下 登录名就是 工作室标识
            }
            $list = $deviceModel->getDevices($limit, $where);
//            var_dump(DB::table("bsa_device")->getLastSql());
            $data = empty($list['data']) ? array() : $list['data'];
            foreach ($data as $key => $vo) {
                $data[$key]['add_time'] = date('Y-m-d H:i:s', $data[$key]['add_time']);
                $data[$key]['heart_time'] = date('Y-m-d H:i:s', $vo['heart_time']);

                if (!empty($data[$key]['qr_update_time']) && $data[$key]['qr_update_time'] != 0) {
                    $data[$key]['update_time'] = date('Y-m-d H:i:s', $data[$key]['qr_update_time']);
                }

                //订单状态 :是否可用1：可用2：不可用（心跳正常且开启情况下是否可下单）
                //设备状态：是否开启1：开启中2已关闭
                //心跳2：离线  1在线
            }
            $list['data'] = $data;
            if (0 == $list['code']) {
                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 添加设备
    public function addDevice()
    {
        if (request()->isPost()) {

            $param = input('post.');

            $validate = new DeviceValidate();
            $param['studio'] = session("admin_user_name");
            $param['add_time'] = time();
            if (!$validate->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }
            if (isset($param['thumbnail']) && !empty($param['thumbnail'])) {
                $imgUrl = request()->root(true) . $param['thumbnail'];
                $qrcode = new QrReader($imgUrl);
                $imgUrlText = $qrcode->text(); //return decoded text from QR Code
                $param['qr_url'] = $imgUrlText;
            }

            $admin = new DeviceModel();
            $res = $admin->addDevice($param);

            Log::write("添加账号/设备：" . $param['account'] . "(studio:" . $param['studio'] . ")");

            return json($res);
        }


        return $this->fetch('add');
    }

    // 编辑设备
    public function editDevice()
    {
        if (request()->isPost()) {

            $param = input('post.');
            unset($param['file']);
            $validate = new DeviceValidate();
            if (!$validate->scene('edit')->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }

            if (isset($param['studio'])) {
                $param['studio'] = $param['studio'];
            }

            if (isset($param['thumbnail']) && !empty($param['thumbnail'])) {
                $imgUrl = request()->root(true) . $param['thumbnail'];
                $qrcode = new QrReader($imgUrl);
                $imgUrlText = $qrcode->text(); //return decoded text from QR Code
                $param['qr_url'] = $imgUrlText;
            }

            $device = new DeviceModel();
            $res = $device->editDevice($param);

            Log::write("编辑账号/设备：" . $param['account'] . "(studio:" . $param['studio'] . ")");

            return json($res);
        }

        $deviceId = input('param.id');
        $device = new DeviceModel();

        $this->assign([
            'device' => $device->getDeviceById($deviceId)['data']
        ]);
//        var_dump($device->getDeviceById($deviceId)['data']);exit;
        return $this->fetch('edit');
    }

    /**
     * 删除商户
     * @return \think\response\Json
     */
    public function delDevice()
    {
        if (request()->isAjax()) {

            $id = input('param.id');

            $device = new DeviceModel();
            $res = $device->delDevice($id);

            Log::write(session("admin_role_id") . ":" . session("admin_user_name") . "删除设备：" . $id);

            return json($res);
        }
    }

    /**
     * 手动回调
     * @return void
     */
    public function notify()
    {
        try {
            $param = input('post.');
            if (!isset($param['order_no']) || empty($param['order_no'])) {
                return reMsg(-1, '', "回调错误！");
            }
            //查询订单
            $order = Db::table("bsa_order")->where("order_no", $param['no'])->find();
            $orderModel = new \app\common\model\OrderModel();
            return $orderModel->orderNotify($order, 2);

        } catch (\Exception $exception) {
            return reMsg(-2, "", $exception->getMessage());

        } catch (\Error $error) {
            return reMsg(-2, "", $error->getMessage());
        }
    }

    // 上传缩略图
    public function uploadQrImg()
    {
        if (request()->isAjax()) {

            $file = request()->file('file');
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload');
            if ($info) {
                $src = '/upload' . '/' . date('Ymd') . '/' . $info->getFilename();
                return json(msg(0, ['src' => $src], ''));
            } else {
                // 上传失败获取错误信息
                return json(msg(-1, '', $file->getError()));
            }
        }
    }
}