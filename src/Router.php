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

    protected $names = [];

    public function __construct(){

    }

    protected function slashUri($uri){
        return (strpos($uri, '/') !== 0 && strpos($uri, 'h') !== 0) ? '/' . $uri : $uri;
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

        //save extra data
        if(is_array($callback)){
            $data = $callback;
            foreach($callback as $key => $value){
                if(is_callable($value) && $key == 'callback'){
                    $callback = $value;
                    unset($data[$key]);
                }
            }
            if(!isset($data['name'])){
                $data['name'] = $uri;
            }
        }else{
            $data = [
                'name' => $uri
            ];
        }

        //create route array
        $route = [
            'methods' => (array) $method,
            'uri' => $uri,
            'callback' => $callback,
            'data' => $data
        ];

        //get segments
        $segments = array_filter(explode('/', $route['uri']));
        $route['segments'] = $segments;

        //get possible segment count
        $segmentCount = $this->getPossibleSegments($route['segments']);

        //add to map
        foreach($segmentCount as $count){
            $this->segmentsMap[$count][$route['uri']] = $route['uri'];
        }

        //save route against uri (must be an array as you could define same route with different methods
        $this->routes[$route['uri']] = (isset($this->routes[$route['uri']])) ? $this->routes[$route['uri']] : [];

        $this->routes[$route['uri']][] = $route;

        $this->names[$route['data']['name']] = $route['uri'];

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

    public function options($uri, $callback){
        return $this->match(['OPTIONS'], $uri, $callback);
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

    public function dispatch($method = 'GET', $uri = '', $domain = null){

        if($domain !== null){
            $uri = rtrim($domain) . $this->slashUri($uri);
            try{
                $route = $this->dispatch($method, $uri);
                if($route instanceof Route){
                    return $route;
                }
            }catch(NotFoundException $e){
                //supress and try without domain
            }
        }

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

            //add global matchers/modifyers
            $first->addModifyers($this->modifyers);
            $first->addMatchers($this->matchers);

            if(!$first->matches($uri)){
                continue;
            }

            //store params so we dont have to parse them more than once
            $params = $first->getParams();

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

                //fill params
                $_route->setParams($params);

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

    public function toUrl($name = '', $params = []){

        if(array_key_exists($name, $this->names) && !empty($this->routes[$this->names[$name]])){

            $route = new Route($this->routes[$this->names[$name]][0]);
            return $route->toUrl($params);

        }

        throw new NotFoundException(sprintf('The route name requested: %s cannot be found', $name));
    }

}