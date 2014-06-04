<?php

use Doctrine\REST\Client\Client;
use Doctrine\REST\Client\EntityConfiguration;
use Doctrine\REST\Client\Manager;
use Doctrine\REST\Client\Entity;

require_once __DIR__ . '/../../vendor/autoload.php';

//Point this at `server.php` on your machine
define('TEST_SERVER_URL', 'http://localhost/rest/server.php');

class User extends Entity
{
    public $id;

    public $username;

    public $password;

    public static function configure(EntityConfiguration $entityConfiguration)
    {
        $entityConfiguration->setUrl(TEST_SERVER_URL);
        $entityConfiguration->setName('user');
    }
}

//Register an entity for working with a remote data repository:

$client = new Client();

$manager = new Manager($client);
$manager->registerEntity('User');

Entity::setManager($manager);

//Mutate the contents of the remote data repository using the entity class:

print '<pre>';

$user = User::find(2);

print_r($user);

$user->password = 'newpassword';
$user->save();

print_r($user);
