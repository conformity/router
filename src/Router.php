<?php

namespace Conformity\Router;


use Conformity\Router\Exception\MethodNotAllowedException;
use Conformity\Router\Exception\NotFoundException;

class Router
{

    protected $routes = [];

    protected $segmentsMap = [];

    protected $matchers = [];

    protected $modifyers = [];

    public function __construct(){

    }

    public function addMatcher($name, $callback){
        $this->matchers[$name] = $callback;
        return $this;
    }

    public function addModifyer($name, $callback){
        $this->modifyers[$name] = $callback;
        return $this;
    }

    private function addRoute($method, $uri, $callback){
        return new Route($method, $uri, $callback);
    }

    public function match($method, $uri, $callback = null){
        $route = $this->addRoute($method, $uri, $callback);;
        $this->routes[$route->getUri()] = $route;

        foreach($route->getPossibleSegmentsCount() as $count){
            $this->segmentsMap[$count][] = $route->getUri();
        }

        return $route;
    }

    public function head($uri, $callback){
        return $this->match('HEAD', $uri, $callback);
    }

    public function get($uri, $callback){
        return $this->match(['GET', 'HEAD'], $uri, $callback);
    }

    public function post($uri, $callback){
        return $this->match('POST', $uri, $callback);
    }

    public function put($uri, $callback){
        return $this->match('PUT', $uri, $callback);
    }

    public function patch($uri, $callback){
        return $this->match('PATCH', $uri, $callback);
    }

    public function delete($uri, $callback){
        return $this->match('DELETE', $uri, $callback);
    }

    public function any($uri, $callback){
        return $this->match(['HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $uri, $callback);
    }

    public function dispatch($method = 'GET', $uri = ''){

        $uri = (strpos($uri, '/') !== 0) ? '/' . $uri : $uri;

        //lookup exact matches first
        if(array_key_exists($uri, $this->routes)){
            $route = $this->routes[$uri];
            if(!in_array($method, $route->getMethods())){
                throw new MethodNotAllowedException($route->getMethods(), sprintf('The uri requested: %s isn\'t allowed via: %s', $uri, $method));
            }

            $route->addModifyers($this->modifyers);
            $route->addMatchers($this->matchers);
            return $route;
        }

        //find possible matches via segment count
        $uriSegments = array_filter(explode('/', $uri));

        $possibleRoutes = isset($this->segmentsMap[count($uriSegments)]) ? $this->segmentsMap[count($uriSegments)] ? [];

        foreach($possibleRoutes as $route){

            $route = $this->routes[$route];

            $route->addModifyers($this->modifyers);
            $route->addMatchers($this->matchers);

            if(!$route->matches($uri)){
                continue;
            }

            //we have a match! but does it satisfy the http method?
            if(!in_array($method, $route->getMethods())){
                throw new MethodNotAllowedException($route->getMethods(), sprintf('The uri requested: %s isn\'t allowed via: %s', $uri, $method));
            }

            //all good return
            return $route;
        }

        //if we get here we havent found a route :-(
        throw new NotFoundException(sprintf('The uri requested: %s cannot be found', $uri));
    }

}