<?php

namespace components;


use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slince\Di\Container;
use Zend\Diactoros\Response;
use Zend\Diactoros\Server;
use Zend\Diactoros\ServerRequestFactory;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class App
{

    protected $routes = [];
    protected $tip;
    protected $container;

    public function __construct()
    {
        (new \Dotenv\Dotenv(__DIR__ . '/..'))->load();
        $config = Setup::createAnnotationMetadataConfiguration([__DIR__ . "/entries"], true);
        $this->container = new Container();
        $this->container->bind('log', new Logger('app'));
        $this->container->bind(
            'entityManager',
            EntityManager::create([
                'driver' => 'pdo_mysql',
                'host' => getenv('DB_HOST'),
                'user' => getenv('DB_USER'),
                'password' => getenv('DB_PASSWORD'),
                'dbname' => getenv('DB_NAME'),
            ], $config)
        );
    }

    public function __get($name)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }
    }

    public function add(callable $middleware)
    {
        if (!$this->tip) {
            $this->tip = function ($request, $response) {
            };
        }
        $next = $this->tip;
        $this->tip = function ($request, $response) use ($middleware, $next) {
            call_user_func($middleware, $request, $response, $next);
        };
    }

    public function route($method, $pattern, callable $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function get($pattern, callable $handler)
    {
        $this->route('GET', $pattern, $handler);
    }

    public function post($pattern, callable $handler)
    {
        $this->route('post', $pattern, $handler);
    }

    public function run()
    {
        $request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
        $response = new Response();
        $server = new Server(function (ServerRequestInterface $request, ResponseInterface $response) {
            if ($this->tip) {
                call_user_func($this->tip, $request, $response);
            }
            $dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) {
                foreach ($this->routes as $route) {
                    $r->addRoute($route['method'], $route['pattern'], $route['handler']);
                }
            });
            [$status, $handler, $vars] = $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
            switch ($status) {
                case Dispatcher::NOT_FOUND:
                    $response->getBody()->write($this->render("views/404.php"));
                    break;
                case Dispatcher::METHOD_NOT_ALLOWED:
                    throw new \HttpException("Not allowed", 405);
                    break;
                case Dispatcher::FOUND:
                    call_user_func($handler, $request, $response, $vars);
                    break;
            }

        }, $request, $response);
        $server->listen();
    }

    protected function render($name)
    {
        ob_start();
        require_once($name);
        return ob_get_clean();
    }
}