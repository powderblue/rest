<?php

declare(strict_types=1);

namespace Doctrine\Tests\REST\Client\Manager;

use Doctrine\REST\Client\Client;
use Doctrine\REST\Client\Entity;
use Doctrine\REST\Client\EntityConfiguration;
use Doctrine\REST\Client\Manager;
use Doctrine\REST\Client\Request;
use Doctrine\REST\Client\ResponseCache;
use Doctrine\REST\Client\ResponseTransformer\StandardResponseTransformer;
use Doctrine\REST\Client\URLGenerator\StandardURLGenerator;
use Doctrine\REST\Exception\HttpException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use const false;
use const null;
use const true;

class ManagerTest extends TestCase
{
    private function createResponseCache($className = 'Doctrine\REST\Client\ResponseCache')
    {
        return new $className(sys_get_temp_dir());
    }

    private function createManager(): Manager
    {
        $client = new Client();
        $responseCache = $this->createResponseCache();

        return new Manager($client, $responseCache);
    }

    protected function setUp(): void
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
            'appendSuffix' => true,
        ), $configuration->getAttributeValues());

        $this->assertSame($manager, $entityClassName::getManager());
    }

    public function testRegisterentityDoesNotRequireEachEntityToHaveAConfigureMethod()
    {
        $this->expectNotToPerformAssertions();

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

    #[DataProvider('uncacheableHttpMethods')]
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
            $manager->execute($entityClassName, 'https://jsonplaceholder.typicode.com/posts/0', Client::GET);
        } catch (HttpException $ex) {
            $this->assertSame(404, $ex->getCode());
            $this->assertSame('The HTTP request was unsuccessful', $ex->getMessage());

            return;
        }

        $this->fail();
    }

    public function testArrayFieldValuesInResponsesAreNotConvertedToStrings()
    {
        $entityClassName = __NAMESPACE__ . '\Entity07';

        $manager = new Manager(new Client02(), $this->createResponseCache());
        $manager->registerEntity($entityClassName);

        $entityInstance = $manager->execute($entityClassName, 'http://localhost/users/1', Client::GET);

        $this->assertEquals(array(
            'home' => '01234 567890',
            'mobile' => '07123 456789',
        ), $entityInstance->telephone_numbers);
    }
}

// phpcs:disable
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

class Entity07 extends Entity
{
    public $id;

    public $telephone_numbers = array();
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

class Client02 extends Client
{
    public function execute(Request $request)
    {
        return array(
            'id' => '1',
            'telephone_numbers' => array(
                'home' => '01234 567890',
                'mobile' => '07123 456789',
            ),
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
// phpcs:enable
