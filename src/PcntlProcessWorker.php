<?php
/**
 * Desc:  pcntl 模式处理器
 * User: maozhongyu
 * Date: 2021/6/7
 * Time: 上午12:53
 */

namespace Xpx\MultiProcessWorker;

/**
 * pcntl 模式处理器
 * Class PcntlProcessWorker
 * @package Xpx\MultiProcessWorker
 */
class PcntlProcessWorker  implements MultiProcessWorkerInterface
{

    /**
     * 生成工作子进程
     * @param $processNum   进程数
     * @param callable $callBack 外部自定义回调函数
     */
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

    /**
     * 等待收回子进程，避免僵尸进程产生 todo 优化
     * @param $processNum
     */
    public function waitProcesses($processNum)
    {
        $status = 0;
        for ($i = 1; $i <= $processNum; $i++) {
            \pcntl_wait($status);
        }
    }
}