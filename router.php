<?php 

class Router {
    protected $_tree = array();
    protected $_err = array();
    const COLON = ':';
    const SEPARATOR = '/';
    const LEAF = 'LEAF';
    protected function split($path){
        $pos = strpos($path, self::SEPARATOR, 1);
        return array(($leaf = false === $pos),
            $leaf ? substr($path, 1) : substr($path, 1, $pos-1),
            $leaf ? '' : substr($path, $pos));
    }
    protected function match_one_path(&$node, $path, $cb){
        list($leaf, $token, $path) = $this->split($path);
        if ($leaf) return $node[$token] = array(self::LEAF=>$cb);
        if (!array_key_exists($token, $node)) $node[$token] = array();
        $this->match_one_path($node[$token], $path, $cb);
    }
    protected function _resolve($node, $path, $params){
        list($leaf, $current_token, $path) = $this->split($path);
        if ($leaf && array_key_exists($current_token,  $node)) 
            return array($node[$current_token][self::LEAF], $params);
        if ($leaf && array_key_exists(self::LEAF, $node)) 
            return array($node[self::LEAF], $params);
        foreach($node as $child_token=>$child_node){
            if ($child_token == $current_token)
                return $this->_resolve($child_node, $path, $params);
        }
        foreach($node as $child_token=>$child_node){
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
        if (!array_key_exists($method, $this->_tree)) return array(null, "Unknown method: $method");
        return $this->_resolve($node, $path, $params);
    }
    public function execute($params=array(), $method=null, $path=null){
        $method = $method ? $method : $_SERVER['REQUEST_METHOD'];
        $path = rtrim($path ? $path : parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), self::SEPARATOR);
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
    public function match($method, $path, $cb){
        if (!is_array($method)) $method = array($method=>array($path=>$cb));
        foreach($method as $m=>$routes){
            if (!array_key_exists($m, $this->_tree)) $this->_tree[$m] = array();
            $this->match_one_path($this->_tree[$m], $path, $cb);
        }
        return $this;
    }
    public function __call($name, $args){
        if (in_array($name, array('get', 'post', 'put', 'patch', 'delete', 'trace', 'connect', 'options', 'head'))){
            array_unshift($args, strtoupper($name));
            return call_user_method_array('match', $this, $args);
        }
    }
    public function error(){
        $argv = func_get_args();
        if (func_num_args()>1 && is_callable($argv[1]))
            $this->_err[$argv[0]] = $argv[1];
        elseif(isset($this->_err[$argv[0]]) && is_callable($this->_err[$argv[0]]))
            call_user_func_array($this->_err[$argv[0]], array_slice($argv, 1));
        return $this;
    }
}

