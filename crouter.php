<?php 
/**
 * @author Lloyd Zhou (lloydzhou@qq.com)
 * compile the Router to source code, the $_tree, $_error and $_hook is plain array.
 * no need to add so many callback handlers, can save time.
 */

include ("router.php");

class CRouter extends Router {
    protected $target = null;
    protected $debug = null;
    protected $compile = null;

    /* helper function to get the compile status*/
    protected function needCompile(){
        if (null === $this->compile){
            $this->compile = ($this->debug || !file_exists($this->target) 
                || @filemtime($_SERVER['SCRIPT_FILENAME'])>@filemtime($this->target));
        }
        return $this->compile;
    }
    /* helper function to dump callback handlers into plain array source code.*/
    protected function export ($var, $return=false){
        if ($var instanceof Closure){ /* dump anonymous function in to plain code.*/
            $ref = new ReflectionFunction($var);
            $file = new SplFileObject($ref->getFileName());
            $file->seek($ref->getStartLine()-1);
            $result = '';
            while ($file->key() < $ref->getEndLine()){
                $result .= $file->current();
                $file->next();
            }
            $begin = strpos($result, 'function');
            $end = strrpos($result, '}');
            $result = substr($result, $begin, $end - $begin + 1);
        } elseif (is_object($var)){ /* dump object with construct function. */
            $result = 'new '. get_class($var). '('. $this->export(get_object_vars($var), true). ')';
        } elseif (is_array($var)) { /* dump array in plain array.*/
            $array = array ();
            foreach($var as $k=>$v) $array[] = var_export($k, true).' => '. $this->export($v, true);
            $result = 'array('. implode(', ', $array). ')';
        } else $result = var_export($var, true);
        if (!$return) print $result;
        return $result;
    }

    /* construct function */
    public function __construct($target=null, $debug=false){
        $this->debug = $debug;
        $this->target = $target?$target:$_SERVER['SCRIPT_FILENAME'].'c';
    }
    /**
     * compile router into source code, and execute the compiled source code with parameters.
     */
    public function execute($params=array(), $method=null, $path=null){
        if ($this->needCompile()){
            $code = "<?php\nreturn new Router("
                . $this->export($this->_tree, true). ', '
                . $this->export($this->_error, true). ', '
                . $this->export($this->_hook, true). ');';
            file_put_contents($this->target, $code);
        }
        $router = include ($this->target);
        return $router->execute($params, $method, $path);
    }
}

