<?php

use Doctrine\ORM as ORM;
use Doctrine\REST\Server as RESTServer;

require_once __DIR__ . '/../../vendor/autoload.php';

class TestAction
{
    public function executeDBAL()
    {
        return array('test' => 'test');
    }
}

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

//Insert some records:

$em = createEntityManager();

$conn = $em->getConnection();
$conn->exec("CREATE TABLE user (id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL)");
$conn->exec("INSERT INTO user (username, password) VALUES ('joebloggs', 'foo')");
$conn->exec("INSERT INTO user (username, password) VALUES ('fredbloggs', 'bar')");

////Test request:
//
//$_SERVER['REQUEST_METHOD'] = 'GET';
////$_SERVER['PATH_INFO'] = '/user/1/test';
//$_SERVER['PATH_INFO'] = '/user/1';

//Handle the request:

$parser = new RESTServer\PHPRequestParser();
$requestData = $parser->getRequestArray();

$server = new RESTServer\Server($em->getConnection(), $requestData);
$server->addEntityAction('user', 'test', 'TestAction');
$server->execute();
$server->getResponse()->send();
