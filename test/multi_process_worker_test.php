<?php
/**
 * Desc:
 * User: maozhongyu
 * Date: 2021/6/7
 * Time: 上午1:05
 */

require_once __DIR__ . "/../vendor/autoload.php";

use Crmao\MultiProcessWorker\MultiProcessWorker;

$workNum = 4;
//$worker = new MultiProcessWorker($workNum, MultiProcessWorker::modePcntl);
$worker = new MultiProcessWorker($workNum, MultiProcessWorker::modeSwooleProcess);
$worker->onWork = function ($workPage, $pid) use ($workNum) {
    echo PHP_EOL;
    echo "工作空间编号:{$workPage},进程id:{$pid}" . PHP_EOL;

    //提供便捷函数，快速获得任务内容，id模式
    list($beginId, $endId, $isLastWorkPage) = MultiProcessWorker::getWorkContentByIdMode($workPage, $workNum, 1, 100001);
    //  select * from xxx where id >={$beginId} AND id <= {$endId},每个进程内可以自行在分页处理,  最后一个工作空间,你也许要考虑数据新增情况
    echo "begin mysql id is {$beginId}, end mysql id is {$endId}, 是否是最后一个工作空间:{$isLastWorkPage}";
    echo PHP_EOL;

    //提供便捷函数，快速获得内容  offset,limit 模式
    list($offset, $limit, $isLastWorkPage) = MultiProcessWorker::getWorkContentByOffsetMode($workPage, $workNum, 10001);
    echo "offset is {$offset}, limit is {$limit}, 是否是最后一个工作空间:{$isLastWorkPage} ";
    // select * from xxx where xxxxx  offset {$offset},$limit
    echo PHP_EOL;
    sleep(3);
};
$worker->start();


