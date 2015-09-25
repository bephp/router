<?php 
require ('router.php');
$r = new Router();
$r->get('/test/:id/aaa', function($id=2, $p=1, $a=1){var_dump($id, $p, $a);});
var_dump($r);
var_dump($r->execute('GET', '/test/12/aaa', array('id'=>21, 'aaa'=>'bbb')));

