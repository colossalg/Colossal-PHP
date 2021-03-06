<?php declare(strict_types=1);

require_once __DIR__ . '/../models/UserModel.php';

final class UserController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel;
    }

    #[Route(method: 'GET',  pattern: '/^\/users\/(?<id>[0-9]+)\/?$/')]
    public function getUser($routeParameters): void
    {
        $this->userModel->load((int)$routeParameters['id']);

        $json = [];
        $json['id']         = $this->userModel->id;
        $json['firstName']  = $this->userModel->firstName;
        $json['lastName']   = $this->userModel->lastName;
        $json['email']      = $this->userModel->email;

        echo json_encode($json);
    }

    #[Route(method: 'POST', pattern: '/^\/users\/?$/')]
    public function postUser(): void
    {
        $this->userModel = new UserModel;

        $json = json_decode(file_get_contents('php://input'));

        $this->userModel->firstName = $json->firstName;
        $this->userModel->lastName  = $json->lastName;
        $this->userModel->email     = $json->email;

        $this->userModel->insert();
    }

    #[Route(method: 'PUT', pattern: '/^\/users\/(?<id>[0-9]+)\/?$/')]
    public function putUser($routeParameters): void
    {
        $this->userModel->load((int)$routeParameters['id']);

        $json = json_decode(file_get_contents('php://input'));

        $this->userModel->firstName = $json->firstName;
        $this->userModel->lastName  = $json->lastName;
        $this->userModel->email     = $json->email;

        $this->userModel->update();
    }

    #[Route(method: 'DELETE', pattern: '/^\/users\/(?<id>[0-9]+)\/?$/')]
    public function deleteUser($routeParameters): void
    {
        $this->userModel->load((int)$routeParameters['id']);
        $this->userModel->delete();
    }
}