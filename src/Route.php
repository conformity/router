<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 24/11/15
 * Time: 08:06
 */

namespace Conformity\Router;


class Route
{
    protected $methods = [];

    protected $uri;

    protected $segments = [];

    protected $callback;

    protected $params = [];

    protected $data = [];

    protected $matchers = [];

    protected $modifyers = [];

    public function __construct($methods, $uri, $callback){
        $this->methods = (array) $methods;
        $this->uri = $this->prependSlash($uri);
        $this->segments = array_filter(explode('/', $this->uri));
        $this->callback = $callback;
    }

    private function prependSlash($uri){
        if(strpos($uri, '/') !== 0){
            return '/' . $uri;
        }
        return $uri;
    }

    public function getPossibleSegmentsCount(){
        $segmentCount = count($this->segments);
        $optionals = $segmentCount;
        foreach($this->segments as $segment){
            if(strpos($segment, '?') !== false){
                $optionals--;
            }
        }
        return range($optionals, $segmentCount);
    }

    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return array
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function withData($data){
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function addMatchers($matchers){
        $this->matchers = array_merge($matchers, $this->matchers);
        return $this;
    }

    public function addModifyers($modifyers){
        $this->modifyers = array_merge($modifyers, $this->modifyers);
        return $this;
    }

    public function where($name, $callback){
        return $this->addMatcher($name, $callback);
    }

    public function addMatcher($name, $callback){
        $this->matchers[$name] = $callback;
        return $this;
    }

    public function addModifyer($name, $callback){
        $this->modifyers[$name] = $callback;
        return $this;
    }

    public function matches($uri){

        $segments = array_filter(explode('/', $uri));

        foreach($segments as $index => $segment){

            //if its a parameter, validate it and set value
            if(strpos($this->segments[$index], '{') === 0){

                //validate and save the segment against the route defintion, if false this route hasn't matched!
                if(!$segmentData = $this->validateSegment($this->segments[$index], $segment)){
                    return false;
                }

                $this->params[$segmentData['key']] = $this->modifySegment($segmentData);

                continue;
            }

            //if we get here its not a parameter, so we can just do an equals check
            if($segment !== $this->segments[$index]){

                return false;

            }
        }

        //if we get here all checks have passed
        return true;
    }

    private function validateSegment($segmentDefinition, $suppliedSegment){

        //trim the curly braces, we dont need them here
        $key = substr($segmentDefinition, 1, -1);

        //if its optional remove the ?
        if(strpos($key, '?') !== false){
            $key = substr($key, 0, -1);
        }

        //if it has options, lets pull them off for use
        if(strpos($key, '|') !== false){

            $parts = explode('|', $key);

            $key = array_shift($parts);

            //validate here
            if(!empty($parts)){
                foreach($parts as $part){

                    $part = explode(':', $part);

                    $matcher = array_shift($part);

                    if(!empty($part)){
                        $options = explode(',', $suppliedSegment.','.$part[0]);
                    }else{
                        $options = [$suppliedSegment];
                    }

                    if(!array_key_exists($matcher, $this->matchers)){
                        throw new \InvalidArgumentException(sprintf('The route requested %s pattern matcher, but no definition was supplied.', $matcher));
                    }

                    $matcher = $this->matchers[$matcher];

                    if(false === call_user_func_array($matcher, $options)){
                        return false;
                    }
                }
            }
        }

        //its just a plain old parameter, send it back
        return ['key' => $key, 'value' => $suppliedSegment];
    }


    private function modifySegment($data){

        if(array_key_exists($data['key'], $this->modifyers)){
            $data['value'] = call_user_func_array($this->modifyers[$data['key']], [$data['value']]);
        }

        return $data['value'];
    }

}