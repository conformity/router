<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 25/11/15
 * Time: 13:16
 */

namespace Conformity\Router;


use Conformity\Router\Exception\MethodNotAllowedException;

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
        if($route instanceof Route){
            $this->cacheMap[$uri] = $route->getUri();
            $this->changed = true;
        }

        if($save === true){
            $this->save();
        }

        return $route;
    }

    private function dispatchViaCacheMap($method = 'GET', $uri = ''){
        if(array_key_exists($uri, $this->cacheMap)){

            //loop each route for this uri and assign if method matches
            $routes = $this->routes[$this->cacheMap[$uri]];


            //we need to store methods to send back later
            $methods = [];
            $route = null;
            foreach($routes as $instance){
                $methods = array_merge($methods, $instance->getMethods());
                if(in_array($method, $instance->getMethods())){
                    $route = $instance;
                    break;
                }
            }

            //the route uri exists but there isn't a route matching this method
            if(!$route instanceof Route){
                throw new MethodNotAllowedException($methods, sprintf('The uri requested: %s isn\'t allowed via: %s', $uri, $method));
            }

            $route->addModifyers($this->modifyers);
            $route->addMatchers($this->matchers);

            //we have to call this to setup the params
            if($route->matches($uri)){
                return $route;
            }
        }
        return false;
    }

}