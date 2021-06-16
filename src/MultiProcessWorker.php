<?php
/**
 * Desc: 多进程任务处理器
 * User: maozhongyu
 * Date: 2021/6/7
 * Time: 上午12:43
 */

namespace Crmao\MultiProcessWorker;

/**
 * 多任务处理器
 * Class MultiProcessWorker
 * @package Crmao\MultiProcessWorker
 */
class MultiProcessWorker
{
    public $workerNum = 1; //子进程个数,默认一个
    public $maxWorkerNum = 128; //最多开启子进程个数
    public $onWork = null; //工作进程的回调函数，用于自定义处理任务
    public $mode = 1; //工作模式  （1=>pcntl，2=>swoole Process (单进程) ），默认使用 pcntl
    const  modePcntl = 1;  // pcntl模式
    const  modeSwooleProcess = 2;// swoole Process模式,  https://wiki.swoole.com/#/process/process?id=process


    /**
     * MultiProcessWorker constructor.
     * @param int $workerNum 工作进程数
     * @param int $mode 工作模式 1=>pcntl    2=>swoole process
     */
    public function __construct(int $workerNum, int $mode = self::modePcntl)
    {
        $this->workerNum = $workerNum;
        $this->mode = $mode;
    }


    /**
     * 检测工作模式 ，扩展是否符合要求
     */
    private function checkMode()
    {
        if (!in_array($this->mode, [self::modePcntl, self::modeSwooleProcess])) {
            throw new MultiProcessWorkerException("unvailable mode");
        }
        switch (true) {
            case $this->mode == self::modePcntl:
                if (!\extension_loaded("pcntl")) {
                    throw new MultiProcessWorkerException("pcntl-ext is not installed");
                }
                break;
            case $this->mode == self::modeSwooleProcess:
                if (!\extension_loaded("swoole")) {
                    throw new MultiProcessWorkerException("swoole-ext is not installed");
                }
                break;
            default :
                return;
        }
    }


    /**
     * 检测开启子进程数
     * @throws MultiProcessWorkerException
     */
    private function checkWorkNum()
    {
        if ($this->workerNum < 1) {
            throw new MultiProcessWorkerException("最少开启1个子进程");
        }
        if ($this->workerNum > $this->maxWorkerNum) {
            throw new MultiProcessWorkerException("最多可开启进程个数" . $this->maxWorkerNum);
        }
    }


    //启动
    public function start()
    {
        try {
            //检测进程个数
            $this->checkWorkNum();
            //检测模式，php扩展是否符合要求
            $this->checkMode();
            //默认Pcntl模式
            $processWorker = new PcntlProcessWorker();
            if ($this->mode == self::modeSwooleProcess) {
                $processWorker = new SwooleProcessWorker();
            }
            // 启动进程，等待进程退出
            $this->RunProcess($processWorker);
        } catch (MultiProcessWorkerException $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     *  启动进程，等待进程退出
     * @param MultiProcessWorkerInterface $processWorker 处理进程类
     */
    public function RunProcess(MultiProcessWorkerInterface $processWorker)
    {
        $processWorker->productProcesses($this->workerNum, function ($workPage) {
            $this->setWorkCallBack($workPage);
        });
        $processWorker->waitProcesses($this->workerNum);
    }


    /**
     *  设置回调函数，用于外部自定义处理任务
     * @param $workPage 工作进程逻辑空间编号
     */
    public function setWorkCallBack($workPage)
    {
        $pid = \getmypid();
        \call_user_func($this->onWork, $workPage, $pid);
    }


    /**
     * 快速获得 每个进程要工作的内容信息（offset,limit 模式 ） 适合小表，数组
     * @param $workPage      工作进程，逻辑空间编号
     * @param $workNum       工作进程数
     * @param $totalTaskNum  总任务数
     * @return array
     */
    public static function getWorkContentByOffsetMode(int $workPage, int $workNum, int $totalTaskNum)
    {
        $workPageTaskNum = ceil($totalTaskNum / $workNum);
        $offset = ($workPage - 1) * $workPageTaskNum;
        $isLastWorkPage = false;
        if ($workPage == $workNum) {
            $isLastWorkPage = true;
            $workPageTaskNum = $totalTaskNum - ($workPage - 1) * $workPageTaskNum;
        }
        return [$offset, $workPageTaskNum, $isLastWorkPage];
    }


    /**
     *快速获得 每个进程要工作的内容信息（id模式 ） 适合大表，数据够均匀
     * @param $workPage  工作进程，逻辑空间编号
     * @param $startId   开始任务id
     * @param $endId     结束任务id
     */
    public static function getWorkContentByIdMode(int $workPage, int $workNum, int $startId, int $endId)
    {
        $total = $endId - $startId;
        if ($total <= 0) {
            return;
        }
        $workPageTaskNum = ceil($total / $workNum);
        $taskStartId = ($workPage - 1) * $workPageTaskNum + $startId + $workPage - 1;
        $taskEndId = $taskStartId + $workPageTaskNum;
        $isLastWorkPage = false;
        //最后一个工作进程 ，可能要考虑数据跑脚本时候新增问题
        if ($workPage == $workNum) {
            $isLastWorkPage = true;
            $taskEndId = $endId;
        }
        return [$taskStartId, $taskEndId, $isLastWorkPage];
    }
}

