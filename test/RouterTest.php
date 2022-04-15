<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DummyController
{
    static public bool $dummyMethod1Called = false;
    static public bool $dummyMethod2Called = false;
    static public bool $dummyMethod3Called = false;

    static public function reset(): void
    {
        self::$dummyMethod1Called = false;
        self::$dummyMethod2Called = false;
        self::$dummyMethod3Called = false;
    }

    #[Route(method: 'GET', pattern: '/^\/dummy\/?$/')]
    public function dummyMethod1(): void
    {
        self::$dummyMethod1Called = true;
    }

    #[Route(method: 'POST', pattern: '/^\/dummy\/?$/')]
    public function dummyMethod2(): void
    {
        self::$dummyMethod2Called = true;
    }

    #[Route(method: 'POST', pattern: '/^\/dummy3\/?$/')]
    public function dummyMethod3(): void
    {
        self::$dummyMethod3Called = true;
    }
}

final class RouterTest extends TestCase
{
    public function testRoutesMatchedByMethod(): void
    {
        $router = new Colossal\Router\Router;
        
        $pattern = '/^\/test\/?$/';

        $router->addRoute('GET',  $pattern, function() use(&$getMatched)  { $getMatched  = true; });
        $router->addRoute('POST', $pattern, function() use(&$postMatched) { $postMatched = true; });

        $_SERVER['REQUEST_URI'] = '/test';

        // Test that only route with GET method is matched
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $getMatched  = false;
        $postMatched = false;
        $router->run();
        $this->assertTrue($getMatched);
        $this->assertFalse($postMatched);

        // Test that only route with POST method is matched
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $getMatched  = false;
        $postMatched = false;
        $router->run();
        $this->assertFalse($getMatched);
        $this->assertTrue($postMatched);
    }

    public function testRoutesMatchedByPattern(): void
    {
        $router = new Colossal\Router\Router;

        $router->addRoute('GET', '/^\/pattern1\/?$/', function() use(&$pattern1Matched) { $pattern1Matched = true; });
        $router->addRoute('GET', '/^\/pattern2\/?$/', function() use(&$pattern2Matched) { $pattern2Matched = true; });

        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Test that only route corresponding to pattern 1 is matched
        $_SERVER['REQUEST_URI'] = '/pattern1';
        $pattern1Matched = false;
        $pattern2Matched = false;
        $router->run();
        $this->assertTrue($pattern1Matched);
        $this->assertFalse($pattern2Matched);

        // Test that only route corresponding to pattern 2 is matched
        $_SERVER['REQUEST_URI'] = '/pattern2';
        $pattern1Matched = false;
        $pattern2Matched = false;
        $router->run();
        $this->assertFalse($pattern1Matched);
        $this->assertTrue($pattern2Matched);
    }

    public function testHandle404(): void
    {
        $router = new Colossal\Router\Router;

        $_SERVER['REQUEST_METHOD']  = 'GET';
        $_SERVER['REQUEST_URI']     = '/non-existant-pattern';

        // Test default 404 handler is called
        $this->expectException(\Error::class);
        $router->run();

        $router->set404(function(string $method, string $url) use(&$handlerCalled) { $handlerCalled = true; });

        // Test that a user defined 404 handler is called
        $handlerCalled = false;
        $router->run();
        $this->assertTrue($handlerCalled);
    }

    public function testRegisteringRoutesViaClassReflection(): void
    {
        $router = new Colossal\Router\Router;

        $router->addController(DummyController::class);

        // Test that dummyMethod1 is registered
        $_SERVER['REQUEST_METHOD']  = 'GET';
        $_SERVER['REQUEST_URI']     = '/dummy/';
        DummyController::reset();
        $router->run();
        $this->assertTrue(DummyController::$dummyMethod1Called);

        // Test that dummyMethod2 is registered
        $_SERVER['REQUEST_METHOD']  = 'POST';
        $_SERVER['REQUEST_URI']     = '/dummy/';
        DummyController::reset();
        $router->run();
        $this->assertTrue(DummyController::$dummyMethod2Called);

        // Test that dummyMethod3 is registered
        $_SERVER['REQUEST_METHOD']  = 'POST';
        $_SERVER['REQUEST_URI']     = '/dummy3/';
        DummyController::reset();
        $router->run();
        $this->assertTrue(DummyController::$dummyMethod3Called);
    }

    public function testRouteParametersPassedToHandler(): void
    {
        $router = new Colossal\Router\Router;

        $aOK = false;
        $aExpected = 'param-a';
        $bOK = false;
        $bExpected = 'param-b';

        $router->addRoute('GET', '/^\/test\/(?<a>[a-zA-Z-]+)\/(?<b>[a-zA-Z-]+)\/?$/', function($routeParameters) use(&$aOK, $aExpected, &$bOK, $bExpected) {
            $aOK = (array_key_exists('a', $routeParameters) && $routeParameters['a'] == $aExpected);
            $bOK = (array_key_exists('b', $routeParameters) && $routeParameters['b'] == $bExpected);
        });

        $_SERVER['REQUEST_METHOD']  = 'GET';
        $_SERVER['REQUEST_URI']     = '/test/param-a/param-b/';
        $router->run();
        $this->assertTrue($aOK && $bOK);
    }
}