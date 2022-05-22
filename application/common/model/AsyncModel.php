<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/3/17
 * Time: 4:48 PM
 */

namespace app\common\model;


use think\Model;
//**
class AsyncModel extends Model
{

    /**
     * 是否需要返回HTTP头信息
     */
    public $curlopt_header = 0;
    /**
     * 单个CURL调用超时限制
     */
    public $curlopt_timeout = 20;
    private $param = array();
    /**
     * 构造函数（可直接传入请求参数）
     *
     * @param array 可选
     * @return void
     */
    public function __construct($param = False)
    {
        if ($param !== False)
        {
            $this->param = $this->init_param($param);
        }
    }
    /**
     * 设置请求参数
     *
     * @param array
     * @return void
     */
    public function set_param($param)
    {
        $this->param = $this->init_param($param);
    }
    /**
     * 发送请求
     * @return array
     */
    public function send()
    {
        if(!is_array($this->param) || !count($this->param))
        {
            return False;
        }
        $curl = $ret = array();
        $handle = curl_multi_init();
        foreach ($this->param as $k => $v)
        {
            $param = $this->check_param($v);
            if (!$param) $curl[$k] = False;
            else $curl[$k] = $this->add_handle($handle, $param);
        }
        $this->exec_handle($handle);
        foreach ($this->param as $k => $v)
        {
            if ($curl[$k])
            {
                $ret[$k] = curl_multi_getcontent($curl[$k]);
                curl_multi_remove_handle($handle, $curl[$k]);
            } else {
                $ret[$k] = False;
            }
        }
        curl_multi_close($handle);
        return $ret;
    }
    //以下为私有方法
    private function init_param($param)
    {
        $ret = False;
        if (isset($param['url']))
        {
            $ret = array($param);
        } else {
            $ret = isset($param[0]) ? $param : False;
        }
        return $ret;
    }
    private function check_param($param = array())
    {
        $ret = array();
        if (is_string($param))
        {
            $url = $param;
        } else {
            extract($param);
        }
        if (isset($url))
        {
            $url = trim($url);
            $url = stripos($url, 'http://') === 0 ? $url : NULL;
        }
        if (isset($data) && is_array($data) && !empty($data))
        {
            $method = 'POST';
        } else {
            $method = 'GET';
            unset($data);
        }
        if (isset($url)) $ret['url'] = $url;
        if (isset($method)) $ret['method'] = $method;
        if (isset($data)) $ret['data'] = $data;
        $ret = isset($url) ? $ret : False;
        return $ret;
    }
    private function add_handle($handle, $param)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $param['url']);
        curl_setopt($curl, CURLOPT_HEADER, $this->curlopt_header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->curlopt_timeout);
        if ($param['method'] == 'POST')
        {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $param['data']);
        }
        curl_multi_add_handle($handle, $curl);
        return $curl;
    }
    private function exec_handle($handle)
    {
        $flag = null;
        do {
            curl_multi_exec($handle, $flag);
        } while ($flag > 0);
    }
}