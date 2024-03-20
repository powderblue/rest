<?php

// phpcs:ignoreFile

namespace Doctrine\REST;

use Doctrine\ORM as ORM;

require_once __DIR__ . '/../../vendor/autoload.php';

function createORMConfiguration()
{
    $config = new ORM\Configuration();
    $config->setProxyDir(sys_get_temp_dir());
    $config->setProxyNamespace('Proxies');
    $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());
    return $config;
}

function createEntityManager()
{
    return ORM\EntityManager::create(array(
        'driver' => 'pdo_sqlite',
        'memory' => true
    ), createORMConfiguration());
}

$em = createEntityManager();

//Insert some records:

$conn = $em->getConnection();
$conn->exec("CREATE TABLE users (id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL)");
$conn->exec("INSERT INTO users (username, password) VALUES ('joebloggs', 'foo')");
$conn->exec("INSERT INTO users (username, password) VALUES ('fredbloggs', 'bar')");

//Handle the request:

$parser = new Server\PHPRequestParser();
$requestData = $parser->getRequestArray();

$server = new Server\Server($em->getConnection(), $requestData);
$server->execute();
$server->getResponse()->send();
