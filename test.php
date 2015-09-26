<?php 
require ('router.php');
$r = new Router();
$r->get('/test/:id/aaa', function($id=2, $aaa=1){var_dump($id, $aaa, $_GET, $_POST, $_REQUEST);})
  ->post('/test/id/:a', function($id, $p=111, $a=1){var_dump($id, $p, $a, $_GET, $_POST, $_REQUEST);});

$r->execute($_SERVER['REQUEST_METHOD'], $_SERVER['SCRIPT_NAME'], array());


