<?php

require __DIR__ . '/vendor/autoload.php';

$router = new \Conformity\Router\CachedRouter(__DIR__ . '/cache');

$router->addMatcher('numeric', function($value, $min, $max){
    return false;
});

$router->match('GET', '/test', function(){return 'test';})->withData(['param1' => 'value']);

$router->get('/gettest', function(){return 'gettest';})->withData(['param1' => 'value']);

$router->post('posttest', function(){return 'posttest';})->withData(['param1' => 'value']);

$router->put('/puttest', function(){return 'puttest';})->withData(['param1' => 'value']);

$router->patch('/patchtest', function(){return 'patchtest';})->withData(['param1' => 'value']);

$router->delete('/deletetest', function(){return 'deletetest';})->withData(['param1' => 'value']);

$router->any('/anytest', function(){return 'anytest';})->withData(['param1' => 'value']);


$router->get('/multiple/segments/test', function(){return 'segmenttest';})->withData(['param1' => 'value']);

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

$router->post('/multiple/segments/test/{optional?}', function(){return 'segmenttest';})->withData(['param1' => 'value']);

//print_r($router->dispatch('GET', '/gettest'));
print_r($router->dispatch('GET', '/multiple/segments/test/10/'));