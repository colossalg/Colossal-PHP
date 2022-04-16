<?php declare(strict_types=1);

final class UserModel extends Colossal\ORM\Model
{
    const TABLE = 'users';

    #[Field(column: 'id', type: 'int', key: true)]
    public int $id;

    #[Field(column: 'first_name', type: 'string')]
    public string $firstName;

    #[Field(column: 'last_name',  type: 'string')]
    public string $lastName;

    #[Field(column: 'email', type: 'string')]
    public string $email;
}