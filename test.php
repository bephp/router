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
->hook('after', function($result){
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


