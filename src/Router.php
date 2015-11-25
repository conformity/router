<?php

namespace Conformity\Router;


use Conformity\Router\Exception\MethodNotAllowedException;
use Conformity\Router\Exception\NotFoundException;

class Router implements RouterInterface
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
        $route = $this->addRoute($method, $uri, $callback);

        //save route against uri (must be an array as you could define same route with different methods
        $this->routes[$route->getUri()] = (isset($this->routes[$route->getUri()])) ? $this->routes[$route->getUri()] : [];

        $this->routes[$route->getUri()][] = $route;

        foreach($route->getPossibleSegmentsCount() as $count){
            $this->segmentsMap[$count][] = $route->getUri();
            //it only needs to be there once
            $this->segmentsMap[$count] = array_filter($this->segmentsMap[$count]);
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

    private function findExactRoute($method = 'GET', $uri = ''){
        if(array_key_exists($uri, $this->routes)){

            //loop each route for this uri and assign if method matches
            $routes = $this->routes[$uri];
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

    public function dispatch($method = 'GET', $uri = ''){

        $uri = (strpos($uri, '/') !== 0) ? '/' . $uri : $uri;

        //lookup exact matches first
        if($route = $this->findExactRoute($method, $uri)){
            return $route;
        }

        //find possible matches via segment count
        $uriSegments = array_filter(explode('/', $uri));

        $possibleRoutes = isset($this->segmentsMap[count($uriSegments)]) ? $this->segmentsMap[count($uriSegments)] : [];

        $route = null;

        foreach($possibleRoutes as $routeGroup){

            //loop each route for this uri and assign if method matches
            $routes = $this->routes[$routeGroup];

            //test the first route in the array, the uris are the same so it either matches or it doesnt
            if(!$routes[0]->matches($uri)){
                continue;
            }

            //we need to store methods to send back later
            $methods = [];

            //loop the group to find a route with the method
            foreach($routes as $_route){

                //save methods as we found a match, but maybe not the right method
                $methods = array_merge($methods, $_route->getMethods());

                //not this route, goto the next one
                if(!in_array($method, $_route->getMethods())){
                    continue;
                }

                //great, the uri matches and the method is allowed

                //add global matchers/modifyers
                $_route->addModifyers($this->modifyers);
                $_route->addMatchers($this->matchers);

                //set the route variable
                $route = $_route;

                //we found it!
                break;
            }

            //the route uri exists but there isn't a route matching this method
            if(!$route instanceof Route){
                throw new MethodNotAllowedException($methods, sprintf('The uri requested: %s isn\'t allowed via: %s', $uri, $method));
            }

            //all good return
            return $route;
        }

        //if we get here we havent found a route :-(
        throw new NotFoundException(sprintf('The uri requested: %s cannot be found', $uri));
    }

}