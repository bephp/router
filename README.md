# router
A barebones router for PHP. It matches urls and executes PHP functions. automatic get variable based on handler function parameter list.

## Installation

    composer require lloydzhou/router

## API Reference

### match($method, $path, $callback, $hook)

create the router tree based on given $method and $path, the $callback and $hook will stored in the leaf node.

### get($path, $callback, $hook)

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

## Performance

using tree struct to stored callback handler on leaf node. Ensure that the time complexity of find callback function is O(log n).

## Example

belong is one simple example, see the full examples in [test.php](https://github.com/lloydzhou/router/blob/master/test.php).

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


