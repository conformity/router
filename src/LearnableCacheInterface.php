<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 25/11/15
 * Time: 18:26
 */

namespace Conformity\Router;


interface LearnableCacheInterface
{

    public function get($key);

    public function set($key, Route $route);

}