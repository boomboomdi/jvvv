<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/26
 * Time: 22:59
 */

namespace app\common\model;

use think\Model;
use think\Db;

class SystemConfigModel extends Model
{


    /**
     * 核销单冻结期
     * @return int
     */
    public static function getOrderLockTime()
    {
        try {
//            $where[] = ["configName", "=", "orderLockTime"];
//            $where[] = ["status", "=", 1];
//            $config = Db::table('bsa_system_config')
//                ->where($where)
//                ->find();
//            if (isset($config['configContent']) && !empty($config['configContent'])) {
//                return (int)$config['configContent'];
//            }
            return 180;
        } catch (\Exception $exception) {
            return 300;
        } catch (\Error $error) {
            return 300;
        }
    }

    /**
     * 订单冻结期
     * @return int
     */
    public static function getOrderHxLockTime()
    {
        try {
//            $where[] = ["configName", "=", "orderHxLockTime"];
//            $where[] = ["status", "=", 1];
//            $config = Db::table('bsa_system_config')
//                ->where($where)
//                ->find();
//            if (isset($config['configContent']) && !empty($config['configContent'])) {
//                return (int)$config['configContent'];
//            }
            return 280;
        } catch (\Exception $exception) {
            return 1800;
        } catch (\Error $error) {
            return 1800;
        }
    }

    /**
     * 订单冻结期
     * @return int
     */
    public static function getOrderHxCanUseTime()
    {
        try {
//            $where[] = ["configName", "=", "orderHxLockTime"];
//            $where[] = ["status", "=", 1];
//            $config = Db::table('bsa_system_config')
//                ->where($where)
//                ->find();
//            if (isset($config['configContent']) && !empty($config['configContent'])) {
//                return (int)$config['configContent'];
//            }
            return 280;
        } catch (\Exception $exception) {
            return 300;
        } catch (\Error $error) {
            return 300;
        }
    }

    /**
     * 获取订单自动查询时间(第三次-第二次间隔)
     * @return int
     */
    public static function getAutoCheckOrderTime()
    {
        try {
//            $where[] = ["configName", "=", "autoCheckOrderTime"];
//            $where[] = ["status", "=", 1];
//            $config = Db::table('bsa_system_config')
//                ->where($where)
//                ->find();
////            var_dump($config);exit;
//            if (isset($config['configContent']) && !empty($config['configContent'])) {
//                return (int)$config['configContent'];
//            }
            return 30;
        } catch (\Exception $exception) {
            return 30;
        } catch (\Error $error) {
            return 30;
        }
    }

    /**
     * 获取订单自动查询时间(第三次-第二次间隔)
     * @return int
     */
    public static function getOrderShowTime()
    {
        try {
//            $where[] = ["configName", "=", "orderShowTime"];
//            $where[] = ["status", "=", 1];
//            $config = Db::table('bsa_system_config')
//                ->where($where)
//                ->find();
//            if (isset($config['configContent']) && !empty($config['configContent'])) {
//                return (int)$config['configContent'];
//            }
            return 180;
        } catch (\Exception $exception) {
            return 300;
        } catch (\Error $error) {
            return 300;
        }
    }
}
