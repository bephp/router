<?php 

class RouterTest extends \PHPUnit_Framework_TestCase{
    public function router(){
        return new \Router();
    }
    public function testRootDispatched(){
        $r = $this->router(); 
        $r->get('/', function(){ return 'bar'; });
        $response = $r->execute(array(), 'GET', '/');
        $this->assertEquals('bar',$response);
    }
    public function testStringDispatched(){
        $r = $this->router(); 
        $r->get('/foo', function(){ return 'bar'; });
        $response = $r->execute(array(), 'GET', '/foo');
        $this->assertEquals('bar',$response);
    }
    public function testParamDispatched(){
        $r = $this->router(); 
        $r->get('/hello/:name', function($name){ return $name; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd');
        $this->assertEquals('lloyd',$response);
    }
    public function testParamsDispatched(){
        $r = $this->router(); 
        $r->get('/hello/:name1/:name2', function($name1, $name2){ return $name1. $name2; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd/zhou');
        $this->assertEquals('lloydzhou',$response);
    }
    public function testParamsExtDispatched(){
        $r = $this->router(); 
        $r->get('/hello/:name.:ext', function($name, $ext){ return $name. '.'. $ext; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd.json');
        $this->assertEquals('lloyd.json',$response);
    }
    public function testParamExecuteDefaultDispatched(){
        $r = $this->router(); 
        $r->get('/hello/:name', function($name, $ext='json'){ return $name. '.'. $ext; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd');
        $this->assertEquals('lloyd.json',$response);
    }
    public function testParamExecuteDispatched(){
        $r = $this->router(); 
        $r->get('/hello/:name', function($name, $ext){ return $name. '.'. $ext; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd');
        $this->assertEquals('lloyd.',$response);
    }
    public function testParamExecute1Dispatched(){
        $r = $this->router(); 
        $r->get('/hello/:name', function($name, $ext){ return $name. '.'. $ext; });
        $response = $r->execute(array('ext'=>'js'), 'GET', '/hello/lloyd');
        $this->assertEquals('lloyd.js',$response);
    }

    // test hooks
    public function testBeforeHook(){
        $r = $this->router(); 
        $r->hook('before', function($params){
            $params['ext'] = 'json';
            return $params;
        });
        $r->get('/hello/:name', function($name, $ext){ return $name. '.'. $ext; });
        $response = $r->execute(array('ext'=>'js'), 'GET', '/hello/lloyd');
        $this->assertEquals('lloyd.json',$response);
    }
    public function testCustomerHook(){
        $r = $this->router(); 
        $r->hook('auth', function($params){
            return false;
        });
        $r->get('/hello/:name', function($name, $ext){ return $name; }, array('auth'));
        $response = $r->execute(array(), 'GET', '/hello/lloyd');
        $this->assertEquals('Failed to execute hook: auth',$response);
    }
    public function testAfterHook(){
        $r = $this->router(); 
        $r->hook('after', function($response, $router){
            return 'foo.bar';
        });
        $r->get('/hello/:name', function($name, $ext){ return $name. '.'. $ext; });
        $response = $r->execute(array('ext'=>'js'), 'GET', '/hello/lloyd');
        $this->assertEquals('foo.bar',$response);
    }

    // test error handler
    public function testError(){
        $r = $this->router(); 
        $r->error(405, function($message){
            return '405';
        });
        $r->get('/hello/:name', function($name, $ext){ return $name. '.'. $ext; });
        $response = $r->execute(array(), 'POST', '/hello/lloyd');
        $this->assertEquals('405',$response);
    }
    public function testError1(){
        $r = $this->router(); 
        $r->error(405, function($message){
            return '405';
        });
        $r->get('/hello/:name', function($name, $ext){ return $name. '.'. $ext; });
        $response = $r->execute(array(), 'GET', '/foo');
        $this->assertEquals('405',$response);
    }
    public function testError2(){
        $r = $this->router(); 
        $r->error('some_error', function(){
            return 'some error';
        });
        $r->get('/hello/:name', function($name, $router){ return $router->error('some_error'); });
        $response = $r->execute(array(), 'GET', '/hello/lloyd');
        $this->assertEquals('some error', $response);
    }
}
