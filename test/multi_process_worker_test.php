<?php
/**
 * Desc:
 * User: maozhongyu
 * Date: 2021/6/7
 * Time: 上午1:05
 */

require_once __DIR__ . "/../vendor/autoload.php";
use Xpx\MultiProcessWorker\MultiProcessWorker;
$workNum = 4;
$totalTaskNum = 101;
$worker = new MultiProcessWorker(4,101,MultiProcessWorker::modePcntl);
$worker->onWork = function ($startTaskId, $endTaskId, $isLastWorkPage, $workPage, $pid) {
    //每个工作空间，如任务数较多，建议分页处理
    echo "工作空间编号{$workPage},pid:{$pid}, 负责任务编号{$startTaskId}-{$endTaskId}";
    if ($isLastWorkPage) {
        echo " 最后一个工作空间，需要跑脚本新增情况考虑，如select * from xxx where id > {$startTaskId}";
    }
    echo "begin work";
    echo PHP_EOL;
};
echo "pcntl模式------------start".PHP_EOL;
$worker->start();

echo "pcntl模式------------end".PHP_EOL;

sleep(2);

$worker = new MultiProcessWorker(4,101,MultiProcessWorker::modeSignleSwooleProcess);
$worker->onWork = function ($startTaskId, $endTaskId, $isLastWorkPage, $workPage, $pid) {
    //每个工作空间，如任务数较多，建议分页处理
    echo "工作空间编号{$workPage},pid:{$pid}, 负责任务编号{$startTaskId}-{$endTaskId}";
    if ($isLastWorkPage) {
        echo " 最后一个工作空间，需要跑脚本新增情况考虑，如select * from xxx where id > {$startTaskId}";
    }
    echo " begin work";
    echo PHP_EOL;
};
echo "swooleProcess模式---------start".PHP_EOL;
$worker->start();
echo "swooleProcess模式---------end".PHP_EOL;

