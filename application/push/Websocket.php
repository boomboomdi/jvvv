<?php

namespace app\push;


use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use Workerman\Worker;
use GatewayWorker\Gateway;
use GatewayWorker\BusinessWorker;
use GatewayWorker\Register;

class Websocket extends Command
{
    protected function configure()
    {
        $this->setName('websocket')->setDescription('Here is the remark ')
            ->addArgument('control', Argument::REQUIRED, 'start stop restart reload status connections')
            ->addArgument('daemon', Argument::OPTIONAL, '-d');
    }

    protected function execute(Input $input, Output $output)
    {
        // gateway 进程，这里使用Text协议，可以用telnet测试
        $gateway = new Gateway("websocket://0.0.0.0:8282");
        // gateway名称，status方便查看
        $gateway->name = 'AlipayReceivablesMgrGateway';
        // gateway进程数
        $gateway->count = 4;     
        // 本机ip，分布式部署时使用内网ip
        $gateway->lanIp = '127.0.0.1';
        // 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
        // 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口
        $gateway->startPort = 10001;
        // 服务注册地址
        $gateway->registerAddress = '127.0.0.1:10000';

        // register 必须是text协议
        $register = new Register('text://0.0.0.0:10000');

        // bussinessWorker 进程
        $worker = new BusinessWorker();
        // worker名称
        $worker->name = 'AlipayReceivablesMgrWorker';
        // bussinessWorker进程数量
        $worker->count = 4;
        // 服务注册地址
        $worker->registerAddress = '127.0.0.1:10000';

        // 如果不是在根目录启动，则运行runAll方法
        if (!defined('GLOBAL_START')) {

            global $argv;

            $argv[1] = $input->getArgument('control');
            $argv[2] = $input->getArgument('daemon');

            Worker::runAll();


        }
    }
}