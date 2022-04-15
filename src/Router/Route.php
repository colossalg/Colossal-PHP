<?php declare(strict_types=1);

namespace Colossal\Router;

class Route {

    public function __construct(
        private string      $method,
        private string      $pattern,
        private \Closure    $handler,
    )
    {}

    public function getMethod():  string    { return $this->method; }
    public function getPattern(): string    { return $this->pattern; }
    public function getHandler(): \Closure  { return $this->handler; }

    /**
     * Check whether a given http method/URL of a request matches the method/pattern of this route.
     * @param $method (string) The HTTP method of the request.
     * @param $url    (string) The URL of the request.
     * @param $routeParameters (array&) The route parameters extracted from the URL using the capture groups in this route's pattern.
     * @return bool Whether the http method/URL of the request matches the method/pattern of this route.
     */
    public function matches(string $method, string $url, array &$routeParameters): bool
    {
        $result = preg_match($this->pattern, $url, $routeParameters);
        if ($result === false) {
            throw new \Error("An error occurred while trying to match pattern: $this->pattern and url: $url.");
        }

        return ($this->method === $method && $result);
    }

    /**
     * Call the handler for this route.
     * @param $routeParameters (array) The route parameters extracted from the request URL.
     */
    public function handle(array $routeParameters): void
    {
        ($this->handler)($routeParameters);
    }
}