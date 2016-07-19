<?php

namespace Doctrine\Tests\REST\Client\ResponseCache;

use Doctrine\REST\Client\Request;
use Doctrine\REST\Client\Client;
use Doctrine\REST\Client\EntityConfiguration;
use Doctrine\REST\Client\Entity;
use Doctrine\REST\Client\ResponseCache;

class TestCase extends \PHPUnit_Framework_TestCase
{
    private function createResponseCache()
    {
        return new ResponseCache(__DIR__ . '/ResponseCacheTest');
    }

    protected function setUp()
    {
        //Remove all cache files associated with all test entity classes
        $this->createResponseCache()->emptyByEntityClassNames(array(
            __NAMESPACE__ . '\Entity01',
            __NAMESPACE__ . '\Entity02',
            __NAMESPACE__ . '\Entity03',
            __NAMESPACE__ . '\Entity04',
            __NAMESPACE__ . '\Entity05',
            __NAMESPACE__ . '\Entity06',
            __NAMESPACE__ . '\Entity07',
        ));
    }

    private function createRequest(EntityConfiguration $entityConfiguration, $httpMethod, $url, array $arguments = array())
    {
        $request = new Request();
        $request->setUrl($url);
        $request->setMethod($httpMethod);
        $request->setParameters($arguments);
        $request->setUsername($entityConfiguration->getUsername());
        $request->setPassword($entityConfiguration->getPassword());
        $request->setResponseType($entityConfiguration->getResponseType());
        $request->setResponseTransformerImpl($entityConfiguration->getResponseTransformerImpl());

        return $request;
    }

    public static function providesTrimmedDirPaths()
    {
        return array(
            array(
                __DIR__ . '/ResponseCacheTest',
                __DIR__ . '/ResponseCacheTest',
            ),
            array(
                __DIR__ . '/ResponseCacheTest',
                __DIR__ . '/ResponseCacheTest/',
            ),
        );
    }

    /**
     * @dataProvider providesTrimmedDirPaths
     */
    public function testIsInstantiatedUsingThePathOfADirectory($expectedDir, $actualDir)
    {
        $responseCache = new ResponseCache($actualDir);

        $this->assertSame($expectedDir, $responseCache->getDir());
    }

    public function testConstructorThrowsAnExceptionIfTheSpecifiedDirectoryDoesNotExist()
    {
        try {
            new ResponseCache('/nonexistent');
        } catch (\RuntimeException $ex) {
            return $this->assertSame('The directory does not exist', $ex->getMessage());
        }

        $this->fail();
    }

    /**
     * @dataProvider something
     */
    public function testGetiffreshReturnsTheValueSetUsingSet($cacheResponse, $originalResponse, $entityClassName)
    {
        $responseCache = $this->createResponseCache();

        $entityConfiguration = new EntityConfiguration($entityClassName);
        $entityClassName::configure($entityConfiguration);

        $request = $this->createRequest($entityConfiguration, Client::GET, 'http://localhost/users/1');

        $responseCache->set($entityConfiguration, $request, $originalResponse);

        $this->assertEquals($cacheResponse, $responseCache->getIfFresh($entityConfiguration, $request));
    }

    public static function something()
    {
        return array(
            array(
                array(
                    'username' => 'danbettles',
                    'password' => 'letmein',
                ),
                array(
                    'username' => 'danbettles',
                    'password' => 'letmein',
                ),
                __NAMESPACE__ . '\Entity01',
            ),
            array(
                false,
                array(
                    'username' => 'danbettles',
                    'password' => 'letmein',
                ),
                __NAMESPACE__ . '\Entity02',
            ),
            array(
                false,
                array(
                    'username' => 'danbettles',
                    'password' => 'letmein',
                ),
                __NAMESPACE__ . '\Entity03',
            ),
        );
    }

    public function testGetiffreshReturnsFalseIfAResponseWasNotPreviouslyCached()
    {
        $responseCache = $this->createResponseCache();

        $entityClassName = __NAMESPACE__ . '\Entity04';

        $entityConfiguration = new EntityConfiguration($entityClassName);
        $entityClassName::configure($entityConfiguration);

        $request = $this->createRequest($entityConfiguration, Client::GET, 'http://localhost/users/1');

        $this->assertFalse($responseCache->getIfFresh($entityConfiguration, $request));
    }

    public function testEmptybyentityclassnamesRemovesAllCacheFilesAssociatedWithEachOfTheSpecifiedEntityClasses()
    {
        $responseCache = $this->createResponseCache();

        //Add some responses to the cache

        $aResponse = array(
            'username' => 'danbettles',
            'password' => 'letmein',
        );

        $entityConfig1 = new EntityConfiguration(__NAMESPACE__ . '\Entity06');
        Entity06::configure($entityConfig1);
        $request1 = $this->createRequest($entityConfig1, Client::GET, 'http://localhost/users/1');
        $responseCache->set($entityConfig1, $request1, $aResponse);

        $entityConfig2 = new EntityConfiguration(__NAMESPACE__ . '\Entity07');
        Entity07::configure($entityConfig2);
        $request2 = $this->createRequest($entityConfig2, Client::GET, 'http://localhost/users/1');
        $responseCache->set($entityConfig2, $request2, $aResponse);

        $this->assertEquals($aResponse, $responseCache->getIfFresh($entityConfig1, $request1));
        $this->assertEquals($aResponse, $responseCache->getIfFresh($entityConfig2, $request2));

        //Now remove all cache files

        $responseCache->emptyByEntityClassNames(array(
            __NAMESPACE__ . '\Entity06',
            __NAMESPACE__ . '\Entity07',
        ));

        $this->assertFalse($responseCache->getIfFresh($entityConfig1, $request1));
        $this->assertFalse($responseCache->getIfFresh($entityConfig2, $request2));
    }
}

class Entity01 extends Entity
{
    private $username;

    private $password;

    public static function configure(EntityConfiguration $entityConfiguration)
    {
        $entityConfiguration->setCacheTtl(60);
    }
}

class Entity02 extends Entity
{
    private $username;

    private $password;

    public static function configure(EntityConfiguration $entityConfiguration)
    {
        $entityConfiguration->setCacheTtl(0);
    }
}

class Entity03 extends Entity
{
    private $username;

    private $password;

    public static function configure(EntityConfiguration $entityConfiguration)
    {
    }
}

class Entity04 extends Entity
{
    private $username;

    private $password;

    public static function configure(EntityConfiguration $entityConfiguration)
    {
    }
}

class Entity05 extends Entity
{
    private $username;

    private $password;

    public static function configure(EntityConfiguration $entityConfiguration)
    {
        $entityConfiguration->setCacheTtl(60);
    }
}

class Entity06 extends Entity
{
    private $username;

    private $password;

    public static function configure(EntityConfiguration $entityConfiguration)
    {
        $entityConfiguration->setCacheTtl(60);
    }
}

class Entity07 extends Entity
{
    private $username;

    private $password;

    public static function configure(EntityConfiguration $entityConfiguration)
    {
        $entityConfiguration->setCacheTtl(60);
    }
}
