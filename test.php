<?php 
require ('router.php');
$r = new Router();
$r->get('/test/:id', function($id=2, $aaa=1){var_dump($id, $aaa);})
  ->get('/test/:id/aaa', function($id=3, $aaa=111, $bbb=888){var_dump($id, $aaa, $bbb, $_GET, $_POST, $_REQUEST);})
  ->post('/test/id/:a', function($id, $p=111, $a=1){var_dump($id, $p, $a, $_GET, $_POST, $_REQUEST);});

$r->execute(array());


