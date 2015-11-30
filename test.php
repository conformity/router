<?php

require __DIR__ . '/vendor/autoload.php';

$router = new \Conformity\Router\LearnableCachedRouter(new \Conformity\Router\LearnableArrayCache());

$router->addMatcher('numeric', 'is_numeric');

$router->match('GET', '/test', 'handler');

$router->match('GET', 'testing', 'handler');


$router->get('/gettest', 'handler');

$router->post('posttest', 'handler');

$router->put('/puttest', 'handler');

$router->patch('/patchtest', 'handler');

$router->delete('/deletetest', 'handler');

$router->any('/anytest', 'handler');


$router->get('/multiple/segments/test', 'handler');

$router->get('/multiple/segments/test/{optional?}', 'is_numeric');

$router->get('http://{subdomain|numeric}.test.com/multiple/segments/test/{optional?}', 'is_numeric');

$router->get('/multiple/{inmiddle}/test/{optional}/{segments?}', 'handler');


$router->get('/a/test/{inmiddle}/optional-{name}.{extension}', 'handler');


//print_r($router);

//exit();
print_r($router->dispatch('GET', '/multiple/segments/test'));
print_r($router->dispatch('GET', '/multiple/segments/test/var'));
print_r($router->dispatch('GET', '/a/test/with/optional-lee.json'));
print_r($router->dispatch('GET', 'http://2.test.com/multiple/segments/test/hey'));
//print_r($router->dispatch('GET', '/multiple/segments/test/10/'));