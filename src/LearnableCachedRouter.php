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

    public function dispatch($method = 'GET', $uri = '', $domain = null){

        //slash uri
        $uri = $this->slashUri($uri);

        //found in cache
        if($route = $this->dispatchViaCache($method, $uri, $domain)){
            return $route;
        }

        //run normal dispatcher
        $route = parent::dispatch($method, $uri, $domain);

        //found it, lets add it to the map
        if($route instanceof Route){
            $this->cache->set($this->getCacheKey($method, $uri, $domain), $route);
        }

        //and return
        return $route;
    }

    private function dispatchViaCache($method = 'GET', $uri = '', $domain = null){
        return $this->cache->get($this->getCacheKey($method, $uri, $domain));
    }

    private function getCacheKey($method = 'GET', $uri = '', $domain = null){
        $key = ($domain === null) ? $method . '|' . $uri : $method . '|' . $domain . '|' . $uri;
        return $key;
    }

}