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

    public function __construct($data){
        $this->methods = $data['methods'];
        $this->uri = $data['uri'];
        $this->segments = $data['segments'];
        $this->callback = $data['callback'];
        $this->data = $data['data'];
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
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
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

            //if its parameter(s), validate it and set value
            if(strpos($this->segments[$index], '{') !== false){

                //validate the segment against the route defintion, if false this route hasn't matched! If populated we have params
                if(!$segmentData = $this->validateSegment($this->segments[$index], $segment)){
                    return false;
                }

                //save param values
                foreach($segmentData as $param){
                    $this->params[$param['key']] = $this->modifyParam($param);
                }

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

        //if we only have 1 parameter - and its the entire segment
        if(strpos($segmentDefinition, '{') === 0 && strpos($segmentDefinition, '}') === strlen($segmentDefinition) && substr_count($segmentDefinition, '{') < 2) {

            $param = $this->validateParam(str_replace(['{', '}'], ['**', '**'], $segmentDefinition), $suppliedSegment);

            if(false === $param){
                return false;
            }

            return [$param];
        }else{

            //we have multiple parameters within this string, this is a little more difficult!
            //we need to replace the curly braces with something else so we can still identify them later on, then split the definition into an array
            $segmentDefinition = str_replace(['{', '}'], ['{**', '**}'], $segmentDefinition);
            $tempDefinitionParts = array_filter(explode('{', $segmentDefinition));
            $builtDefinitionParts = [];
            foreach($tempDefinitionParts as $index => $value){
                $subParts = array_filter(explode('}', $value));
                foreach($subParts as $part) {
                    $builtDefinitionParts[] = $part;
                }
            }

            //now we need to split the supplied segment into the same type of array, using the definition array as the template
            $suppliedParts = [];
            $suppliedTemp = $suppliedSegment;
            foreach($builtDefinitionParts as $index => $part){
                if(strpos($part, '**') !== false){
                    //if its a variable we need to find the next part and chop it off from there, or if its the last part, just use that
                    if(isset($builtDefinitionParts[$index + 1])){
                        $suppliedTemp = explode($builtDefinitionParts[$index + 1], $suppliedTemp);
                        $suppliedParts[] = array_shift($suppliedTemp);
                        $suppliedTemp = $builtDefinitionParts[$index + 1] . implode($builtDefinitionParts[$index + 1], $suppliedTemp);//make sure we put the next part back onto the start of the string to ensure the keys still line up.
                    }else{
                        //end of string
                        $suppliedParts[] = $suppliedTemp;
                    }
                }else{
                    //its just a standard string, chop it now
                    $suppliedTemp = explode($part, $suppliedTemp);
                    array_shift($suppliedTemp);
                    $suppliedParts[] = $part;
                    $suppliedTemp = implode($part, $suppliedTemp);
                }
            }

            $data = [];
            foreach($builtDefinitionParts as $index => $part){
                if(strpos($part, '**') !== false) {
                    $param = $this->validateParam($part, $suppliedParts[$index]);
                    if(!$param){
                        return false;
                    }
                    $data[] = $param;
                }
            }
            return $data;
        }
    }

    private function validateParam($expected, $supplied){
        //trim the stars, we dont need them here
        $key = substr($expected, 2, -2);

        //if its optional remove the ?
        if (strpos($key, '?') !== false) {
            $key = substr($key, 0, -1);
        }

        //if it has options, lets pull them off for use
        if (strpos($key, '|') !== false) {

            $parts = explode('|', $key);

            $key = array_shift($parts);

            //validate here
            if (!empty($parts)) {
                foreach ($parts as $part) {

                    $part = explode(':', $part);

                    $matcher = array_shift($part);

                    if (!empty($part)) {
                        $options = explode(',', $supplied . ',' . $part[0]);
                    } else {
                        $options = [$supplied];
                    }

                    if (!array_key_exists($matcher, $this->matchers)) {
                        throw new \InvalidArgumentException(sprintf('The route requested %s pattern matcher, but no definition was supplied.', $matcher));
                    }

                    $matcher = $this->matchers[$matcher];

                    if (false === call_user_func_array($matcher, $options)) {
                        return false;
                    }
                }
            }
        }

        //its just a plain old parameter, send it back
        return ['key' => $key, 'value' => $supplied];
    }


    private function modifyParam($data){

        if(array_key_exists($data['key'], $this->modifyers)){
            $data['value'] = call_user_func_array($this->modifyers[$data['key']], [$data['value']]);
        }

        return $data['value'];
    }


    public function toUrl($params = []){
        if(!empty($params)){
            $parts = array_filter(explode('{', $this->uri));
            foreach($parts as $index => $part){
                $moreParts = array_filter(explode('}', $part));

                if(strpos($moreParts[0], '|') !== false){
                    $name = array_filter(explode($moreParts[0], '|'));
                    $moreParts[0] = array_shift($name);
                }

                if(strpos($moreParts[0], '?') !== false){
                    $moreParts[0] = substr($moreParts[0], 0, -1);
                }

                if(isset($params[$moreParts[0]])){
                    $moreParts[0] = $params[$moreParts[0]];
                }

                $parts[$index] = implode($moreParts);
            }
            return str_replace('//', '/', implode('', $parts));
        }else{
            return $this->uri;
        }
    }

}