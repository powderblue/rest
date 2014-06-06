<?php

namespace Doctrine\Tests\REST\Client\Manager;

use Doctrine\REST\Client\Manager;
use Doctrine\REST\Client\Entity;
use Doctrine\REST\Client\Client;
use Doctrine\REST\Client\EntityConfiguration;
use Doctrine\REST\Client\URLGenerator\StandardURLGenerator;
use Doctrine\REST\Client\ResponseTransformer\StandardResponseTransformer;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Factory method.
     * 
     * @return \Doctrine\REST\Client\Manager
     */
    private function createManager()
    {
        $client = new Client();
        $manager = new Manager($client);
        return $manager;
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
        ), $configuration->getAttributeValues());

        $this->assertSame($manager, $entityClassName::getManager());
    }

    public function testRegisterentityDoesNotRequireEachEntityToHaveAConfigureMethod()
    {
        $entityClassName = __NAMESPACE__ . '\Entity02';

        $manager = $this->createManager();
        $manager->registerEntity($entityClassName);
    }

    public function testCreateReturnsAFullyConfiguredInstance()
    {
        $manager = Manager::create();

        $this->assertInstanceOf('Doctrine\REST\Client\Manager', $manager);
//        $this->assertInstanceOf('Doctrine\REST\Client\Client', $manager->getClient());
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
