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
    protected function split($path){
        return strlen($path) > 0 && preg_match("@([^/.]+)(.*)@", $path, $m) ? array(false, $m[1], $m[2]) : array(true, '', '');
    }
    // helper function to create the tree based on urls, handlers will stored to leaf.
    protected function match_one_path(&$node, $path, $cb){
        list($leaf, $token, $path) = $this->split($path);
        if ($leaf) return $token ? $node[$token] = array(self::LEAF=>$cb) : $node[self::LEAF] = $cb;
        if (!array_key_exists($token, $node)) $node[$token] = array();
        $this->match_one_path($node[$token], $path, $cb);
    }
    // helper function to find handler by $path.
    protected function _resolve($node, $path, $params){
        list($leaf, $current_token, $path) = $this->split($path);
        if ($leaf && array_key_exists(self::LEAF, $node)) 
            return array($node[self::LEAF], $params);
        foreach($node as $child_token=>$child_node){
            if ($child_token == $current_token)
                return $this->_resolve($child_node, $path, $params);
            if ($child_token[0] == self::COLON){
                $pname = substr($child_token, 1);
                $pvalue = array_key_exists($pname, $params) ? $params[$pname] : null;
                $params[$pname] = $current_token;
                if ($leaf) return array($child_node[self::LEAF], $params);
                list($cb, $params) = $this->_resolve($child_node, $path, $params);
                if (is_callable($cb)) return array($cb, $params);
                $params[$pname] = $pvalue;
            }
        }
        return array(false, '');
    }
    public function resolve($method, $path, $params){
        $node = $this->_tree[$method];
        if (strlen($path) == 0 || !array_key_exists($method, $this->_tree)) return array(null, "Unknown method: $method");
        return $this->_resolve($node, $path, $params);
    }
    // API to find handler and execute it by parameters.
    public function execute($params=array(), $method=null, $path=null){
        $method = $method ? $method : $_SERVER['REQUEST_METHOD'];
        $path = self::SEPARATOR. trim($path ? $path : parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), self::SEPARATOR);
        $params['router'] = $this;
        list($cb, $params) = $this->resolve($method, $path, $params);
        if (!is_callable($cb)) return array(null, $this->error(405, "Could not resolve [$method] $path"));
        //TODO need format params.
        $args = (new ReflectionFunction($cb))->getParameters();
        array_walk($args, function(&$p, $i, $params){
            $p = array_key_exists($p->getName(), $params)?$params[$p->getName()]
                :($p->isOptional()?$p->getDefaultValue():null);
        }, array_merge($params, $_SERVER, $_REQUEST, $_COOKIE, isset($_SESSION)?$_SESSION:array()));
        return array(true, call_user_func_array($cb, array_map(function($v){
            return is_string($v)?rawurldecode($v):$v;
        }, $args)));
    }
    public function match($method, $path, $cb, $hooks=array()){
        if (!is_array($method)) $method = array($method=>array($path=>$cb));
        foreach($method as $m=>$routes){
            if (!array_key_exists($m, $this->_tree)) $this->_tree[$m] = array();
            $this->match_one_path($this->_tree[$m], $path, $cb);
        }
        return $this;
    }
    // register api based on request method.
    public function __call($name, $args){
        if (in_array($name, array('get', 'post', 'put', 'patch', 'delete', 'trace', 'connect', 'options', 'head'))){
            array_unshift($args, strtoupper($name));
            return call_user_method_array('match', $this, $args);
        }
        if (in_array($name, array('error', 'hook'))){
            $_name = '_'. $name;
            if (isset($args[1]) && is_callable($args[1]))
                $this->{$_name}[$args[0]] = $args[1];
            elseif (isset($this->{$_name}[$args[0]]) && is_callable($this->{$_name}[$args[0]]))
                call_user_func_array($this->{$_name}[$args[0]], array_slice($args, 1));
            return $this;
        }
    }
}

