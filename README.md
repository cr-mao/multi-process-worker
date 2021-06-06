<h1 align="center">  muti process worker </h1>

<p align="center"> use muti process worker do tasks  easily.</p>

## Requires

 php > 7.1
 
 swoole enable ||  pcntl enabled
 

## Installing

```shell
$ composer require cr-mao/multi-process-worker
```

## Usage
```php
use Xpx\MultiProcessWorker\MultiProcessWorker;
$workNum = 4;
$totalTaskNum = 101;
// pcntl模式，
$worker = new MultiProcessWorker(4,101,MultiProcessWorker::modePcntl);
// swooleProcess模式
// $worker = new MultiProcessWorker(4,101,MultiProcessWorker::modeSignleSwooleProcess);
$worker->onWork = function ($startTaskId, $endTaskId, $isLastWorkPage, $workPage, $pid) {
    //每个工作空间，如任务数较多，建议分页处理
    echo "工作空间编号{$workPage},pid:{$pid}, 负责任务编号{$startTaskId}-{$endTaskId}";
    if ($isLastWorkPage) {
        echo " 最后一个工作空间，需要跑脚本新增情况考虑，如select * from xxx where id > {$startTaskId}";
    }
    echo "begin work";
    echo PHP_EOL;
};
$worker->start();
```


## Contributing
ithub.com:cr-mao/multi_process_worker
You can contribute in one of three ways:
1. File bug reports using the [issue tracker](https://github.com/cr-mao/multi-process-worker/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/cr-mao/multi-process-worker/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT

## Links

 - https://www.php.net/manual/zh/book.pcntl.php

 - https://wiki.swoole.com/#/process/process?id=process
 
