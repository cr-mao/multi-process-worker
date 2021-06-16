<?php
/**
 * Desc: swoole process 模式处理器
 * User: maozhongyu
 * Date: 2021/6/7
 * Time: 上午12:53
 */

namespace Crmao\MultiProcessWorker;


/**
 *  swoole process 模式处理器
 *  swoole 信号是异步信号，暂不考虑加上信号功能
 * Class SwooleProcessWorker
 * @package Crmao\MultiProcessWorker
 */
class SwooleProcessWorker implements MultiProcessWorkerInterface
{
//    public $workPids = []; //子进程，工作进程
//    public $parentPid; //主进程
    /**
     * 生成工作子进程
     * @param $processNum   进程数
     * @param callable $callBack 外部自定义回调函数
     */
    public function productProcesses($processNum, callable $callBack)
    {
        // 保存主进程
//        $this->parentPid = getmypid();
        for ($workPage = 1; $workPage <= $processNum; $workPage++) {
            $process = new \Swoole\Process(function () use ($workPage, $callBack) {
//                $this->workPids[] = getmypid();
                $callBack($workPage);
            });
            $process->start();
        }
    }

    /**
     * 等待收回子进程，避免僵尸进程产生 todo 优化
     * @param $processNum
     */
    public function waitProcesses($processNum)
    {
        for ($i = $processNum; $i--;) {
            \Swoole\Process::wait(true);
        }
    }

}
