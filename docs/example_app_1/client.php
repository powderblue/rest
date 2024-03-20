<?php

// phpcs:ignoreFile

namespace Doctrine\REST;

require_once __DIR__ . '/../../vendor/autoload.php';

//Point this at `server.php` on your machine
define('TEST_SERVER_URL', 'http://localhost/rest/server.php');

class User extends Client\Entity
{
    public $id;

    public $username;

    public $password;

    public static function configure(Client\EntityConfiguration $entityConfiguration)
    {
        $entityConfiguration->setUrl(TEST_SERVER_URL);
        $entityConfiguration->setName('user');
    }
}

//Register an entity for working with a remote data repository:

$manager = new Client\Manager(new Client\Client(), new Client\ResponseCache(sys_get_temp_dir()));
$manager->registerEntity(__NAMESPACE__ . '\User');

Client\Entity::setManager($manager);

//Mutate the contents of the remote data repository using the entity class:

print '<pre>';

$user = User::find(2);
print_r($user);

$user->password = 'newpassword';
$user->save();
print_r($user);
