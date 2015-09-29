# router
A barebones router for PHP. It matches urls and executes PHP functions. automatic get variable based on handler function parameter list.

## Installation

    composer require lloydzhou/router

## Example

    (new Router())
    ->error(405, function($message){
        header('Location: /hello/world', true, 302);
    })
    ->get('/hello/:name', function($name){
        echo "Hello $name !!!";
    })

### Start server

    php -S 0.0.0.0:8888 test.php

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

there's one [blog demo](https://github.com/lloydzhou/blog), work with [ActiveRecord](https://github.com/lloydzhou/activerecord) and [MicroTpl](https://github.com/lloydzhou/microtpl).


