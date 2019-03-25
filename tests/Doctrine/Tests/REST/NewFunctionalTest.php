<?php

namespace Doctrine\Tests\REST\NewFunctional;

use Doctrine\ORM\EntityManager;
use Doctrine\REST\Client\Manager;
use Doctrine\REST\Client\Request;
use Doctrine\REST\Client\Entity;
use Doctrine\REST\Client\Client;
use Doctrine\REST\Server\Server;
use Doctrine\REST\Client\ResponseCache;

class TestCase extends \PHPUnit_Framework_TestCase
{
    private function createEntityManager()
    {
        $connectionOptions = array(
            'driver' => 'pdo_sqlite',
            'memory' => true
        );

        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $em = EntityManager::create($connectionOptions, $config);

        return $em;
    }

    private function setUpTestDatabase(EntityManager $em, array $entityClassNames)
    {
        $classes = array();

        foreach ($entityClassNames as $entityClassName) {
            $classes[] = $em->getMetadataFactory()->getMetadataFor($entityClassName);
        }

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    private function createClientManager($entityResourceName, EntityManager $em)
    {
        $functiontalTestClient = new TestFunctionalClient($entityResourceName, $em);
        $responseCache = new ResponseCache(sys_get_temp_dir());
        $clientManager = new Manager($functiontalTestClient, $responseCache);
        return $clientManager;
    }

    public function testConfiguringEntitiesIsEasier()
    {
        //Set-up the test server:

        $entityManager = $this->createEntityManager();

        $this->setUpTestDatabase($entityManager, array(
            __NAMESPACE__ . '\DoctrineUser',
        ));

        //Set-up the test client:

        $clientManager = $this->createClientManager('users', $entityManager);

        //1. Set default attribute values for entities that will be associated with this manager.  These settings do not
        //need to be specified in each entity.

        $clientManager->setDefaultEntityConfigurationAttributes(array(
            'url' => 'api',
        ));

        //2. Register entities.  Configuration done.

        $clientManager->registerEntity(__NAMESPACE__ . '\User');

        $entityConfiguration = $clientManager->getEntityConfiguration(__NAMESPACE__ . '\User');

        $this->assertEquals('users', $entityConfiguration->getName());
        $this->assertEquals('api', $entityConfiguration->getUrl());

        //3. Use entity classes

        $user1 = new User();
        $user1->setUsername('jwage');
        $user1->save();

        $this->assertEquals(1, $user1->getId());

        $user2 = new User();
        $user2->setUsername('danbettles');
        $user2->save();

        $this->assertEquals(2, $user2->getId());

        $user2->setUsername('dan_bettles');
        $user2->save();
        $fetchedUser2 = User::find($user2->getId());

        $this->assertEquals('dan_bettles', $fetchedUser2->getUsername());

        $fetchedUsers = User::findAll();

        $this->assertEquals(array($user1, $user2), $fetchedUsers);

        $user2->delete();
        $fetchedUsers = User::findAll();

        $this->assertEquals(array($user1), $fetchedUsers);
    }
}

class TestFunctionalClient extends Client
{
    public $name;
    public $source;
    public $data = array();
    public $count = 0;

    public function __construct($name, $source)
    {
        $this->name = $name;
        $this->source = $source;
    }

    public function execServer($request, $requestArray, $parameters = array(), $responseType = 'xml')
    {
        $requestArray = array_merge($requestArray, (array) $parameters);
        $server = new Server($this->source, $requestArray);
        if ($this->source instanceof EntityManager) {
            $server->setEntityAlias(__NAMESPACE__ . '\DoctrineUser', $this->name);
        }
        $response = $server->getRequestHandler()->execute();
        $data = $request->getResponseTransformerImpl()->transform($response->getContent());
        return $data;
    }

    public function execute(Request $request)
    {
        $url = $request->getUrl();
        $method = $request->getMethod();
        $parameters = $request->getParameters();
        $responseType = $request->getResponseType();

        // GET api/users/1.xml (get)
        if ($method === 'GET' && preg_match_all('/api\/' . $this->name . '\/([0-9]).xml/', $url, $matches)) {
            $id = $matches[1][0];
            return $this->execServer($request, array(
                '_method' => $method,
                '_format' => $responseType,
                '_entity' => $this->name,
                '_action' => 'get',
                '_id' => $id
            ), $parameters, $responseType);
        }

        // GET api/users.xml (list)
        if ($method === 'GET' && preg_match_all('/api\/' . $this->name . '.xml/', $url, $matches)) {
            return $this->execServer($request, array(
                '_method' => $method,
                '_format' => $responseType,
                '_entity' => $this->name,
                '_action' => 'list'
            ), $parameters, $responseType);
        }

        // PUT api/users.xml (insert)
        if ($method === 'PUT' && preg_match_all('/api\/' . $this->name . '.xml/', $url, $matches)) {
            return $this->execServer($request, array(
                '_method' => $method,
                '_format' => $responseType,
                '_entity' => $this->name,
                '_action' => 'insert'
            ), $parameters, $responseType);
        }

        // POST api/users/1.xml (update)
        if ($method === 'POST' && preg_match_all('/api\/' . $this->name . '\/([0-9]).xml/', $url, $matches)) {
            return $this->execServer($request, array(
                '_method' => $method,
                '_format' => $responseType,
                '_entity' => $this->name,
                '_action' => 'update',
                '_id' => $parameters['id']
            ), $parameters, $responseType);
        }

        // DELETE api/users/1.xml (delete)
        if ($method === 'DELETE' && preg_match_all('/api\/' . $this->name . '\/([0-9]).xml/', $url, $matches)) {
            return $this->execServer($request, array(
                '_method' => $method,
                '_format' => $responseType,
                '_entity' => $this->name,
                '_action' => 'delete',
                '_id' => $matches[1][0]
            ), $parameters, $responseType);
        }
    }
}

class User extends Entity
{
    protected $id;
    protected $username;

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }
}

/**
 * @Entity
 * @Table(name="user")
 */
class DoctrineUser
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string", length=255, unique=true)
     */
    private $username;

    public function setUsername($username)
    {
        $this->username = $username;
    }
}
