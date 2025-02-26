<?php

namespace App;

use App\Utils\{HttpException, Route};
use App\Middlewares\RoleMiddleware;

class Router {
    private array $routes = [];
    private array $controllers = [];

    public function __construct() {
        // Parse the current request URL and method
        $this->url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->method = $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Register all controllers and their routes.
     *
     * @param array $controllers List of controller classes to register
     */
    public function registerControllers(array $controllers) {
        foreach ($controllers as $controller) {
            $this->registerController($controller);
        }
    }

    private function registerController(string $controller) {
        $reflection = new \ReflectionClass($controller);
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            $attributes = $method->getAttributes(Route::class);
            foreach ($attributes as $attribute) {
                $route = $attribute->newInstance();
                $this->routes[] = [
                    'method' => $route->method,
                    'path' => $route->path,
                    'callback' => [$controller, $method->getName()],
                    'middlewares' => $route->middlewares
                ];
            }
        }
    }

    /**
     * Execute the route matching the current request.
     */
    public function run() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = $uri === '/' ? $uri : rtrim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->getPattern($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                $params = $this->extractParams($route['path'], $uri);

                try {
                    // Exécuter les middlewares
                    foreach ($route['middlewares'] as $middleware) {
                        if (is_string($middleware) && strpos($middleware, 'role:') === 0) {
                            // Middleware de rôle
                            $roles = explode(',', substr($middleware, 5));
                            $roleMiddleware = new RoleMiddleware($roles);
                            $roleMiddleware->handle();
                        } else if (is_string($middleware)) {
                            // Middleware classique
                            $middlewareInstance = new $middleware();
                            $middlewareInstance->handle();
                        } else {
                            // Middleware déjà instancié
                            $middleware->handle();
                        }
                    }

                    // Exécuter le callback
                    $callback = $route['callback'];
                    $controller = is_array($callback) ? new $callback[0]($params) : null;
                    $method = is_array($callback) ? $callback[1] : $callback;

                    $response = is_array($callback) ? $controller->$method() : $callback($params);

                    if (is_array($response) || is_object($response)) {
                        echo json_encode($response);
                    } else {
                        echo $response;
                    }

                    return;
                } catch (HttpException $e) {
                    http_response_code($e->getCode());
                    echo json_encode(['error' => $e->getMessage()]);
                    return;
                } catch (\Exception $e) {
                    http_response_code(500);
                    echo json_encode(['error' => $e->getMessage()]);
                    return;
                }
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    }

    private function getPattern($path) {
        $pattern = preg_replace('/:([a-zA-Z0-9_]+)/', '([^/]+)', $path);
        return "#^$pattern$#";
    }

    private function extractParams($path, $uri) {
        $pathParts = explode('/', trim($path, '/'));
        $uriParts = explode('/', trim($uri, '/'));
        $params = [];

        foreach ($pathParts as $index => $part) {
            if (isset($part[0]) && $part[0] === ':') {
                $paramName = substr($part, 1);
                $params[$paramName] = $uriParts[$index] ?? null;
            }
        }

        return $params;
    }

    /**
     * Check the authorization of the current request.
     *
     * @return bool True if the request is authorized, false otherwise
     */
    protected function checkAuth() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            return false;
        }

        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];
            // Verify the JWT token
            return JWT::verify($jwt);
        }

        return false;
    }
}