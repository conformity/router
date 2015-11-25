<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 25/11/15
 * Time: 18:31
 */

namespace Conformity\Router;


class LearnableArrayCache implements LearnableCacheInterface
{
    protected $cache = [];

    public function get($key)
    {
        return (isset($this->cache[$key])) ? $this->cache[$key] : false;
    }

    public function set($key, Route $route)
    {
        $this->cache[$key] = $route;
    }

}