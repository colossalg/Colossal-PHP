<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

Colossal\ORM\Model::connect('mysql:host=localhost;dbname=test', 'root', 'root');

$router = new Colossal\Router\Router;

$router->addRoute('GET', '/^\/index\/?$/', function() { require_once __DIR__ . '/views/index.php'; });
$router->addRoute('GET', '/^\/about\/?$/', function() { require_once __DIR__ . '/views/about.php'; });

require_once __DIR__ . '/controllers/UserController.php';
$router->addController(UserController::class);

$router->set404(function(string $method, string $url) { require_once __DIR__ . '/views/404.php'; });

$router->run();