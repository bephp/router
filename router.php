<?php 

class Router {
    protected $_tree = array();
    const COLON = ':';
    const SEPARATOR = '/';
    const LEAF = 'LEAF';
    protected function match_one_path(&$node, $path, $cb){
        $pos = strpos($path, self::SEPARATOR, 1);
        if (false === $pos) return $node[substr($path, 1)] = array(self::LEAF=>$cb);
        $token = substr($path, 1, $pos-1);
        if (!array_key_exists($token, $node)) $node[$token] = array();
        $this->match_one_path($node[$token], substr($path, $pos), $cb);
    }
    protected function _resolve($node, $path, $params){
        $pos = strpos($path, self::SEPARATOR, 1);
        if (false === $pos && array_key_exists(substr($path, 1), $node))
            return array($node[substr($path, 1)][self::LEAF], $params);
        $current_token = substr($path, 1, $pos-1);
        $path = substr($path, $pos);
        foreach($node as $child_token=>$child_node){
            if ($child_token == $current_token)
                return $this->_resolve($child_node, $path, $params);
        }
        foreach($node as $child_token=>$child_node){
            if ($child_token[0] == self::COLON){
                $pname = substr($child_token, 1);
                $pvalue = array_key_exists($pname, $params) ? $params[$pname] : null;
                $params[$pname] = $current_token;
                list($cb, $params) = $this->_resolve($child_node, $path, $params);
                if (is_callable($cb)) return array($cb, $params);
                $params[$pname] = $pvalue;
            }
        }
        return array(false, '');
    }
    public function resolve($method, $path, $params){
        $node = $this->_tree[$method];
        if (!array_key_exists($method, $this->_tree)) return array(null, 'Unknown method: '. $method);
        return $this->_resolve($node, $path, $params);
    }
    public function execute($method, $path, $params){
        list($cb, $params) = $this->resolve($method, $path, $params);
        if (!is_callable($cb)) return array(null, 'Could not resolve ['. $method. '] '. $path);
        // need format params.
        $args = (new ReflectionFunction($cb))->getParameters();
        array_walk($args, function(&$p, $i, $params){
            $p = array_key_exists($p->getName(), $params)?$params[$p->getName()]
                :(array_key_exists($p->getName(), $_POST)?$_POST[$p->getName()]
                :(array_key_exists($p->getName(), $_GET)?$_GET[$p->getName()]
                :($p->isOptional()?$p->getDefaultValue():null)));
        }, $params);
        return array(true, call_user_func_array($cb, $args));
    }
    public function match($method, $path, $cb){
        if (!is_array($method)) $method = array($method=>array($path=>$cb));
        foreach($method as $m=>$routes){
            if (!array_key_exists($m, $this->_tree)) $this->_tree[$m] = array();
            $this->match_one_path($this->_tree[$m], $path, $cb);
        }
    }
    public function __call($name, $args){
        if (in_array($name, array('get', 'post', 'put', 'patch', 'delete', 'trace', 'connect', 'options', 'head'))){
            array_unshift($args, strtoupper($name));
            return call_user_method_array('match', $this, $args);
        }
    }
}


$r = new Router();

$r->get('/test/:id/aaa', function($id=2, $p=1, $a=1){var_dump($id, $p, $a);});
var_dump($r);
var_dump($r->execute('GET', '/test/12/aaa', array('id'=>21, 'aaa'=>'bbb')));


