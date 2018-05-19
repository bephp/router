<?php 
/**
 * @author Lloyd Zhou (lloydzhou@qq.com)
 * A barebones router for PHP. It matches urls and executes PHP functions.
 * automatic get variable based on handler function parameter list.
 */
class Router {
    protected $prefix = '';
    protected $prefix_hook = array();
    protected $_tree = array();
    protected $_events = array();
    protected $_ctypes = array('A' => 'alnum', 'a' => 'alpha', 'd' => 'digit', 'x' => 'xdigit', 'l' => 'lower', 'u' => 'upper');
    protected $_default_node = array(self::COLON => array());
    const COLON = ':';
    const SEPARATOR = '/';
    const LEAF = 'LEAF';
    public function __construct($tree=array(), $events=array()){
        $this->_tree = $tree;
        $this->_events = $events;
    }
    /* helper function to create the tree based on urls, handlers will stored to leaf. */
    protected function match_one_path(&$node, $tokens, $cb, $hook){
        $token = array_shift($tokens);
        $is_token = ($token && self::COLON == $token[0]);
        $real_token = $is_token ? substr($token, 1) : $token;
        if ($is_token) $node = &$node[self::COLON];
        if ($real_token && !array_key_exists($real_token, $node))
            $node[$real_token] = $this->_default_node;
        if ($real_token)
            return $this->match_one_path($node[$real_token], $tokens, $cb, $hook);
        $node[self::LEAF] = array($cb, (array)($hook));
    }
    /* helper function to find handler by $path. */
    protected function _resolve($node, $tokens, $params, $depth=0){
        $depth = ($depth == 0 && !$tokens[0]) ? 1 : $depth;
        $current_token = isset($tokens[$depth])?$tokens[$depth]:'';
        if (!$current_token && array_key_exists(self::LEAF, $node))
            return array($node[self::LEAF][0], $node[self::LEAF][1], $params);
        if (array_key_exists($current_token, $node))
            return $this->_resolve($node[$current_token], $tokens, $params, $depth+1);
        foreach($node[self::COLON] as $child_token=>$child_node){
            /**
             * if defined ctype validate function, for the current params, call the ctype function to validate $current_token
             * example: "/hello/:name:a.json", and url "/hello/lloyd.json" will call "ctype_alpha" to validate "lloyd"
             */
            if ($pos = stripos($child_token, self::COLON)){
                if (($m=substr($child_token, $pos+1)) && isset($this->_ctypes[$m]) && !call_user_func('ctype_'.$this->_ctypes[$m], $current_token))
                    continue;
                $child_token = substr($child_token, 0, $pos);
            }
            /**
             * if $current_token not null, and $child_token start with ":"
             * set the parameter named $pname and resolve next $path.
             * if can not resolve with next $path, restore the parameter named $pname.
             */
            $pvalue = array_key_exists($child_token, $params) ? $params[$child_token] : null;
            $params[$child_token] = $current_token;
            list($cb, $hook, $params) = $this->_resolve($child_node, $tokens, $params, $depth+1);
            if ($cb) return array($cb, $hook, $params);
            $params[$child_token] = $pvalue;
        }
        return array(false, '', null);
    }
    public function resolve($method, $path, $params){
        $tokens = explode(self::SEPARATOR, str_replace('.', self::SEPARATOR, $path));
        return $this->_resolve(array_key_exists($method, $this->_tree) ? $this->_tree[$method] : $this->_default_node, $tokens, $params);
    }
    /* API to find handler and execute it by parameters. */
    public function execute($params=array(), $method=null, $path=null){
        $method = $method ? $method : $_SERVER['REQUEST_METHOD'];
        $path = $path ? $path : parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        list($cb, $hook, $params) = $this->resolve($method, $path, $params);
        if (!is_callable($cb)) return $this->error(405, "Could not resolve [$method] $path");
        /**
         * merge the $roter and all $request values into $params.
         * auto call the "before" hook before execute the callback handler, and call "after" hook with return value of handler.
         * need define the hook with @param $params, and @return $params, so can change it in the hook handler.
         * if the hook return false, will trigger 406 error handler.
         */
        $input = ((isset($_SERVER['HTTP_CONTENT_TYPE']) && 'application/json' == $_SERVER['HTTP_CONTENT_TYPE'])
            || (isset($_SERVER['CONTENT_TYPE']) && 'application/json' == $_SERVER['CONTENT_TYPE']))
            ? (array)json_decode(file_get_contents('php://input'), true) : array();
        $this->params = array_merge($params, $_SERVER, $_REQUEST, $input, $_FILES, $_COOKIE, isset($_SESSION)?$_SESSION:array(), array('router'=>$this));
        foreach(array_merge(array('before'), $hook) as $i=>$h){
            if (false === $this->hook($h, $this)) return $this->error(406, "Failed to execute hook: $h");
        }
        /**
         * auto get the variable list based on the callback handler parameter list.
         * if the named parameter set in user defined $params or in request, get the value.
         * if the named parameter not set, get the default value in callback handler.
         */
        $ref = is_array($cb) && isset($cb[1]) ? new ReflectionMethod($cb[0], $cb[1]) : new ReflectionFunction($cb);
        $args = $ref->getParameters();
        array_walk($args, function(&$p, $i, $params){
            $p = isset($params[$p->getName()]) ? $params[$p->getName()] : ($p->isOptional() ? $p->getDefaultValue() : null);
        }, $this->params);
        /* execute the callback handler and pass the result into "after" hook handler.*/
        return $this->hook('after', call_user_func_array($cb, $args), $this);
    }
    public function match($method, $path, $cb, $hook=null){
        foreach((array)($method) as $m){
            $m = strtoupper($m);
            if (!array_key_exists($m, $this->_tree)) $this->_tree[$m] = $this->_default_node;
            foreach((array)($path) as $p){
                $tokens = explode(self::SEPARATOR, str_replace('.', self::SEPARATOR, trim($this->prefix.$p, self::SEPARATOR)));
                $this->match_one_path($this->_tree[$m], $tokens, $cb, array_merge($this->prefix_hook, (array)$hook));
            }
        }
        return $this;
    }
    /* register api based on request method. also register "error" and "hook" API. */
    public function __call($name, $args){
        if (in_array($name, array('get', 'post', 'put', 'patch', 'delete', 'trace', 'connect', 'options', 'head'))
            && array_unshift($args, $name))
            return call_user_func_array(array($this, 'match'), $args);
        if (in_array($name, array('group', 'prefix'))){
            $this->prefix = isset($args[0]) && is_string($args[0]) && self::SEPARATOR == $args[0][0] ? $args[0] : '';
            $this->prefix_hook = isset($args[1]) ? (array)$args[1] : array();
        }
        if (in_array($name, array('error', 'hook'))){
            $key = $name. ':'. array_shift($args);
            if (isset($args[0]) && is_callable($args[0]))
                $this->_events[$key] = $args[0];
            else if (isset($this->_events[$key]) && is_callable($this->_events[$key]))
                return $this->checkHandlerParameter($this->_events[$key], $args) ? call_user_func_array($this->_events[$key], $args) : trigger_error('"'.$key.'" missing parameter', E_USER_ERROR);
            else return ('error' == $name) ? trigger_error('"'.$key.'" not defined to handler error: '.$args[0]) : $args[0];
        }
        return $this;
    }
    protected function checkHandlerParameter($handle,$args)
    {
         $reflectionFunc = new ReflectionFunction($handle);
         $countHandleParameters = count($reflectionFunc->getParameters());
         if ($countHandleParameters === 0) {
             return true;
         }
         $countParameters = count($args);
         if ($countParameters < $countHandleParameters) {
             return false;
         }
         return true;
    }
}

