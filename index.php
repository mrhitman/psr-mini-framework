<?php

require_once __DIR__ . "/vendor/autoload.php";

use components\App;
use Dotenv\Dotenv;
use Psr\Http\Message\ResponseInterface;


(new Dotenv(__DIR__))->load();

$app = new App();

$app->use(function ($request, ResponseInterface $response, $next) use ($app) {
    $app->log->info("middleware");
    $next();
});

$app->get('/', function ($request, ResponseInterface $response) {
    $response->getBody()->write(<<<HTML
    <html>
      <head>
      </head>
      <body>
        example
      </body>
    </html>
HTML
);
});

$app->get('/test/{id:\d+}', function ($request, ResponseInterface $response, array $args) {
    [$id] = $args;
    $response->getBody()->write("ggasdsaasd $id");
});

$app->run();
