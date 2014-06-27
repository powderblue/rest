<?php

namespace Doctrine\Tests\REST\Client\Manager;

use Doctrine\REST\Client\Manager;
use Doctrine\REST\Client\Entity;
use Doctrine\REST\Client\Client;
use Doctrine\REST\Client\EntityConfiguration;
use Doctrine\REST\Client\URLGenerator\StandardURLGenerator;
use Doctrine\REST\Client\ResponseTransformer\StandardResponseTransformer;
use Doctrine\REST\Client\ResponseCache;
use Doctrine\REST\Client\Request;
use Doctrine\REST\Exception\HttpException;

class TestCase extends \PHPUnit_Framework_TestCase
{
    private function createResponseCache($className = 'Doctrine\REST\Client\ResponseCache')
    {
        return new $className(sys_get_temp_dir());
    }

    private function createManager($className = 'Doctrine\REST\Client\Manager')
    {
        $client = new Client();
        $responseCache = $this->createResponseCache();
        $manager = new $className($client, $responseCache);
        return $manager;
    }

    protected function setUp()
    {
        //Remove all cache files associated with all test entity classes
        $this->createResponseCache()->emptyByEntityClassNames(array(
            __NAMESPACE__ . '\Entity01',
            __NAMESPACE__ . '\Entity02',
            __NAMESPACE__ . '\Entity03',
        ));
    }

    public function testGetdefaultentityconfigurationattributesReturnsTheAttributesSetUsingSetdefaultentityconfigurationattributes()
    {
        $manager = $this->createManager();

        $sourceAttributeValues = array(
            'url' => 'http://localhost',
            'username' => 'root',
            'password' => 'letmein',
        );

        $manager->setDefaultEntityConfigurationAttributes($sourceAttributeValues);

        $this->assertSame($sourceAttributeValues, $manager->getDefaultEntityConfigurationAttributes());
    }

    public function testRegisterentityConfiguresTheEntityClassWithTheSpecifiedName()
    {
        $entityClassName = __NAMESPACE__ . '\Entity01';

        $manager = $this->createManager();

        $manager->setDefaultEntityConfigurationAttributes(array(
            'url' => 'http://localhost',
            'username' => 'root',
            'password' => 'letmein',
        ));

        $manager->registerEntity($entityClassName);

        $configuration = $manager->getEntityConfiguration($entityClassName);

        $this->assertInstanceOf('Doctrine\REST\Client\EntityConfiguration', $configuration);

        $this->assertEquals(array(
            'class' => $entityClassName,
            'url' => 'http://localhost',
            'name' => 'entity01',
            'username' => 'root',
            'password' => 'letmein',
            'identifierKey' => 'id',
            'responseType' => 'xml',
            'urlGeneratorImpl' => new StandardURLGenerator($configuration),
            'responseTransformerImpl' => new StandardResponseTransformer($configuration),
            'cacheTtl' => null,
        ), $configuration->getAttributeValues());

        $this->assertSame($manager, $entityClassName::getManager());
    }

    public function testRegisterentityDoesNotRequireEachEntityToHaveAConfigureMethod()
    {
        $entityClassName = __NAMESPACE__ . '\Entity02';

        $manager = $this->createManager();
        $manager->registerEntity($entityClassName);
    }

    public function testGetentityconfigurationAlsoAcceptsAnEntityObject()
    {
        $entityClassName = __NAMESPACE__ . '\Entity01';

        $manager = $this->createManager();
        $manager->registerEntity($entityClassName);

        $configuration = $manager->getEntityConfiguration(new $entityClassName());

        $this->assertInstanceOf('Doctrine\REST\Client\EntityConfiguration', $configuration);
        $this->assertSame($manager->getEntityConfiguration($entityClassName), $configuration);
    }

    public function testIsInstantiatedUsingResponsecache()
    {
        $responseCache = $this->createResponseCache();
        $manager = new Manager(new Client(), $responseCache);
        $this->assertSame($responseCache, $manager->getResponseCache());
    }

    public function testExecuteReturnsACachedResponseIfCachingIsEnabledForTheEntity()
    {
        $entity = __NAMESPACE__ . '\Entity03';

        $manager = new Manager(new Client01(), $this->createResponseCache(__NAMESPACE__ . '\ResponseCache01'));
        $manager->registerEntity($entity);

        $entityInstance = $manager->execute($entity, 'http://localhost/users/1');

        $this->assertEquals(array(
            'id' => '1',
            'username' => 'live',
            'password' => 'live',
        ), $entityInstance->toArray());

        $entityInstance = $manager->execute($entity, 'http://localhost/users/1');

        $this->assertEquals(array(
            'id' => '1',
            'username' => 'cached',
            'password' => 'cached',
        ), $entityInstance->toArray());
    }

    public function testExecuteAlwaysReturnsALiveResponseIfCachingIsDisabledForTheEntity()
    {
        $entity = __NAMESPACE__ . '\Entity04';

        $manager = new Manager(new Client01(), $this->createResponseCache(__NAMESPACE__ . '\ResponseCache01'));
        $manager->registerEntity($entity);

        $entityInstance = $manager->execute($entity, 'http://localhost/users/1');

        $this->assertEquals(array(
            'id' => '1',
            'username' => 'live',
            'password' => 'live',
        ), $entityInstance->toArray());

        $entityInstance = $manager->execute($entity, 'http://localhost/users/1');

        $this->assertEquals(array(
            'id' => '1',
            'username' => 'live',
            'password' => 'live',
        ), $entityInstance->toArray());
    }

    /**
     * @dataProvider uncacheableHttpMethods
     */
    public function testExecuteAlwaysReturnsALiveResponseIfTheRequestMethodIsNotGet($uncacheableHttpMethod)
    {
        $entity = __NAMESPACE__ . '\Entity05';

        $manager = new Manager(new Client01(), $this->createResponseCache(__NAMESPACE__ . '\ResponseCache01'));
        $manager->registerEntity($entity);

        $liveResponse = array(
            'id' => '1',
            'username' => 'live',
            'password' => 'live',
        );

        $entityInstance = $manager->execute($entity, 'http://localhost/users/1', $uncacheableHttpMethod);

        $this->assertEquals($liveResponse, $entityInstance->toArray());

        $entityInstance = $manager->execute($entity, 'http://localhost/users/1', $uncacheableHttpMethod);

        $this->assertEquals($liveResponse, $entityInstance->toArray());
    }

    public static function uncacheableHttpMethods()
    {
        return array(
            array(Client::POST),
            array(Client::PUT),
            array(Client::DELETE),
        );
    }

    public function testExecuteThrowsAnHttpexceptionIfAnHttpErrorOccurred()
    {
        $entityClassName = __NAMESPACE__ . '\Entity06';

        $manager = $this->createManager();
        $manager->registerEntity($entityClassName);

        try {
            $manager->execute($entityClassName, 'http://example.com/entity06s/1', Client::GET);
        } catch (HttpException $ex) {
            $this->assertSame(404, $ex->getCode());
            $this->assertSame('The HTTP request was unsuccessful', $ex->getMessage());
            return;
        }

        $this->fail();
    }
}

class Entity01 extends Entity
{
    public static function configure(EntityConfiguration $entityConfiguration)
    {
        $entityConfiguration->setName('entity01');
    }
}

class Entity02 extends Entity
{
}

class Entity03 extends Entity
{
    protected $id;

    protected $username;

    protected $password;

    public static function configure(EntityConfiguration $entityConfiguration)
    {
        $entityConfiguration->setCacheTtl(60);
    }
}

class Entity04 extends Entity
{
    protected $id;

    protected $username;

    protected $password;
}

class Entity05 extends Entity
{
    protected $id;

    protected $username;

    protected $password;

    public static function configure(EntityConfiguration $entityConfiguration)
    {
        $entityConfiguration->setCacheTtl(60);
    }
}

class Entity06 extends Entity
{
    protected $id;
}

class Client01 extends Client
{
    public function execute(Request $request)
    {
        return array(
            'id' => '1',
            'username' => 'live',
            'password' => 'live',
        );
    }
}

class ResponseCache01 extends ResponseCache
{
    public function getIfFresh(EntityConfiguration $entityConfiguration, Request $request)
    {
        $something = parent::getIfFresh($entityConfiguration, $request);

        if ($something === false) {
            return $something;
        }

        $something['username'] = 'cached';
        $something['password'] = 'cached';

        return $something;
    }
}
