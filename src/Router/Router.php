<?php declare(strict_types=1);

namespace Colossal\Router;

require_once __DIR__ . '/Route.php';

class Router
{
    private array       $routes;
    private \Closure    $handler404;    // This should take two parameters $method (string) and $url (string).

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->routes       = [];
        $this->handler404   = function(string $method, string $url) {
            throw new \Error("Route with pattern matching method: $method, or url: $url does not exist, request unhandled.");
        };
    }

    /**
     * Add a route to the router.
     * @param $method  (string) The HTTP method of the route.
     * @param $pattern (string) The PCRE pattern of the route.
     * @param $handler (Closure) The handler of the route, this should take a single parameter $routeParameters (array).
     */
    public function addRoute(string $method, string $pattern, \Closure $handler): void
    {
        foreach ($this->routes as $route) {
            if ($route->getMethod() === $method && $route->getPattern() == $pattern) {
                throw new \Error("Route with method: $method, and pattern: $pattern already exists, can not insert route.");
            }
        }

        array_push($this->routes, new Route($method, $pattern, $handler));
    }

    /**
     * Add a controller to the router using the reflection API.
     * 
     * All methods marked with the attribute #[Route(method: '<http-method>', pattern: '<pcre-pattern>')]
     * will be registered as individual routes (via addRoute) where:
     *  - <http-method>  (string) Is the HTTP method of the route.
     *  - <pcre-pattern> (string) Is the PCRE pattern of the route.
     *  - The method will be wrapped in a Closure as the handler of the route,
     *    this should take a single parameter $routeParameters (array).
     * 
     * @param $controllerClassName (string) The name of the controller class to register.
     */
    public function addController(string $controllerClassName): void
    {
        $reflectionClass = new \ReflectionClass($controllerClassName);  
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $controllerMethodName = $reflectionMethod->getName();
            foreach ($reflectionMethod->getAttributes('Route') as $routeAttribute) {
                $routeMethod  = $routeAttribute->getArguments()['method'];
                $routePattern = $routeAttribute->getArguments()['pattern'];
                $routeHandler = function (array $routeParameters) use ($controllerClassName, $controllerMethodName) {
                    (new $controllerClassName)->$controllerMethodName($routeParameters);
                };
                $this->addRoute($routeMethod, $routePattern, $routeHandler);
            }
        }
    }

    /**
     * Set the 404 error handler that is called when the request method/URL does not match the method/pattern of any routes.
     * @param $handler404 (Closure) The 404 error handler, this should take two parameters, $method (string) and $url (string).
     */
    public function set404(\Closure $handler404): void
    {
        $this->handler404 = $handler404;
    }

    /**
     * Run the router.
     * 
     * This will do the following:
     *  - Try to find a route whose method/pattern match $_SERVER['REQUEST_METHOD'] and $_SERVER['REQUEST_URI'].
     *  - Extract the route parameters from $_SERVER['REQUEST_URI'] using capture groups in the route's pattern.
     *  - Call the route's handler passing the route parameters as an associative array.
     *  - If no matching route was found the 404 error handler is called.
     */
    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $url    = $_SERVER['REQUEST_URI'];
        foreach ($this->routes as $route) {
            $routeParameters = [];
            if ($route->matches($method, $url, $routeParameters)) {
                $route->handle($routeParameters);
                return;
            }
        }

        ($this->handler404)($method, $url);
    }
}