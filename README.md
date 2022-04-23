# Colossal PHP Framework

Colossal is a micro framework for websites written in PHP 8.

The following components are supported:

- Routing
- ORM

## Basic Routing

The easiest way to explain the features supported by the router is using some
code examples. Further examples can also be found in the example app.

```php

// Creating the router is very simple.
$router = Colossal\Router\Router;

// -------------------------------------------------------------------------- //
// We can directly register routes to the router. To do so we must specify    //
// the HTTP method, a PCRE regex pattern and the handler for the route.       //
// -------------------------------------------------------------------------- //

// This route will match any GET requests to /index or /index/
$router->addRoute('GET',  '/^\/index\/?$/', function() { echo 'GET index'; });

// This route will match any POST requests to /queue or /queue/
$router->addRoute('POST', '/^\/queue\/?$/', function() { echo 'POST queue'; });

// -------------------------------------------------------------------------- //
// We can also use capture groups in the PCRE patterns to extract variables   //
// from the URL. These will placed in an associative array and passed to the  //
// route handler. Route handlers should either specify no parameters if they  //
// don't use any route parameters or just one parameter for this associative  //
// array.                                                                     //
// -------------------------------------------------------------------------- //

// This route will match any GET requests to /users/<id> or /users/<id>/
// <id> will be placed in an associative array and passed to the route handler.
$router->addRoute('GET',  '/^\/users\/(?<id>[0-9]+)\/?$/', function(array $routeParameters) {
    echo "GET User id = $routeParameters[id]";
});

// This route will match any POST requests to /users/<id> or /users/<id>/
// <id> will be placed in an associative array and passed to the route handler.
$router->addRoute('POST', '/^\/users\/(?<id>[0-9]+)\/?$/', function(array $routeParameters) {
    echo "POST User id = $routeParameters[id]";
});

// -------------------------------------------------------------------------- //
// Router instances have a default handler for 404 errors. The default        //
// behaviour is to throw an Error. A custom 404 handler can also be set to    //
// override this behaviour. Handlers should take two parameters $method and   //
// $url both of type string.                                                  //
// -------------------------------------------------------------------------- //

// This will set a custom 404 error handler which will be called if the request
// method/URL does not match the method/pattern of any registered route.
$router->set404(function(string $method, string $url) {
    echo "Could not locate resource. Method: $method, URL: $url.";
});

// We need to call run() to do the actual routing.
// The method and URL are automatically sourced from:
// - $_SERVER['REQUEST_METHOD']
// - $_SERVER['REQUEST_URI']
$router->run();
```

## Reflection Based Routing

The router also supports registering routes using method attributes.

```php

// -------------------------------------------------------------------------- //
// Behind the scenes what happens is that the router will examine all of the  //
// methods which have the attribute:                                          //
// #[Route(method: <http-method>, pattern: <pcre-pattern>)]                   //
// This will then be converted in to a normal route where:                    //
// - The route method is <http-method>.                                       //
// - The route pattern is <pcre-pattern>.                                     //
// - The route handler is a closure that:                                     //
//      * Creates an instance of the controller class.                        //
//      * Calls the method with any route parameters from capture groups.     //
// -------------------------------------------------------------------------- //

final class UserController
{
    // This route will match any GET requests to /users/<id> or /users/<id>/
    // <id> will be placed in an associative array and passed to the method.
    #[Route(method: 'GET',  pattern: '/^\/users\/(?<id>[0-9]+)\/?$/')]
    public function getUser(array $routeParameters)
    {
        echo "GET User id = $routeParameters[id]";
    }

    // This route will match any POST requests to /users/<id> or /users/<id>/
    // <id> will be placed in an associative array and passed to the method.
    #[Route(method: 'POST', pattern: '/^\/users\/(?<id>[0-9]+)\/?$/')]
    public function postUser(array $routeParameters)
    {
        echo "POST User id = $routeParameters[id]";
    }
}

$router = Colossal\Router\Router;

// Register the controller. All of the reflection magic happens behind the scenes.
$router->addController(UserController::class);

$router->run();

```

## ORM

A VERY simple ORM is supported that provides a convenient way to specify user
models using property attributes.

```php

// -------------------------------------------------------------------------- //
// To define a model we do the following:                                     //
// - Subclass Colossal\ORM\Model.                                             //
// - The subclass must have a const TABLE for the name of the database table. //
// - Define the database column / class property pairs using attributes.      //
//   #[Field(column: <column-name>, ?key: true)]                              //
//   - <column-name> (string) Is the name of the database column that the property maps to.
//   - The key property is optional and if provided should always be given a value of true.
// -------------------------------------------------------------------------- //

// Our database has a table called users with columns:
// id         - number (primary key)
// first_name - varchar
// last_name  - varchar
// email      - varchar

class UserModel extends Colossal\ORM\Model
{
    const TABLE = 'users';

    #[Field(column: 'id', key: true)]
    public int $id;

    #[Field(column: 'first_name')]
    public string $firstName;

    #[Field(column: 'last_name')]
    public string $lastName;

    #[Field(column: 'email')]
    public string $email;
}

// -------------------------------------------------------------------------- //
// The model then supports the following operations:                          //
// - Load                                                                     //
// - Insert                                                                   //
// - Update                                                                   //
// - Delete                                                                   //
//                                                                            //
// In each of these operations the database column / class property (defined  //
// as explained above using attributes) are used to map values between the    //
// database and model.                                                        //
// -------------------------------------------------------------------------- //

// Assume we have an entry with values:
// id         - 1
// first_name - John
// last_name  - Doe
// email      - johndoe@gmail.com

// After loading the properties will have the values corresponding to the entry
// in the database.
// $model->id         === 1
// $model->first_name === 'John'
// $model->last_name  === 'Doe'
// $model->email      === 'johndoe@gmail.com'
$model = new UserModel;
$model->load(1);

// If we change the properties then a subsequent call to update will change the
// corresponding entry in the database.
// id         - 1
// first_name - 'Mary'
// last_name  - 'Poppins'
// lemail     - 'mpoppins@outlook.com'
$model->firstName = 'Mary';
$model->lastName  = 'Poppins';
$model->email     = 'mpoppins@outlook.com'
$model->update();

// We can insert a new entry in to the database by creating a model, setting its
// properties (except for the primary key) then calling insert.
// id         - 2 (via auto increment)
// first_name - 'Mr.'
// last_name  - 'Bean'
// email      - 'bean@gmail.com'
$model = new UserModel;
$model->firstName = 'Mr.';
$model->lastName  = 'Bean';
$model->email     = 'bean@gmail.com';
$model->insert();

// We can delete an existing entry from the database as well.
// Mr. Bean will now be gone :(
$model->delete();
```