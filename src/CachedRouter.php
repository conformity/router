<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 25/11/15
 * Time: 13:16
 */

namespace Conformity\Router;

class CachedRouter extends Router implements RouterInterface
{

    protected $cacheMap;

    protected $file;

    protected $changed = false;
    
    public function __construct($file){
        $this->cacheMap = (is_file($file)) ? require $file : [];
        $this->file = $file;
    }

    public function save(){
        if($this->changed === true){
            file_put_contents($this->file, "<?php\n\nreturn " . var_export($this->cacheMap, true) . ";");
        }
    }

    public function dispatch($method = 'GET', $uri = '', $save = true){

        //found in cache
        if($route = $this->dispatchViaCacheMap($method, $uri)){
            return $route;
        }

        //run normal dispatcher
        $route = parent::dispatch($method, $uri);

        //found it, lets add it to the map
        if($route instanceof Route && $route->requiresMatch() === true){
            $this->cacheMap[$uri] = serialize($route);
            $this->changed = true;

            if($save === true){
                $this->save();
            }
        }

        return $route;
    }

    private function dispatchViaCacheMap($method = 'GET', $uri = ''){
        if(array_key_exists($method.'|'.$uri, $this->cacheMap)){

            return unserialize($this->cacheMap[$method.'|'.$uri]);

        }
        return false;
    }

}