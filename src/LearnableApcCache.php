<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 25/11/15
 * Time: 18:31
 */

namespace Conformity\Router;


class LearnableApcCache implements LearnableCacheInterface
{

    public function __construct(){
        if(!extension_loaded('apc')){
            throw new \Exception("You must have the Apc extension installed to use this cache driver!");
        }
    }

    public function get($key)
    {
        $route = apc_fetch($key);
        return (false !== $cache) ? unserialize($route) : false;
    }

    public function set($key, Route $route)
    {
        apc_store($key, serialize($route));
    }

}