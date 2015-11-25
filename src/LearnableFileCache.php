<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 25/11/15
 * Time: 18:31
 */

namespace Conformity\Router;


class LearnableFileCache implements LearnableCacheInterface
{
    protected $cache = [];

    protected $file;

    public function __construct($file){
        $this->file = $file;
        if(is_file($this->file)){
            $this->cache = unserialize(file_get_contents($this->file));
        }
    }

    public function get($key)
    {
        return (isset($this->cache[$key])) ? $this->cache[$key] : false;
    }

    public function set($key, Route $route)
    {
        $this->cache[$key] = $route;
        file_put_contents($this->file, serialize($this->cache));
    }

}