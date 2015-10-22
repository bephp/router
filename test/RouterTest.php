<?php 

class RouterTest extends \PHPUnit_Framework_TestCase{
    public function router($compile=false){
        if ($compile)
            return new \CRouter('index.inc', true);
        return new \Router();
    }
    public function testRootDispatched(){
        $r = $this->router();
        $r->get('/', function(){ return 'bar'; });
        $response = $r->execute(array(), 'GET', '/');
        $this->assertEquals('bar',$response);

        $r = $this->router(true);
        $r->get('/', function(){ return 'bar'; });
        $response = $r->execute(array(), 'GET', '/');
        $this->assertEquals('bar',$response);
    }
    public function testStringDispatched(){
        $r = $this->router();
        $r->get('/foo', function(){ return 'bar'; });
        $response = $r->execute(array(), 'GET', '/foo');
        $this->assertEquals('bar',$response);

        $r = $this->router(true);
        $r->get('/foo', function(){ return 'bar'; });
        $response = $r->execute(array(), 'GET', '/foo');
        $this->assertEquals('bar',$response);
    }
    public function testParamDispatched(){
        $r = $this->router();
        $r->get('/hello/:name', function($name){ return $name; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd');
        $this->assertEquals('lloyd',$response);

        $r = $this->router(true); 
        $r->get('/hello/:name', function($name){ return $name; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd');
        $this->assertEquals('lloyd',$response);
    }
    public function testParamsDispatched(){
        $r = $this->router();
        $r->get('/hello/:name1/:name2', function($name1, $name2){ return $name1. $name2; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd/zhou');
        $this->assertEquals('lloydzhou',$response);

        $r = $this->router(true);
        $r->get('/hello/:name1/:name2', function($name1, $name2){ return $name1. $name2; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd/zhou');
        $this->assertEquals('lloydzhou',$response);
    }
    public function testParamsExtDispatched(){
        $r = $this->router();
        $r->get('/hello/:name.:ext', function($name, $ext){ return $name. '.'. $ext; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd.json');
        $this->assertEquals('lloyd.json',$response);

        $r = $this->router(true);
        $r->get('/hello/:name.:ext', function($name, $ext){ return $name. '.'. $ext; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd.json');
        $this->assertEquals('lloyd.json',$response);
    }
    public function testParamExecuteDefaultDispatched(){
        $r = $this->router();
        $r->get('/hello/:name', function($name, $ext='json'){ return $name. '.'. $ext; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd');
        $this->assertEquals('lloyd.json',$response);

        $r = $this->router(true);
        $r->get('/hello/:name', function($name, $ext='json'){ return $name. '.'. $ext; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd');
        $this->assertEquals('lloyd.json',$response);
    }
    public function testParamExecuteDispatched(){
        $r = $this->router();
        $r->get('/hello/:name', function($name, $ext){ return $name. '.'. $ext; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd');
        $this->assertEquals('lloyd.',$response);

        $r = $this->router(true);
        $r->get('/hello/:name', function($name, $ext){ return $name. '.'. $ext; });
        $response = $r->execute(array(), 'GET', '/hello/lloyd');
        $this->assertEquals('lloyd.',$response);
    }
    public function testParamExecute1Dispatched(){
        $r = $this->router();
        $r->get('/hello/:name', function($name, $ext){ return $name. '.'. $ext; });
        $response = $r->execute(array('ext'=>'js'), 'GET', '/hello/lloyd');
        $this->assertEquals('lloyd.js',$response);

        $r = $this->router(true);
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
    public function testCustomerHookHandleErrorOutside(){
        $r = $this->router(); 
        $r->hook('auth', function($params){
            return false;
        });
        $r->get('/hello/:name', function($name, $ext){ return $name; }, array('auth'));
        $router = $this;
        set_error_handler(function($errno, $errstr) use ($router){
            $router->assertEquals('"406" not defined to handler error: Failed to execute hook: auth', $errstr);
        });
        $response = $r->execute(array(), 'GET', '/hello/lloyd');
        restore_error_handler();
    }
    public function testCustomerHookNotHandleInside(){
        $r = $this->router();
        $r->hook('auth', function($params){
            return false;
        })->error(406, function($message){
            return $message;
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

    // test in build-in server
    // see the server side code in "example.php" 
    public function getRequest(){
        return new \Simplon\Request\Request();
    }
    /**
     * @requires PHP 5.4
     */
    public function testGet405(){
        $response = $this->getRequest()->get('http://127.0.0.1:8889/foo');
        $this->assertEquals(405, $response->getHttpCode());
        $this->assertEquals('Could not resolve [GET] /foo', $response->getContent());
    }
    /**
     * @requires PHP 5.4
     */
    public function testGet401(){
        $response = $this->getRequest()->get('http://127.0.0.1:8889/hello/world/again');
        $this->assertEquals(401, $response->getHttpCode());
        $this->assertEquals('Forbiden', $response->getContent());
    }
    /**
     * @requires PHP 5.4
     */
    public function testGet200(){
        // test '/hello/:name'
        $response = $this->getRequest()->get('http://127.0.0.1:8889/hello/lloyd');
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertEquals('Hello lloyd !!!', $response->getContent());
        // test '/hello/:name/again'
        $response = $this->getRequest()->get('http://127.0.0.1:8889/hello/lloyd/again');
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertEquals('Hello lloyd again !!!', $response->getContent());
        // test '/hello/:name.:ext'
        $response = $this->getRequest()->get('http://127.0.0.1:8889/hello/lloyd.json');
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertEquals('{"name":"lloyd"}', $response->getContent());
        $response = $this->getRequest()->get('http://127.0.0.1:8889/hello/lloyd.js?jsoncallback=test');
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertEquals('test({"name":"lloyd"})', $response->getContent());
        $response = $this->getRequest()->get('http://127.0.0.1:8889/hello/lloyd.jsx?jsoncallback=test');
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertEquals('test({"code":1,"msg":"error message..."})', $response->getContent());
    }
    /**
     * @requires PHP 5.4
     */
    public function testPost401(){
        $response = $this->getRequest()->post('http://127.0.0.1:8889/hello', array('name'=>'world'));
        $this->assertEquals(401, $response->getHttpCode());
        $this->assertEquals('Forbiden', $response->getContent());
    }
    /**
     * @requires PHP 5.4
     */
    public function testPost200(){
        $response = $this->getRequest()->post('http://127.0.0.1:8889/hello', array('name'=>'lloyd'));
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertEquals('Hello lloyd !!!', $response->getContent());
        // post data using json format.
        $response = $this->getRequest()->post('http://127.0.0.1:8889/hello', array('name'=>'lloyd'), array(CURLOPT_HTTPHEADER=>array('Content-Type: application/json')), \Simplon\Request\Request::DATA_FORMAT_JSON);
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertEquals('Hello lloyd !!!', $response->getContent());
    }
}
