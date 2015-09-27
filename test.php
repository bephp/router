<?php 
require ('router.php');
$r = new Router();
$r->get('/test/:id', function($router, $id=2, $aaa=1){var_dump($id, $aaa);})
  ->get('/test/:id/aaa', function($id=3, $aaa=111, $bbb=888){var_dump($id, $aaa, $bbb, $_GET, $_POST, $_REQUEST);})
  ->post('/test/id/:a', function($id, $p=111, $a=1){var_dump($id, $p, $a, $_GET, $_POST, $_REQUEST);})
  ->error(405, function($message){echo $message. "\r\n";});

$r->execute(array('aaa'=>'a%20aaa'), 'GET', '/test/123');
$r->execute(array('aaa'=>'a%20aaa'), 'GET', '/test11/123/aaaaa');


