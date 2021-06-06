<?php
/**
 * Desc:
 * User: maozhongyu
 * Date: 2021/6/7
 * Time: 上午12:53
 */

namespace Xpx\MultiProcessWorker;

class PcntlProcessWorker extends MultiProcessWorker
{

    public function productProcesses($processNum, callable $callBack)
    {
        for ($workPage = 1; $workPage <= $processNum; $workPage++) {
            $pid = \pcntl_fork(); //创建成功会返回子进程id
            if ($pid < 0) {
                exit('子进程创建失败' . $pid);
            } else if ($pid > 0) {
                //父进程空间，返回子进程id,不做事情
                //echo "子进程{$pid} start".PHP_EOL;
            } else { //返回为0子进程空间
                // echo "i am child space,my id:{$workPage}".PHP_EOL;
                $callBack($workPage);
                exit;
            }
        }
    }

    public function waitProcesses($processNum)
    {
        $status = 0;
        for ($i = 1; $i <= $processNum; $i++) {
            \pcntl_wait($status);
        }
    }
}