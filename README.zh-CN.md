# Router 路由控制器
[![Build Status](https://travis-ci.org/bephp/router.svg?branch=master)](https://travis-ci.org/bephp/router)
[![Coverage Status](https://coveralls.io/repos/bephp/router/badge.svg?branch=master&service=github)](https://coveralls.io/github/bephp/router?branch=master)
[![Latest Stable Version](https://poser.pugx.org/bephp/router/v/stable)](https://packagist.org/packages/bephp/router)
[![Total Downloads](https://poser.pugx.org/bephp/router/downloads)](https://packagist.org/packages/bephp/router)
[![Latest Unstable Version](https://poser.pugx.org/bephp/router/v/unstable)](https://packagist.org/packages/bephp/router)
[![License](https://poser.pugx.org/bephp/router/license)](https://packagist.org/packages/bephp/router)  
*一个及其精简的PHP路由控制器。*  
*匹配URL找到对应的回调函数，并执行*
*依据回调函数的参数列表自动从请求里面获取变量*
*支持“编译”，将映射路由阶段的函数调用直接编译成PHP数组进行初始化，节省时间*

## 安装

    composer require bephp/router

## API 说明

### group/prefix($prefix, $hook)

创建一组拥有相同URL前缀的路由。不传递参数，或者传递参数错误的时候，会将prefix设置成空字符串。
可以在定义group的时候，同时给这一组url定义相同的hook，会在调用match的时候合并到每一个url对应的hook里面，默认是空数组。

### match($method, $path, $callback, $hook)

依据传递的HTTP请求方法以及url路径生成路由映射树形结构体，在叶子节点保存回调函数和需要处理的钩子函数

### get/post/put/delete/head/options($path, $callback, $hook)

get函数是对match函数的封装，直接使用'GET'作为第一个参数调用match方法。
同样的，也对post，put，delete，head，options等请求进行了封装。

### execute()

web程序入口，支持传递3个参数，不过参数是可选的。  
第一个参数$params会和请求体的变量进行合并，并且依次在各个钩子函数中进行传递。  
第二个和第三个参数是请求方法$method和请求地址$pathinfo，这两个参数是为了调试使用的，默认情况会自动获取。

### error()

错误处理函数，有两种用法：  

1. 如果传递$error_code和$callback，那么会对相应的code设定回调处理函数  
2. 如果传递$error_code和其他的参数，会触发code对应的回调函数，并把后面的参数传递进去  

### hook()

钩子函数，也有两种用法：  

1. 如果传递$hook_name和$callback，那么会对相应的hook_name设定回调处理函数  
2. 如果传递$hook_name和其他的参数，会触发hook_name对应的回调函数，并把后面的参数传递进去  
3. 有两个比较特殊的钩子函数“before”，“after”，在控制器里面会自动的调用before和after两个钩子函数，分别在处理回调的前面和后面。  
4. before这个钩子函数以及用户在match这个API里面针对这个API自定义的钩子函数会一次按照顺序执行。并且都会接受当前的$router对象作为参数，如果在某一个钩子函数返回false会触发406错误。用户可以在这些钩子函数里面对$router->params进行更改。  
5. after这个钩子函数会自动在最后执行，并且会将主逻辑回调函数的返回值作为第一个参数，第二个参数是$router自身。  

## 参数验证

使用[ctype_前缀的系列函数](http://php.net/manual/zh/function.ctype-punct.php)验证pathinfo传递过来的参数

**例如:**

如果定义了路由: "/hello/:name:a.json", 使用RUL: "/hello/lloyd.json"查找路由的时候，会调用"ctype_alpha"来验证"lloyd".  

验证指令和ctype函数的映射表

    A => ctype_alnum
    a => ctype_alpha
    d => ctype_digit
    x => ctype_xdigit
    l => ctype_lower
    u => ctype_upper


## 编译

由于PHP程序执行的特殊性，每一次都会从头执行整个代码。所以当用户定义了一大堆的url映射之后，会调用很多次match函数生成路由映射表。  
但是，每一次请求只会映射到其中的一个回调函数里面。    
所以为了在生产环境中节省时间，就设计了编译这个功能。可以直接将映射好的路由表以及错误处理和钩子函数全部保存成PHP数组直接初始化成Router对象。省去了每次都需要创建这个树形结构体的时间。  

### 开发环境

使用CRouter替换Router这个类

    $crouter = new CRouter("router.inc.php", true);

### 生产环境

直接包含编译好的类文件就好

    $router = include("router.inc.php");
    $router->execute();

## 性能

1. 使用树形结构存储回调函数。树形结构有个特点就是查找一个节点（回调函数）的时间复杂度为O(log n)。 ![Tree Node](https://raw.githubusercontent.com/bephp/router/master/node.jpeg)
2. 使用CRouter支持编译特性。大家都知道树形结构查找比较快，在构建这颗树的时候，相应的花的时间也比创建列表时间更多。使用编译，就能节省创建树形节点的时间，直接使用创建好的树形数组初始化，速度会比普通的路由控制器直接创建列表速度还要快！  

### [性能测试](https://github.com/bephp/php-router-benchmark)

使用"php-router-benchmark"来专门测试路由控制器的性能

#### 最坏的情况

这里构造的最坏的情况是查找一个不在路由表里面的url和最后一个url对应的路由。  
测试程序会随机的构造具有一个前缀和后缀的，中间url部分都是需要匹配出来的参数，测试的1000个url里面每一个url都有9个参数需要匹配。  
总共有10种情况进行测试，每一种情况都会重复运行1000次，最终取去掉3%异常值之后的统计平均值。  

Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Router - unknown route (1000 routes) | 993 | 0.0000232719 | +0.0000000000 | baseline
Router - last route (1000 routes) | 981 | 0.0000955424 | +0.0000722705 | 311% slower
FastRoute - unknown route (1000 routes) | 990 | 0.0005051955 | +0.0004819236 | 2071% slower
FastRoute - last route (1000 routes) | 998 | 0.0005567203 | +0.0005334484 | 2292% slower
Symfony2 Dumped - unknown route (1000 routes) | 998 | 0.0006116139 | +0.0005883420 | 2528% slower
Symfony2 Dumped - last route (1000 routes) | 998 | 0.0007765370 | +0.0007532651 | 3237% slower
Symfony2 - unknown route (1000 routes) | 996 | 0.0028456177 | +0.0028223458 | 12128% slower
Symfony2 - last route (1000 routes) | 993 | 0.0030129542 | +0.0029896823 | 12847% slower
Aura v2 - last route (1000 routes) | 989 | 0.1707107230 | +0.1706874511 | 733450% slower
Aura v2 - unknown route (1000 routes) | 988 | 0.1798588730 | +0.1798356011 | 772760% slower

> 这里需要说明一下的是，由于存储的数据结构不一样，这个控制器没有最坏情况这个说法查找第一个路由和最后一个路由时间复杂度是一样的。  
> 上表的数据也证实了O(log n)复杂度会比O(n)复杂度在时间上少好多，是数量级的差距。

## 例子

以下部分是一个简单的例子，详细的示例请查看[example.php](https://github.com/bephp/router/blob/master/example.php)文件.

    (new Router())
    ->error(405, function($message){
        header('Location: /hello/world', true, 302);
    })
    ->get('/hello/:name', function($name){
        echo "Hello $name !!!";
    })

### 启动测试服务

    php -S 0.0.0.0:8888 example.php

### 使用CURL进行测试

URL没有匹配上的情况，触发405错误

    curl -vvv 127.0.0.1:8888
    > GET / HTTP/1.1
    > User-Agent: curl/7.35.0
    > Host: 127.0.0.1:8888
    > Accept: */*
    > 
    < HTTP/1.1 302 Found
    < Host: 127.0.0.1:8888
    < Connection: close
    < X-Powered-By: PHP/5.5.9-1ubuntu4.12
    < Location: /hello/world
    < Content-type: text/html
    < 
    * Closing connection 0

URL匹配上了，返回正确结果
 
    curl -vvv 127.0.0.1:8888/hello/lloyd
    * Connected to 127.0.0.1 (127.0.0.1) port 8888 (#0)
    > GET /hello/lloyd HTTP/1.1
    > User-Agent: curl/7.35.0
    > Host: 127.0.0.1:8888
    > Accept: */*
    > 
    < HTTP/1.1 200 OK
    < Host: 127.0.0.1:8888
    < Connection: close
    < X-Powered-By: PHP/5.5.9-1ubuntu4.12
    < Content-type: text/html
    < 
    * Closing connection 0
    Hello lloyd !!!


## Demo程序

这里有一个相对比较完整的[博客demo](https://github.com/bephp/blog), 和一个数据访问[ActiveRecord](https://github.com/bephp/activerecord)以及模板[MicroTpl](https://github.com/bephp/microtpl)一起完成，很简洁。


