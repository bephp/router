<?php 
require ('router.php');

(new Router())
->error(401, function($message){
    header('Location: /login', true, 302);
    die($message);
})
->error(405, function($message){
    header('Location: /hello/world', true, 302);
})
->error(406, function($message){
    die($message);
})
->hook('auth', function($params){
    if ('lloyd' == $params['name'])
        return $params;
    $params['router']->error(401, 'Forbiden');
})
->hook('after', function($result, $router){
    //var_dump($result);
})
->hook('before', function($params){
    //$params['name'] = 'lloydzhou';
    return $params;
})
->get('/', function(){
    echo "Hello world !!!";
})
->get('/hello/:name', function($name){
    echo "Hello $name !!!";
})
->get('/hello/:name/again', function($name){
    echo "Hello $name again !!!";
}, 'auth')
->execute();

/**
 * curl -vvv 127.0.0.1:8888/hello/
 * will trigger 405 error handler, should redirect to URL: "/hello/world"
 *
 * curl -vvv 127.0.0.1:8888/hello/lloyd 
 * will get 200 status code, and get body "Hello lloyd !!!"
 *
 * curl -vvv 127.0.0.1:8888/hello/lloyd/again 
 * will get 200 status code, and get body "Hello lloyd again !!!"
 *
 * curl -vvv 127.0.0.1:8888/hello/world/again 
 * will trigger 406 error handler, should redirect to URL: "/login"
 *
 */

