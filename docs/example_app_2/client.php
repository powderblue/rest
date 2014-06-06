<?php
/**
 * This example demonstrates an alternative way of configuring entity classes.  In this example, the URL of the server 
 * is 'inherited' from the manager with which the entity is associated.  Also, we let the library derive the server's 
 * name for the entity.
 */

namespace Doctrine\REST;

require_once __DIR__ . '/../../vendor/autoload.php';

//Point this at `server.php` on your machine
define('TEST_SERVER_URL', 'http://localhost/rest/server.php');

class User extends Client\Entity
{
    public $id;

    public $username;

    public $password;
}

//Register an entity for working with a remote data repository:

$manager = Client\Manager::create();

$manager->setDefaultEntityConfigurationAttributes(array(
    'url' => TEST_SERVER_URL,
));

$manager->registerEntity(__NAMESPACE__ . '\User');

//Mutate the contents of the remote data repository using the entity class:

print '<pre>';

$user = User::find(2);
print_r($user);

$user->password = 'newpassword';
$user->save();
print_r($user);
