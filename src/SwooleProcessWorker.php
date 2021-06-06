<?php
/**
 * Desc:
 * User: maozhongyu
 * Date: 2021/6/7
 * Time: 上午12:53
 */

namespace Xpx\MultiProcessWorker;

class SwooleProcessWorker extends MultiProcessWorker
{

    public function productProcesses($processNum,callable  $callBack)
    {
        for ($workPage = 1; $workPage <= $processNum; $workPage++) {
            $process = new \Swoole\Process(function () use ($workPage,$callBack) {
                $callBack($workPage);
            });
            $process->start();
        }
    }

    public function waitProcesses($processNum)
    {
        for ($i = $processNum; $i--;) {
            \Swoole\Process::wait(true);
        }
    }
}
