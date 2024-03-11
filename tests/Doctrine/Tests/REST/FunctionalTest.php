<?php

namespace Doctrine\Tests\REST\Functional;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityManager;
use Doctrine\REST\Client\Manager;
use Doctrine\REST\Client\Request;
use Doctrine\REST\Client\Entity;
use Doctrine\REST\Client\EntityConfiguration;
use Doctrine\REST\Client\Client;
use Doctrine\REST\Server\Server;
use Doctrine\REST\Client\ResponseCache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
{
    private Manager $clientManager;

    private TestFunctionalClient $functiontalTestClient;

    private function setUpRest($type): void
    {
        $connectionOptions = array(
            'driver' => 'pdo_sqlite',
            'memory' => true
        );

        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new ArrayCache());
        $config->setProxyDir('/tmp');
        $config->setProxyNamespace('Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config);
        $classes = array($em->getMetadataFactory()->getMetadataFor(__NAMESPACE__ . '\DoctrineUser'));

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        if ($type === 'orm') {
            $this->functiontalTestClient = new TestFunctionalClient('user', $em);
        } else {
            $this->functiontalTestClient = new TestFunctionalClient('user', $em->getConnection());
        }

        $this->clientManager = new Manager($this->functiontalTestClient, new ResponseCache(sys_get_temp_dir()));
        $this->clientManager->registerEntity(__NAMESPACE__ . '\User');

        Entity::setManager($this->clientManager);
    }

    /** @return array<mixed[]> */
    public static function providesDbLibNames(): array
    {
        return [
            ['orm'],
            // ['dbal'],
        ];
    }

    #[DataProvider('providesDbLibNames')]
    public function testActiveRecordApi(string $dbLibName): void
    {
        $this->setUpRest($dbLibName);

        $user1 = new User();
        $user1->setUsername('jwage');
        $user1->save();

        $this->assertEquals(1, $user1->getId());

        $user2 = new User();
        $user2->setUsername('fabpot');
        $user2->save();

        $this->assertEquals(2, $user2->getId());

        $user3 = new User();
        $user3->setUsername('romanb');
        $user3->save();

        $this->assertEquals(3, $user3->getId());

        $user3->setUsername('romanb_new');
        $user3->save();

        $user3test = User::find($user3->getId());
        $this->assertEquals('romanb_new', $user3test->getUsername());

        $test = User::findAll();
        $this->assertEquals(3, count($test));
        $this->assertTrue($user1 === $test[0]);
        $this->assertTrue($user2 === $test[1]);
        $this->assertTrue($user3 === $test[2]);

        $user3->delete();

        $test = User::findAll();

        $this->assertEquals(2, count($test));
    }
}

// phpcs:disable
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
            $server->setEntityAlias(__NAMESPACE__ . '\DoctrineUser', 'user');
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

        // GET api/user/1.xml (get)
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

        // GET api/user.xml (list)
        if ($method === 'GET' && preg_match_all('/api\/' . $this->name . '.xml/', $url, $matches)) {
            return $this->execServer($request, array(
                '_method' => $method,
                '_format' => $responseType,
                '_entity' => $this->name,
                '_action' => 'list'
            ), $parameters, $responseType);
        }

        // PUT api/user.xml (insert)
        if ($method === 'PUT' && preg_match_all('/api\/' . $this->name . '.xml/', $url, $matches)) {
            return $this->execServer($request, array(
                '_method' => $method,
                '_format' => $responseType,
                '_entity' => $this->name,
                '_action' => 'insert'
            ), $parameters, $responseType);
        }

        // POST api/user/1.xml (update)
        if ($method === 'POST' && preg_match_all('/api\/' . $this->name . '\/([0-9]).xml/', $url, $matches)) {
            return $this->execServer($request, array(
                '_method' => $method,
                '_format' => $responseType,
                '_entity' => $this->name,
                '_action' => 'update',
                '_id' => $parameters['id']
            ), $parameters, $responseType);
        }

        // DELETE api/user/1.xml (delete)
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

    public static function configure(EntityConfiguration $entityConfiguration)
    {
        $entityConfiguration->setUrl('api');
        $entityConfiguration->setName('user');
    }

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
// phpcs:enable
