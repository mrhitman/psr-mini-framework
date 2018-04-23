<?php

require_once __DIR__ . "/vendor/autoload.php";

use components\App;
use Psr\Http\Message\ResponseInterface;


$app = new App();

$app->add(function ($request, ResponseInterface $response, $next) {
    $next($request, $response);
});

$app->get('/', function ($request, ResponseInterface $response) {
    $response->getBody()->write("ggasd");
});

$app->get('/test/{id:\d+}', function ($request, ResponseInterface $response, array $args) {
    [$id] = $args;
    $response->getBody()->write("ggasdsaasd $id");
});

$app->run();
