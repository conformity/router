<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 25/11/15
 * Time: 13:16
 */

namespace Conformity\Router;

class LearnableCachedRouter extends Router implements RouterInterface
{

    protected $cache;
    
    public function __construct(LearnableCacheInterface $cache){
        $this->cache = $cache;
    }

    public function dispatch($method = 'GET', $uri = ''){

        //slash uri
        $uri = $this->slashUri($uri);

        //found in cache
        if($route = $this->dispatchViaCache($method, $uri)){
            return $route;
        }

        //run normal dispatcher
        $route = parent::dispatch($method, $uri);

        //found it, lets add it to the map
        if($route instanceof Route){
            $this->cache->set($method . '|' . $uri, $route);
        }

        //and return
        return $route;
    }

    private function dispatchViaCache($method = 'GET', $uri = ''){
        return $this->cache->get($method . '|' . $uri);
    }

}