<?php

namespace components;


use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slince\Di\Container;
use Zend\Diactoros\Response;
use Zend\Diactoros\Server;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Class App
 * @property Logger $log
 * @property EntityManager $entityManager
 * @package components
 */
class App
{

    protected $routes = [];
    protected $tip;
    public $container;

    public function __construct()
    {
        $this->container = new Container();
    }

    public function __get($name)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }
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

    public function use(callable $middleware)
    {
        $current = $this->tip;
        $this->tip = function ($request, $response) use ($middleware, $current) {
            $next = function () use ($request, $reponse, $current) {
                if ($current) {
                    call_user_func($current, $request, $response);
                }
            };
            call_user_func($middleware, $request, $response, $next);
        };
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
                    $response->getBody()->write($this->twig->render("404.php"));
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
}
