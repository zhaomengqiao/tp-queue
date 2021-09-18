
## 说明
本扩展是基于官方的扩展修复了部分bug , 的支持；如果喜欢请点 start ;必须是tp6版本
- 新增了rabbitmq
- 支持发送不同的队列；默认选择配置文件里的

#支持点右上角start
[我的blog](https://blog.suiyidian.cn)
## 安装

> composer require sonhineboy/tp-queue

## 配置

> 配置文件位于 `config/queue.php`

### 公共配置

```bash
[
    'default'=>'sync' //驱动类型，可选择 sync(默认):同步执行，database:数据库驱动,redis:Redis驱动 rabbitmq
]
```

### rabbitmq配置
```php
 'rabbiMQ' => [
            'type' => 'rabbitMq',
            'dsn' => env('RABBITMQ_DSN', null),
            'host' => env('RABBITMQ_HOST', '127.0.0.1'),
            'port' => env('RABBITMQ_PORT', 5672),
            'vhost' => env('RABBITMQ_VHOST', '/'),
            'login' => env('RABBITMQ_LOGIN', 'admin'),
            'password' => env('RABBITMQ_PASSWORD', 'admin'),
            'queue' => env('RABBITMQ_QUEUE', 'default'),
            'options' => [
                'exchange' => [
                    'name' => env('RABBITMQ_EXCHANGE_NAME',"default_exchange"),
                    /*
                    * Determine if exchange should be created if it does not exist.
                    */
                    'declare' => env('RABBITMQ_EXCHANGE_DECLARE', true),
                    /*
                    * Read more about possible values at https://www.rabbitmq.com/tutorials/amqp-concepts.html
                    */
                    'type' => env('RABBITMQ_EXCHANGE_TYPE', \Interop\Amqp\AmqpTopic::TYPE_DIRECT),
                    'passive' => env('RABBITMQ_EXCHANGE_PASSIVE', false),
                    'durable' => env('RABBITMQ_EXCHANGE_DURABLE', true),
                    'auto_delete' => env('RABBITMQ_EXCHANGE_AUTODELETE', false),
                    'arguments' => env('RABBITMQ_EXCHANGE_ARGUMENTS'),
                ],

                'queue' => [
                    /*
                    * Determine if queue should be created if it does not exist.
                    */
                    'declare' => env('RABBITMQ_QUEUE_DECLARE', true),
                    /*
                    * Determine if queue should be binded to the exchange created.
                    */
                    'bind' => env('RABBITMQ_QUEUE_DECLARE_BIND', true),
                    /*
                    * Read more about possible values at https://www.rabbitmq.com/tutorials/amqp-concepts.html
                    */
                    'passive' => env('RABBITMQ_QUEUE_PASSIVE', false),
                    'durable' => env('RABBITMQ_QUEUE_DURABLE', true),
                    'exclusive' => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
                    'auto_delete' => env('RABBITMQ_QUEUE_AUTODELETE', false),
                    'arguments' => env('RABBITMQ_QUEUE_ARGUMENTS'),
                ],
            ],

```
## 创建任务类
> 单模块项目推荐使用 `app\job` 作为任务类的命名空间
> 多模块项目可用使用 `app\module\job` 作为任务类的命名空间
> 也可以放在任意可以自动加载到的地方

任务类不需继承任何类，如果这个类只有一个任务，那么就只需要提供一个`fire`方法就可以了，如果有多个小任务，就写多个方法，下面发布任务的时候会有区别  
每个方法会传入两个参数 `think\queue\Job $job`（当前的任务对象） 和 `$data`（发布任务时自定义的数据）

还有个可选的任务失败执行的方法 `failed` 传入的参数为`$data`（发布任务时自定义的数据）

### 下面写两个例子

```php
namespace app\job;

use think\queue\Job;

class Job1{
    
    public function fire(Job $job, $data){
    
            //....这里执行具体的任务 
            
             if ($job->attempts() > 3) {
                  //通过这个方法可以检查这个任务已经重试了几次了
             }
            
            
            //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
            $job->delete();
            
            // 也可以重新发布这个任务
            $job->release($delay); //$delay为延迟时间
          
    }
    
    public function failed($data){
    
        // ...任务达到最大重试次数后，失败了
    }

}

```

```php

namespace app\lib\job;

use think\queue\Job;

class Job2{
    
    public function task1(Job $job, $data){
    
          
    }
    
    public function task2(Job $job, $data){
    
          
    }
    
    public function failed($data){
    
          
    }

}

```


## 发布任务
```injectablephp
#直接发布
think\facade\Queue::push($job, $data, $queue) //无延时
think\facade\Queue::later($delay, $job, $data, $queue)  //支持延时
#全局函数发布，支持不同的队列类型
queue($job, $data = '', $delay = 0, $queue,$connection) //$connection 要发送的队列服务； database/redis/rabbimq

```
> `think\facade\Queue::push($job, $data = '', $queue = null)` 无延时
> `think\facade\Queue::later($delay, $job, $data = '', $queue = null)`  支持延时
> 

`$job` 是任务名  
单模块的，且命名空间是`app\job`的，比如上面的例子一,写`Job1`类名即可  
多模块的，且命名空间是`app\module\job`的，写`model/Job1`即可  
其他的需要些完整的类名，比如上面的例子二，需要写完整的类名`app\lib\job\Job2`  
如果一个任务类里有多个小任务的话，如上面的例子二，需要用@+方法名`app\lib\job\Job2@task1`、`app\lib\job\Job2@task2`

`$data` 是你要传到任务里的参数

`$queue` 队列名，指定这个任务是在哪个队列上执行，同下面监控队列的时候指定的队列名,可不填

## 监听任务并执行

```bash
&> php think queue:listen

&> php think queue:work
```

两种，具体的可选参数可以输入命令加 `--help` 查看

> 可配合supervisor使用，保证进程常驻
