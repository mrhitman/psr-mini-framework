<?php

require_once __DIR__ . "/vendor/autoload.php";

use components\App;
use Dotenv\Dotenv;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

(new Dotenv(__DIR__))->load();

$app = new App();

$app->container->instance('log', new Logger('app'));
$config = Setup::createAnnotationMetadataConfiguration([__DIR__ . "/entries"], true);
$app->container->instance('entityManager', EntityManager::create([
        'driver' => 'pdo_mysql',
        'host' => getenv('DB_HOST'),
        'user' => getenv('DB_USER'),
        'password' => getenv('DB_PASSWORD'),
        'dbname' => getenv('DB_NAME'),
    ], $config)
);
$loader = new Twig_Loader_Filesystem('views');
$app->container->instance('twig', new Twig_Environment($loader, ['cache' => 'runtime/']));

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
