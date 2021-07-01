<h1 align="center">  muti process worker </h1>

<p align="center"> use muti process worker do tasks  quickily and easily.</p>

## Introduction

provide child process work space ,you can do task in child work space in onWork function .
also it provide functions to get work content quickily and easily ,you can see `MultiProcessWorker::getWorkContentByIdMode` and  `MultiProcessWorker::getWorkContentByOffsetMode` 



## Requires

 - php > 7.1.3
 
 - ext-pcntl enabled
 
 - ext-swoole optional
  

## Installing

```shell
$ composer require cr-mao/multi-process-worker
```

## Usage
```php

use Crmao\MultiProcessWorker\MultiProcessWorker;

$workNum = 4;
$worker = new MultiProcessWorker($workNum, MultiProcessWorker::modePcntl);
//$worker = new MultiProcessWorker($workNum, MultiProcessWorker::modeSwooleProcess);
$worker->onWork = function ($workPage, $pid) use ($workNum) {
    echo PHP_EOL;
    echo "工作空间编号:{$workPage},进程id:{$pid}" . PHP_EOL;

    //提供便捷函数，快速获得任务内容 ，模式一：id模式
    list($beginId, $endId, $isLastWorkPage) = MultiProcessWorker::getWorkContentByIdMode($workPage, 4, 1, 100001);
    //  select * from xxx where id >={$beginId} AND id <= {$endId},每个进程内可以自行在分页处理,  最后一个工作空间,你也许要考虑数据新增情况
    echo "begin mysql id is {$beginId}, end mysql id is {$endId}, 是否是最后一个工作空间:{$isLastWorkPage}";
    echo PHP_EOL;
    
    //提供便捷函数，快速获得内容 ，模式二: offset,limit 模式
    list($offset, $limit, $isLastWorkPage) = MultiProcessWorker::getWorkContentByOffsetMode($workPage, $workNum, 10001);
    echo "offset is {$offset}, limit is {$limit}, 是否是最后一个工作空间:{$isLastWorkPage} ";
    echo PHP_EOL;
    sleep(3);
};
$worker->start();

```


## Contributing
github.com:cr-mao/multi-process-worker
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
 
 - https://github.com/yidas/php-worker-dispatcher
 
