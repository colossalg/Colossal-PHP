<?php declare(strict_types=1);

final class UserController
{
    #[Route(method: 'GET',  pattern: '/^\/users\/(?<id>[0-9]+)\/?$/')]
    public function getUser($routeParameters)
    {
        echo "getUser($routeParameters[id])";
    }

    #[Route(method: 'POST', pattern: '/^\/users\/(?<id>[0-9]+)\/?$/')]
    public function postUser($routeParameters)
    {
        echo "postUser($routeParameters[id])";
    }
}