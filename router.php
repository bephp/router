<?php 
/**
 * @author Lloyd Zhou (lloydzhou@qq.com)
 * A barebones router for PHP. It matches urls and executes PHP functions.
 * automatic get variable based on handler function parameter list.
 */
class Router {
    protected $_tree = array();
    protected $_error = array();
    protected $_hook = array();
    const COLON = ':';
    const SEPARATOR = '/';
    const LEAF = 'LEAF';
    const HOOK = 'HOOK';
    protected function split($path){
        return strlen($path) > 0 && preg_match("@([^/.]+)(.*)@", $path, $m) ? array(false, $m[1], $m[2]) : array(true, '', '');
    }
    /* helper function to create the tree based on urls, handlers will stored to leaf. */
    protected function match_one_path(&$node, $path, $cb, $hook){
        list($leaf, $token, $path) = $this->split($path);
        if ($token && !array_key_exists($token, $node))
            $node[$token] = array();
        if (!$leaf) 
            return $this->match_one_path($node[$token], $path, $cb, $hook);
        $node[self::LEAF] = $cb;
        $node[self::HOOK] = (array)($hook);
    }
    /* helper function to find handler by $path. */
    protected function _resolve($node, $path, $params){
        list($leaf, $current_token, $path) = $this->split($path);
        if ($leaf && array_key_exists(self::LEAF, $node)) 
            return array($node[self::LEAF], $params, $node[self::HOOK]);
        foreach($node as $child_token=>$child_node){
            if ($child_token == $current_token)
                return $this->_resolve($child_node, $path, $params);
            /**
             * if $current_token not null, and $child_token start with ":"
             * set the parameter named $pname and resolve next $path.
             * if can not resolve with next $path, restore the parameter named $pname.
             */
            if ($current_token && $child_token[0] == self::COLON){
                $pname = substr($child_token, 1);
                $pvalue = array_key_exists($pname, $params) ? $params[$pname] : null;
                $params[$pname] = $current_token;
                if ($leaf) return array($child_node[self::LEAF], $params);
                list($cb, $params, $hook) = $this->_resolve($child_node, $path, $params);
                if (is_callable($cb)) return array($cb, $params, $hook);
                $params[$pname] = $pvalue;
            }
        }
        return array(false, '', null);
    }
    public function resolve($method, $path, $params){
        if (strlen($path) == 0 || !array_key_exists($method, $this->_tree)) return array(null, "Unknown method: $method");
        return $this->_resolve($this->_tree[$method], $path, $params);
    }
    /* API to find handler and execute it by parameters. */
    public function execute($params=array(), $method=null, $path=null){
        $method = $method ? $method : $_SERVER['REQUEST_METHOD'];
        $path = self::SEPARATOR. trim($path ? $path : parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), self::SEPARATOR);
        list($cb, $params, $hook) = $this->resolve($method, $path, $params);
        if (!is_callable($cb)) return array(null, $this->error(405, "Could not resolve [$method] $path"));
        /**
         * merge the $roter and all $request values into $params.
         * auto call the "before" hook before execute the callback handler, and call "after" hook with return value of handler.
         * need define the hook with @param $params, and @return $params, so can change it in the hook handler.
         * if the hook return false, will trigger 406 error handler.
         */
        $params = array_merge($params, array('router'=>$this), $_SERVER, $_REQUEST, $_COOKIE, isset($_SESSION)?$_SESSION:array());
        foreach(array_merge(array('before'), $hook) as $i=>$h){
            if (!($params = $this->hook($h, $params))) return $this->error(406, "Failed to execute hook: $h");
        }
        /**
         * auto get the variable list based on the callback handler parameter list.
         * if the named parameter set in user defined $params or in request, get the value.
         * if the named parameter not set, get the default value in callback handler.
         */
        $args = (new ReflectionFunction($cb))->getParameters();
        array_walk($args, function(&$p, $i, $params){
            $p = isset($params[$p->getName()]) ? $params[$p->getName()] : ($p->isOptional() ? $p->getDefaultValue() : null);
        }, $params);
        /* execute the callback handler and pass the result into "after" hook handler.*/
        return $this->hook('after', call_user_func_array($cb, $args));
    }
    public function match($method, $path, $cb, $hook=array()){
        if (!is_array($method)) $method = array($method=>array($path=>$cb));
        foreach($method as $m=>$routes){
            if (!array_key_exists($m, $this->_tree)) $this->_tree[$m] = array();
            $this->match_one_path($this->_tree[$m], $path, $cb, $hook);
        }
        return $this;
    }
    /* register api based on request method. also register "error" and "hook" API. */
    public function __call($name, $args){
        if (in_array($name, array('get', 'post', 'put', 'patch', 'delete', 'trace', 'connect', 'options', 'head'))){
            array_unshift($args, strtoupper($name));
            return call_user_method_array('match', $this, $args);
        }
        if (in_array($name, array('error', 'hook'))){
            $key = array_shift($args);
            if (($_name = '_'. $name) && isset($args[0]) && is_callable($args[0]))
                $this->{$_name}[$key] = $args[0];
            elseif (isset($this->{$_name}[$key]) && is_callable($this->{$_name}[$key]))
                return call_user_func_array($this->{$_name}[$key], $args);
            return $this;
        }
    }
}

