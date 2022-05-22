<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/15
 * Time: 19:53
 */

namespace app\api\controller;

use think\Db;
use think\Controller;
use think\Request;
use app\common\model\OrderModel;
use app\common\model\SystemConfigModel;
use tool\Log;

class Ceshi extends Controller
{
    public function demo1()
    {
        return $this->fetch();
    }

}