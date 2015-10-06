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
    if ($result) {
        header('Content-type: application/'. ($_GET['jsoncallback']?'javascript':'json'));
        if (isset($_GET['jsoncallback']))
            print $_GET['jsoncallback']. '('. json_encode($result). ')';
        else print json_encode($result);
    }
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
->get('/hello/:name.:ext', function($name, $ext){
    if ('js' == $ext || 'json' == $ext) return array('name'=>$name);
    return array('code'=>1, 'msg'=>'error message...');
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
 * curl -vvv 127.0.0.1:8888/hello/lloyd.json 
 * will get 200 status code, and get body: {"name": "lloyd"}
 *
 * curl -vvv 127.0.0.1:8888/hello/lloyd.js?jsoncallback=test
 * will get 200 status code, and get body: test({"name": "lloyd"})
 *
 * curl -vvv 127.0.0.1:8888/hello/lloyd.jsx?jsoncallback=test
 * will get 200 status code, and get body: test({"code":1,"msg":"error message..."})
 */

