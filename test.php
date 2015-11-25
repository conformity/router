<?php

require __DIR__ . '/vendor/autoload.php';

$router = new \Conformity\Router\LearnableCachedRouter(new \Conformity\Router\LearnableArrayCache());

$router->addMatcher('numeric', 'is_numeric');

$router->match('GET', '/test', 'handler');

$router->get('/gettest', 'handler');

$router->post('posttest', 'handler');

$router->put('/puttest', 'handler');

$router->patch('/patchtest', 'handler');

$router->delete('/deletetest', 'handler');

$router->any('/anytest', 'handler');


$router->get('/multiple/segments/test', 'handler');

$router->get('/multiple/{inmiddle}/test/{optional}/{segments?}', 'handler');

$router->post('/multiple/segments/test/{optional?}', 'is_numeric');

//print_r($router->dispatch('GET', '/gettest'));
print_r($router->dispatch('GET', '/multiple/segments/test/10/'));