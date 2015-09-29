<?php 
require ('router.php');

(new Router())
->error(405, function($message){
    header('Location: /hello/world', true, 302);
})
->get('/hello/:name', function($name){
    echo "Hello $name !!!";
})
->execute();


