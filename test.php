<?php

require __DIR__ . '/vendor/autoload.php';

$router = new \Conformity\Router\Router();

$router->addMatcher('numeric', function($value, $min, $max){
    return false;
});

$router->match('GET', '/test', function(){return 'test';})->with(['param1' => 'value']);

$router->get('/gettest', function(){return 'gettest';})->with(['param1' => 'value']);

$router->post('posttest', function(){return 'posttest';})->with(['param1' => 'value']);

$router->put('/puttest', function(){return 'puttest';})->with(['param1' => 'value']);

$router->patch('/patchtest', function(){return 'patchtest';})->with(['param1' => 'value']);

$router->delete('/deletetest', function(){return 'deletetest';})->with(['param1' => 'value']);

$router->any('/anytest', function(){return 'anytest';})->with(['param1' => 'value']);


$router->get('/multiple/segments/test', function(){return 'segmenttest';})->with(['param1' => 'value']);

$router->get('/multiple/{inmiddle}/test/{optional|numeric:0,11?}/{segments?}', function(){return 'segmenttest';})
    ->withData(['param1' => 'value'])
    ->addMatcher('numeric', function($value, $min, $max){
        return is_numeric($value) && $value >= $min && $value <= $max;
    })
    ->addModifyer('inmiddle', function($value){
        $obj = new stdClass();
        $obj->value = $value;
        return $obj;
    });

$router->post('/multiple/segments/test/{optional?}', function(){return 'segmenttest';})->with(['param1' => 'value']);

print_r($router->dispatch('GET', '/multiple/segments/test/10/'));