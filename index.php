<?php

require_once __DIR__ . "/vendor/autoload.php";

use components\App;
use Dotenv\Dotenv;
use Psr\Http\Message\ResponseInterface;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

(new Dotenv(__DIR__))->load();

$config = [
    'log' => [
        'class' => Logger::class,
        'name' => 'main',
    ],
    'twig' => [
        'class' => Twig_Environment::class,
        'loader' => new Twig_Loader_Filesystem('views'),
        ['cache' => 'runtime/'],
    ],
];

$app = new App($config);
$app->use(function ($request, ResponseInterface $response, $next) use ($app) {
    $app->log->info("middleware");
    $next();
});

$app->get('/', function ($request, ResponseInterface $response) use ($app) {
    $content = $app->twig->render("index.php", [
      "title" => "Index page",
      "content" => "Content"
    ]);
    $response->getBody()->write($content);
});

$app->get('/test/{id:\d+}', function ($request, ResponseInterface $response, array $args) {
    [$id] = $args;
    $response->getBody()->write("ggasdsaasd $id");
});

$app->run();
