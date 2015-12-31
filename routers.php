<?php 
/**
 * @author Lloyd Zhou (lloydzhou@qq.com)
 * A barebones router for PHP. It matches urls and executes PHP functions.
 * automatic get variable based on handler function parameter list.
 */
function router($tree=array(), $error=array(), $hook=array()) {
    list($colon, $separator, $leaf, $ctypes) = array(':', '/', 'LEAF', array('A' => 'alnum', 'a' => 'alpha', 'd' => 'digit', 'x' => 'xdigit', 'l' => 'lower', 'u' => 'upper'));
    function match_one_path(&$node, $tokens, $cb, $hook, $colon, $leaf) {
        $token = array_shift($tokens);
        if (!array_key_exists($colon, $node))
            $node[$colon] = array();
        $is_token = ($token && $colon == $token[0]);
        $real_token = $is_token ? substr($token, 1) : $token;
        if ($is_token) $node = &$node[$colon];
        if ($real_token && !array_key_exists($real_token, $node))
            $node[$real_token] = array();
        if ($real_token)
            return match_one_path($node[$real_token], $tokens, $cb, $hook, $colon, $leaf);
        $node[$leaf] = array($cb, (array)($hook));
    };
    $match = function($method, $path, $cb, $hook=array()) use (&$tree, $colon, $separator, $leaf){
        foreach((array)$path as $p){
            $tokens = explode($separator, str_replace('.', $separator, trim($p, $separator)));
            foreach((array)$method as $m){
                if (!array_key_exists($m, $tree)) $tree[$m] = array();
                match_one_path($tree[$m], $tokens, $cb, $hook, $colon, $leaf);
            }
        }
    };
    function _resolve($node, $tokens, $params, $depth=0, $colon, $leaf, $ctypes){
        if ($depth == 0 && !$tokens[0]) return _resolve($node, $tokens, $params, $depth+1, $colon, $leaf, $ctypes);
        $current_token = isset($tokens[$depth])?$tokens[$depth]:'';
        if (!$current_token && array_key_exists($leaf, $node))
            return array($node[$leaf][0], $node[$leaf][1], $params);
        if (array_key_exists($current_token, $node))
            return _resolve($node[$current_token], $tokens, $params, $depth+1, $colon, $leaf, $ctypes);
        foreach($node[$colon] as $child_token=>$child_node){
            /**
             * if defined ctype validate function, for the current params, call the ctype function to validate $current_token
             * example: "/hello/:name:a.json", and url "/hello/lloyd.json" will call "ctype_alpha" to validate "lloyd"
             */
            if ($pos = stripos($child_token, $colon)){
                if (($m=substr($child_token, $pos+1)) && isset($ctypes[$m]) && !call_user_func('ctype_'.$ctypes[$m], $current_token))
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
            if (!$current_token && array_key_exists(self::LEAF, $child_node))
                return array($child_node[$leaf][0], $child_node[$leaf][1], $params);
            list($cb, $hook, $params) = _resolve($child_node, $tokens, $params, $depth+1, $colon, $leaf, $ctypes);
            if ($cb) return array($cb, $hook, $params);
            $params[$child_token] = $pvalue;
        }
        return array(false, '', null);
    }
    function resolve($method, $path, $params, $tree, $separator, $colon, $leaf, $ctypes){
        if (!array_key_exists($method, $tree)) return array(null, "Unknown method: $method", null);
        $tokens = explode($separator, str_replace('.', $separator, $path));
        return _resolve($tree[$method], $tokens, $params, 0, $colon, $leaf, $ctypes);
    }
    $execute = function($params, $method, $path) use (&$tree, $separator, $colon, $leaf, $ctypes, &$call) {
        list($cb, $hook, $params) = resolve($method, $path, $params, $tree, $separator, $colon, $leaf, $ctypes);
        if (!is_callable($cb)) return $call('error', 405, "Could not resolve [$method] $path");
        foreach(array_merge(array('before'), $hook) as $i=>$h){
            if (!is_array($params = $call('hook', $h, $params))) return $call('error', 406, "Failed to execute hook: $h");
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
        }, $params);
        /* execute the callback handler and pass the result into "after" hook handler.*/
        return $call('hook', 'after', call_user_func_array($cb, $args));
    };
    $call = function() use (&$tree, &$error, &$hook, $match, $execute) {
        $args = func_get_args();
        if (isset($args[0]) && is_string($args[0])){
            if(in_array($args[0], array('get', 'post', 'head', 'delete', 'options')))
                return call_user_func_array($match, $args);
            elseif('match' == $args[0] && array_shift($args))
                return call_user_func_array($match, $args);
            elseif(in_array($args[0], array('error', 'hook')) && ($name = array_shift($args)) && ($key = array_shift($args))) {
                if (isset($args[0]) && is_callable($args[0]))
                    ${$name}[$key] = $args[0];
                else if (isset(${$name}[$key]) && is_callable(${$name}[$key]))
                    return call_user_func_array(${$name}[$key], $args);
                else return ('error' == $name) ? trigger_error('"'.$key.'" not defined to handler error: '.$args[0]) : $args[0];
            }
        } else{
            return call_user_func_array($execute, $args);
        }
    };
    return $call;
}

$router = router();
$router('get', array('/', '/foo/:bar:d/zoo'), function($bar='bar'){var_export('execute');var_export($bar); });
$router('match', 'get', '/foo/barz/zoo', function($bar='bar'){ var_export('execute');var_export($bar); });
$router('hook', 'before', function($params){
    return $params;
});
$router('error', 405, function($message=''){ echo "MESSAGE: \n",  $message; });
$router('error', 405, 'test message');
$router(array(), 'get', '/foo/1/zoo');

