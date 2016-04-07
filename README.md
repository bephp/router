# router 
[![Build Status](https://travis-ci.org/bephp/router.svg?branch=master)](https://travis-ci.org/bephp/router)
[![Coverage Status](https://coveralls.io/repos/bephp/router/badge.svg?branch=master&service=github)](https://coveralls.io/github/bephp/router?branch=master)
[![Latest Stable Version](https://poser.pugx.org/bephp/router/v/stable)](https://packagist.org/packages/bephp/router)
[![Total Downloads](https://poser.pugx.org/bephp/router/downloads)](https://packagist.org/packages/bephp/router)
[![Latest Unstable Version](https://poser.pugx.org/bephp/router/v/unstable)](https://packagist.org/packages/bephp/router)
[![License](https://poser.pugx.org/bephp/router/license)](https://packagist.org/packages/bephp/router)  
*A barebones router for PHP.*  
*It matches urls and executes PHP functions.*  
*Automatic get variable based on handler function parameter list.*  
*Suport to compile router callback handlers into plain array source code.*  

> [中文版](https://github.com/bephp/router/blob/master/README.zh-CN.md).

## Installation

    composer require bephp/router

## API Reference

### group/prefix($prefix, $hook)

add group routers with same prefix. if not pass param $prefix just reset attribute prefix of router instance to empty string.
will merge the $hook to this group.

### match($method, $path, $callback, $hook)

create the router tree based on given $method and $path, the $callback and $hook will stored in the leaf node.

### get/post/put/delete/head/options($path, $callback, $hook)

wraper the match method without $method parameter. also defined "post", "put", "delete", "head" and so on.

### execute()

the enter point of the application.
have 3 optional parameters, $params will be merged with request variable and passed into callback handler.
can pass $method and $path when not deploy as web server, can using the 2 parameters to test this library.

### error()

1. if call this API with $error_code and $callback, just define the callback handler for the error code.
2. if call this API with $error_code and other parameters, will trigger the error callback handler, the parameters will passed to the error handler.

### hook()

1. if call this API with $hook_name and $callback, just define the callback handler for the hook name.
2. if call this API with $hook_name and other parameters, will trigger the hook handler, the parameters will passed to the hook handler.
3. there's 2 spec hook: "before" and "after", this library will auto call "before" hook before execute the handler, and call "after" hook after execute the handler.
4. the "after" hook will auto trigger with the return value of callback handler.
5. the "before" hook, and other user define hooks will auto trigger with the merged $params. these hooks need return the $params, so can change the value of $params (like format it). if these hook return false, will trigger 406 error handler.


## Validate

using [ctype functions](http://php.net/manual/zh/function.ctype-punct.php) to validate params in pathinfo  
**example:**

if defined router: "/hello/:name:a.json", and using URL: "/hello/lloyd.json" to resolve url.  
will call function "ctype_alpha" to validate "lloyd".  
validate command to map the ctype functions:  

    A => ctype_alnum
    a => ctype_alpha
    d => ctype_digit
    x => ctype_xdigit
    l => ctype_lower
    u => ctype_upper

## Compile

the PHP request always match the callback handlers every time. but the request just match one callback.
so we can compile the routed tree node into plain array, to save time.

### DEV model
using CRouter instead of Router, will always compile the source code into target file.

    $crouter = new CRouter("router.inc.php", true);

### PRODUCTION model
just include the target source code, and execute it with parameters.

    $router = include("router.inc.php");
    $router->execute();

## Performance

1. using tree struct to stored callback handler on leaf node. Ensure that the time complexity of find callback function is O(log n). ![Tree Node](https://raw.githubusercontent.com/bephp/router/master/node.jpeg)
2. using CRouter class, suport to compile router callback handlers into plain array source code. so can save time to create tree node to store callback by split pathinfo.

### [Benchmark](https://github.com/bephp/php-router-benchmark)

using "php-router-benchmark" to test router performance.

#### Worst-case matching
This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization. 1,000 routes each with 9 arguments.

This benchmark consists of 10 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


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


## Example

belong is one simple example, see the full examples in [example.php](https://github.com/bephp/router/blob/master/example.php).

    (new Router())
    ->error(405, function($message){
        header('Location: /hello/world', true, 302);
    })
    ->get('/hello/:name', function($name){
        echo "Hello $name !!!";
    })

### Start server

    php -S 0.0.0.0:8888 example.php

### Test

Url not match, trigger 405 error handler.

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

Url match get current result.
 
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


## Demo

there's one [blog demo](https://github.com/bephp/blog), work with [ActiveRecord](https://github.com/bephp/activerecord) and [MicroTpl](https://github.com/bephp/microtpl).


