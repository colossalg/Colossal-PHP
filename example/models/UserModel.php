<?php declare(strict_types=1);

final class UserModel extends Colossal\ORM\Model
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