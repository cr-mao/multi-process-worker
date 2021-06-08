<?php
/**
 * Desc: MultiProcessWorkerInterface
 * User: maozhongyu
 * Date: 2021/6/7
 * Time: 上午12:46
 */

namespace Xpx\MultiProcessWorker;

interface MultiProcessWorkerInterface
{
    //生产子进程
    public function productProcesses($processNum, callable $callBack);
    //等待子进程退出
    public function waitProcesses($processNum);
}