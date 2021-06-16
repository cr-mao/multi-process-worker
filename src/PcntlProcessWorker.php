<?php
/**
 * Desc:  pcntl 模式处理器
 * User: maozhongyu
 * Date: 2021/6/7
 * Time: 上午12:53
 */

namespace Crmao\MultiProcessWorker;

/**
 * pcntl 模式处理器
 * Class PcntlProcessWorker
 * @package Crmao\MultiProcessWorker
 */
class PcntlProcessWorker implements MultiProcessWorkerInterface
{
    public $workPids = []; //存放子进程id(工作进程id)
    public $parentPid; //主进程id

    /**
     * 生成工作子进程
     * @param $processNum   进程数
     * @param callable $callBack 外部自定义回调函数
     */
    public function productProcesses($processNum, callable $callBack)
    {
        // 保存主进程
        $this->parentPid = getmypid();
        for ($workPage = 1; $workPage <= $processNum; $workPage++) {
            $pid = \pcntl_fork(); //创建成功会返回子进程id
            if ($pid < 0) {
                exit('子进程创建失败' . $pid);
            } else if ($pid > 0) {
                // echo "子进程{$pid} start".PHP_EOL;
                //父进程空间，返回子进程id, 保存子进程
                $this->workPids[] = $pid;
            } else { //返回为0子进程空间
                // echo "i am child space,my workpage 进程逻辑空间:{$workPage}".PHP_EOL;
                $callBack($workPage);
                exit;
            }
        }
    }


    /**
     * 信号处理，仅支持ctrl+c
     * @param $sigo
     */
    public function signalHandler($sigo)
    {
        switch ($sigo) {
            case SIGINT:
                //  echo "按下ctrl+c,关闭所有进程";
                $this->stopAllProcesses();
                exit();
                break;
        }
    }


    /**
     * 主动关闭子进程
     */
    public function stopAllProcesses()
    {
        foreach ($this->workPids as $index => $pid) {
//            $this->debug("from stopAllProcesses:{$pid}收到信号,子进程退出");
            \posix_kill($pid, SIGKILL); //结束进程,主动杀死，其实一般情况下，父进程在终端被杀死，子进程也会被杀死
            unset($this->workPids[$index]);
        }
    }


    /**
     * 等待进程退出，并接受信号处理
     * @param $processNum
     */
    public function waitProcesses($processNum)
    {
        //ctrl+c  手动注册信号事件回调,是不会自动执行的,
        pcntl_signal(SIGINT, array($this, 'signalHandler'), false); //重启woker进程信号
        $status = 0;
        while (1) {
            //子进程都退出，主进程也退出
            if (empty($this->workPids)) {
                break;
            }
            // 当发现信号队列,一旦发现有信号就会触发进程绑定事件回调
            \pcntl_signal_dispatch();
            $pid = \pcntl_wait($status); //当进程退出，或 中断进程信号到达之后就会被中断
//            $this->debug("pcntl_wait result is {$pid}");
            //主进程收到信号，关闭所有子进程
            if ($pid == $this->parentPid) {
                $this->stopAllProcesses();
                //子进程工作完毕退出，或者异常退出
            } else if ($pid > 0 && in_array($pid, $this->workPids)) {
                $index = array_search($pid, $this->workPids);
//                $this->debug("{$pid}收到信号或脚本结束进程退出");
                \posix_kill($pid, SIGKILL);
                unset($this->workPids[$index]);
            }
            //这个应该没用,如果前面重启进程，那么这个就有必要，保险。
            \pcntl_signal_dispatch();
        }
    }

    /**
     * 调试函数
     * @param $msg
     */
    public function debug($msg)
    {
        echo PHP_EOL . $msg . PHP_EOL;
    }
}