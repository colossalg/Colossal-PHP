# Colossal PHP Framework

Colossal is a micro framework for websites written in PHP 8.

The following components are supported:

- Routing

## Routing

Colossal contains a minimal router for HTTP requests.

### Route Patterns

Route patterns are based upon PCRE regular expressions. These make the route
matching simple and powerful but somewhat less elegant than the patterns
implemented in other routers.

#### Examples

- /home or /home/                => `'/^\/home\/?$/'`
- /pages/about or /pages/about/  => `'/^\/pages\/about\/?$/'`

### Route Parameters

Often you will want to extract data from the URL to pass as a parameter to the
handler of the route. As the route patterns are based upon PCRE regular
expressions this can be done using capture groups.

Route parameters are captured as an associative array that is then passed to the
route handlers.

#### Examples

- /users/<user-id> or /users/<user-id>/   => `'/^\/users\/(?<id>[0-9]+)\/?$/'`

### Route Handlers

Route handlers are called when the request method and URL match the method and
pattern of the route. These should take either no parameters or a single
parameter for the route parameters (an associative array).

#### Examples

```
$router = new Colossal\Router\Router;

// Route handler not using any route parameters
$router->addRoute('GET', '/^\/home\/?$/', function() { echo 'Home'; });

// Route handler using route parameters
$router->addRoute('GET', '/^\/users\/(?<id>[0-9]+)\/?$/', function(array $routeParameters) {
    echo $routeParameters['id'];
});

$router->run();
```

### Controllers

Specifying routes is also possible via registering classes as controllers.
Internally, the reflection API is used to find all methods marked with the
attribute:

#[Route(method: '<http-method>', pattern: '<pcre-pattern>')]

These methods are then registered as a route via the addRoute method where:

- <http-method>  (string) Is the HTTP method of the route.
- <pcre-pattern> (string) Is the PCRE pattern of the route.
- The method itself is wrapped in a Closure and used as the handler of the route.

#### Example

```
final class DummyController
{
    #[Route(method: 'GET', pattern: '/^\/dummy\/?$/')]
    public function dummyMethod1(): void
    {
        echo 'dummy';
    }
}

$router = new Colossal\Router\Router;

$router->addController(DummyController::class);

$router->run();
```

### 404 Handling

The router has a default 404 error handler that simply throws an Error when no
route matches the request method and URL. It is also possible to set the 404
error handler. 404 error handlers should take two parameters $method (string)
and $url (string);

#### Example

```
$router = new Colossal\Router\Router;

$router->set404(function(string $method, string $url) {
    echo "404 handler called with method: $method and url: $url.";
});

$router->run();
```