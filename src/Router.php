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

    protected function toArray(){
        return [
            'routes' => $this->routes,
            'segments_map' => $this->segmentsMap,
            'matchers' => $this->matchers,
            'modifyers' => $this->modifyers
        ];
    }

    protected function fromArray($array = []){
        $this->routes = $array['routes'];
        $this->segmentsMap = $array['segments_map'];
        $this->matchers = $array['matchers'];
        $this->modifyers = $array['modifyers'];
        return $this;
    }

    private function slashUri($uri){
        return (strpos($uri, '/') !== 0) ? '/' . $uri : $uri;
    }

    public function addMatcher($name, $callback){
        $this->matchers[$name] = $callback;
        return $this;
    }

    public function addModifyer($name, $callback){
        $this->modifyers[$name] = $callback;
        return $this;
    }

    public function match($method, $uri, $callback = null){

        //add slash
        $uri = $this->slashUri($uri);

        //get segments
        $segments = array_filter(explode('/', $uri));

        //get possible segment count
        $segmentCount = $this->getPossibleSegments($segments);

        //add to map
        foreach($segmentCount as $count){
            $this->segmentsMap[$count][$uri] = $uri;
        }

        //create route array
        $route = [
            'methods' => (array) $method,
            'uri' => $uri,
            'callback' => $callback,
            'segments' => $segments,
            'requires_match' => (strpos($uri, '{') !== false) ? true : false,
        ];

        //save route against uri (must be an array as you could define same route with different methods
        $this->routes[$uri] = (isset($this->routes[$uri])) ? $this->routes[$uri] : [];

        $this->routes[$uri][] = $route;

        return true;
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
                $methods = array_merge($methods, $instance['methods']);
                if(in_array($method, $instance['methods'])){
                    $route = new Route($instance);
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

        $uri = $this->slashUri($uri);

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

            //test the first route in the array, the uris are the same so it either matches or it doesn't
            $first = new Route($routes[0]);

            if(!$first->matches($uri)){
                continue;
            }

            //we need to store methods to send back later
            $methods = [];

            //loop the group to find a route with the method
            foreach($routes as $_route){

                //save methods as we found a match, but maybe not the right method
                $methods = array_merge($methods, $_route['methods']);

                //not this route, goto the next one
                if(!in_array($method, $_route['methods'])){
                    continue;
                }

                //great, the uri matches and the method is allowed, lets new up a route object
                $_route = new Route($_route);

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

        //if we get here we haven't found a route :-(
        throw new NotFoundException(sprintf('The uri requested: %s cannot be found', $uri));
    }

    /**
     * @param $segments
     * @return array|int
     */
    private function getPossibleSegments($segments)
    {
        $segmentCount = count($segments);
        $optionals = $segmentCount;
        foreach ($segments as $segment) {
            if (strpos($segment, '?') !== false) {
                $optionals--;
            }
        }
        $segmentCount = range($optionals, $segmentCount);
        return $segmentCount;
    }

}