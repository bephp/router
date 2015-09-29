<?php 
require ('router.php');

(new Router())
->error(405, function($message){
    header('Location: /hello/world', true, 302);
})
->get('/', function(){
    echo "Hello world !!!";
})
->get('/hello/:name', function($name){
    echo "Hello $name !!!";
})
->get('/hello/:name/again', function($name){
    echo "Hello $name again !!!";
})
->execute();


