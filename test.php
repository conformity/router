<?php

require __DIR__ . '/vendor/autoload.php';

$router = new \Conformity\Router\CachedRouter(__DIR__ . '/cache');

$router->addMatcher('numeric', 'is_numeric');

$router->match('GET', '/test', 'handler')->withData(['param1' => 'value']);

$router->get('/gettest', 'handler')->withData(['param1' => 'value']);

$router->post('posttest', 'handler')->withData(['param1' => 'value']);

$router->put('/puttest', 'handler')->withData(['param1' => 'value']);

$router->patch('/patchtest', 'handler')->withData(['param1' => 'value']);

$router->delete('/deletetest', 'handler')->withData(['param1' => 'value']);

$router->any('/anytest', 'handler')->withData(['param1' => 'value']);


$router->get('/multiple/segments/test', 'handler')->withData(['param1' => 'value']);

$router->get('/multiple/{inmiddle}/test/{optional}/{segments?}', 'handler')
    ->withData(['param1' => 'value']);

$router->post('/multiple/segments/test/{optional?}', 'is_numeric')->withData(['param1' => 'value']);

//print_r($router->dispatch('GET', '/gettest'));
print_r($router->dispatch('GET', '/multiple/segments/test/10/'));