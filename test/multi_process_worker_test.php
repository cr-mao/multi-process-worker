<?php
/**
 * Desc: MultiProcessWorker 简单脚本使用测试 2种模式 pcntl || swoole Process
 * User: maozhongyu
 * Date: 2021/6/6
 * Time: 下午12:44
 */

/**
 * 多进程任务处理器
 * Class MultiProcessWorker
 */
class MultiProcessWorker
{
    public $workerNum = 1; //子进程个数,默认一个
    public $onWork = NULL; //工作空间的回调函数，用于自定义处理任务
    public $totalTaskNum = 1; //总任务数， 在跑脚本的时候，要考虑新增数据情况
    public $minTaskNum = 1;  //最小任务数
    public $perWorkPageTaskNum;// 每个进程要处理的任务个数
    public $mode = 1; //工作模式  （1=>pcntl，2=>swoole Process (单进程) ），默认使用 pcntl
    const  modePcntl = 1;  // pcntl模式
    const  modeSignleSwooleProcess = 2;// swoole Process模式,  https://wiki.swoole.com/#/process/process?id=process

    /**
     * WorkerProcess constructor.
     * @param int $workerNum 工作进程个数
     * @param int $totalTaskNum
     *              总任务数,可能是来源mysql 的，也可能是写死的数组等，
     *              帮你计算好了每个进程要干的任务编号范围,任务数
     *              其实你可以不用它，只关心有几个逻辑进程空间，自行处理每个进程要干的活
     * @param int $minTaskNum 最小任务个数 ，任务数很小，其实就没必要用多进程处理了
     * @param string $mode 工作模式  （1=>pcntl，2=>swoole Process (单进程) ），默认使用 pcntl
     */
    public function __construct(int $workerNum = 1, int $totalTaskNum = 1, int $mode = 1, int $minTaskNum = 1)
    {
        $this->workerNum = $workerNum;
        $this->totalTaskNum = $totalTaskNum;
        $this->minTaskNum = $minTaskNum;
        $this->mode = $mode;
    }

    /**
     * 检测工作模式 ，扩展是否符合要求
     */
    private function checkMode()
    {
        if (!in_array($this->mode, [self::modePcntl, self::modeSignleSwooleProcess])) {
            exit("模式不正确");
        }
        switch (true) {
            case $this->mode == self::modePcntl:
                if (!\extension_loaded("pcntl")) {
                    exit("pcntl 扩展没有安装");
                }
                break;
            case $this->mode == self::modeSignleSwooleProcess:
                if (!\extension_loaded("swoole")) {
                    exit("swoole 扩展没有安装");
                }
                break;
            default :
                return;
        }
    }

    /**
     * 检测任务数，进程数设置是否合法
     */
    private function checkTaskNum()
    {
        if ($this->totalTaskNum < $this->workerNum) {
            exit("任务数不能小于进程个数");
        }
        if ($this->totalTaskNum < $this->minTaskNum) {
            exit("任务数小于最小任务个数设置");
        }
    }

    /**
     * 计算每个进程应该干的任务数量
     */
    private function perWorkPageShouldDoTaskNum()
    {
        $this->perWorkPageTaskNum = (int)($this->totalTaskNum / $this->workerNum);
    }

    //启动
    public function start()
    {
        //检测模式，php扩展是否符合要求
        $this->checkMode();
        //检测任务数
        $this->checkTaskNum();
        //计算每个进程应该干的任务数量
        $this->perWorkPageShouldDoTaskNum();
        //fork 子进程个数,并设置任务回调函数，处理任务数量计算
        switch (true) {
            case $this->mode == self::modePcntl:
                $this->runByPcntlForkProcess();
                break;
            case $this->mode == self::modeSignleSwooleProcess:
                $this->runBySwooleProcess();
                break;
            default:
                return;
        }
    }


    /**
     * swooleProcess 模式,启动多进程任务
     */
    public function runBySwooleProcess()
    {
//        echo 'Parent #' . getmypid() . ' exit' . PHP_EOL;
        // $workPage 工作空间, 1开始递增，可以理解为子进程的逻辑号
        for ($workPage = 1; $workPage <= $this->workerNum; $workPage++) {
            $process = new Swoole\Process(function () use ($workPage) {
//                echo 'Child #' . getmypid() . " start" . PHP_EOL;
                $this->setWorkCallBack($workPage);
            });
            $process->start();
        }
        for ($i = $this->workerNum; $i--;) {
            Swoole\Process::wait(true);
            // $status = Swoole\Process::wait(true);
            // echo "Recycled #{$status['pid']}, code={$status['code']}, signal={$status['signal']}" . PHP_EOL;
        }
    }

    /**
     * pcnt模式启动多进程任务
     */
    public function runByPcntlForkProcess()
    {
        // $workPage 工作空间, 1开始递增，可以理解为子进程的逻辑号
        for ($workPage = 1; $workPage <= $this->workerNum; $workPage++) {
            $pid = \pcntl_fork(); //创建成功会返回子进程id
            if ($pid < 0) {
                exit('子进程创建失败' . $pid);
            } else if ($pid > 0) {
                //父进程空间，返回子进程id,不做事情
                //echo "子进程{$pid} start".PHP_EOL;
            } else { //返回为0子进程空间
                // echo "i am child space,my id:{$workPage}".PHP_EOL;
                $this->setWorkCallBack($workPage);
                exit;
            }
        }
        //放在父进程空间，结束的子进程信息，阻塞状态,父进程等待子进程退出，避免僵尸进程
        $status = 0;
        for ($i = 1; $i <= $this->workerNum; $i++) {
            //$pid = \pcntl_wait($status);
            \pcntl_wait($status);
        }
    }


    /**
     * 根据工作空间编号获得相应任务
     * @param $workPage
     * @return array [$startTaskId 开始任务编号,$endTaskId 结束任务编号,$isLastWorkPage 是否是最后一个工作空间]
     */
    private function getWorkContent($workPage)
    {
        $perWorkPageTaskNum = $this->perWorkPageTaskNum;
        // 本工作空间，要处理的任务开始编号
        $startTaskId = ($workPage - 1) * $perWorkPageTaskNum + 1;
        // 本工作空间，要处理的任务结束编号
        $endTaskId = $workPage * $perWorkPageTaskNum;
        //最后一个工作空间，要考虑 数据新增情况，如mysql 任务，在脚本执行期间，新增数据
        $isLastWorkPage = false;
        if ($workPage == $this->workerNum) {
            $isLastWorkPage = true;
            $endTaskId = $this->totalTaskNum;
        }
        return [
            $startTaskId, $endTaskId, $isLastWorkPage
        ];
    }

    /**
     *  设置回调函数，用于外部自定义处理任务
     * @param $workPage 工作进程逻辑空间编号
     */
    private function setWorkCallBack($workPage)
    {
        list($startTaskId, $endTaskId, $isLastWorkPage) = $this->getWorkContent($workPage);
        $pid = \getmypid();
        // onWork 回调函数， $startTaskId 开始任务编号，$endTaskId 结束任务编号,$isLastWorkPage是否最后一个工作空间,
        \call_user_func($this->onWork, $startTaskId, $endTaskId, $isLastWorkPage, $workPage, $pid);
    }
}


/**
 *  pcntl模式测试
 * @param $workerNum   工作进程数量
 * @param $totalTaskNum 总任务数量
 */
function MultiProcessByPcntlTest($workerNum, $totalTaskNum)
{
    $work = new MultiProcessWorker($workerNum, $totalTaskNum, MultiProcessWorker::modePcntl);
    $work->onWork = function ($startTaskId, $endTaskId, $isLastWorkPage, $workPage, $pid) {
        //每个工作空间，如任务数较多，建议分页处理
        echo "工作空间编号{$workPage},pid:{$pid}, 负责任务编号{$startTaskId}-{$endTaskId}";
        if ($isLastWorkPage) {
            echo " 最后一个工作空间，需要跑脚本新增情况考虑，如select * from xxx where id > {$startTaskId}";
        }
        echo " 开始工作";
        echo PHP_EOL;
    };
    echo "MultiProcessByPcntl test start" . PHP_EOL;
    $work->start();
}

/**
 * SwooleProcess 模式测试
 * @param $workerNum   工作进程数量
 * @param $totalTaskNum 总任务数量
 */
function MultiProcessBySwooleProcessTest($workerNum, $totalTaskNum)
{
    $work = new MultiProcessWorker($workerNum, $totalTaskNum, MultiProcessWorker::modeSignleSwooleProcess);
    $work->onWork = function ($startTaskId, $endTaskId, $isLastWorkPage, $workPage, $pid) {
        //每个工作空间，如任务数较多，建议分页处理
        echo "工作空间编号{$workPage},pid:{$pid}, 负责任务编号{$startTaskId}-{$endTaskId}";
        if ($isLastWorkPage) {
            echo " 最后一个工作空间，需要跑脚本新增情况考虑，如select * from xxx where id > {$startTaskId}";
        }
        echo " 开始工作";
        echo PHP_EOL;
    };
    echo "MultiProcessBySwooleProcess test start" . PHP_EOL;
    $work->start();
}

$workerNum = 4;
$totalTaskNum = 101;

MultiProcessByPcntlTest($workerNum, $totalTaskNum);
echo "pcnt work test finish".PHP_EOL.PHP_EOL;
sleep(2);
MultiProcessBySwooleProcessTest($workerNum,$totalTaskNum);






