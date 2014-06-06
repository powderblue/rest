<?php

namespace Doctrine\Tests\REST\Client\EntityConfiguration;

use Doctrine\REST\Client\Entity;
use Doctrine\REST\Client\EntityConfiguration;
use Doctrine\REST\Client\URLGenerator\StandardURLGenerator;
use Doctrine\REST\Client\ResponseTransformer\StandardResponseTransformer;

class TestCase extends \PHPUnit_Framework_TestCase
{
    public function testGeturlReturnsTheValueOfTheUrlAttributeSetUsingSetUrl()
    {
        $configuration = new EntityConfiguration(__NAMESPACE__ . '\Entity01');
        $configuration->setUrl('http://localhost/');
        $this->assertEquals('http://localhost', $configuration->getUrl());
    }

    public function testGetattributesReturnsTheAttributesSetUsingSetattributes()
    {
        $sourceAttributes = array(
            'url' => 'http://localhost/',
            'username' => 'root',
        );

        $expectedAttributes = array(
            'url' => 'http://localhost',
            'username' => 'root',
        );

        $configuration = new EntityConfiguration(__NAMESPACE__ . '\Entity01');
        $configuration->setAttributeValues($sourceAttributes);

        $this->assertEquals($expectedAttributes, array_intersect_assoc($expectedAttributes, $configuration->getAttributeValues()));
        $this->assertEquals($expectedAttributes['url'], $configuration->getUrl());
        $this->assertEquals($expectedAttributes['username'], $configuration->getUsername());
    }

    public function testHasDefaultAttributeValues()
    {
        $configuration = new EntityConfiguration(__NAMESPACE__ . '\Entity01');

        $this->assertEquals(array(
            'class' => __NAMESPACE__ . '\Entity01',
            'url' => null,
            'name' => 'entity01s',
            'username' => null,
            'password' => null,
            'identifierKey' => 'id',
            'responseType' => 'xml',
            'urlGeneratorImpl' => new StandardURLGenerator($configuration),
            'responseTransformerImpl' => new StandardResponseTransformer($configuration),
        ), $configuration->getAttributeValues());
    }

    public function testSetattributevaluesThrowsAnExceptionIfAnAttemptIsMadeToSetTheValueOfAnAttributeThatDoesNotExist()
    {
        $configuration = new EntityConfiguration(__NAMESPACE__ . '\Entity01');

        try {
            $configuration->setAttributeValues(array(
                'foo' => 'bar',
            ));
        } catch (\OutOfBoundsException $ex) {
            return $this->assertEquals('The attribute "foo" does not exist', $ex->getMessage());
        }

        $this->fail();
    }

    public function testGetclassReturnsTheValueOfTheClassAttributeSetUsingSetclass()
    {
        $configuration = new EntityConfiguration(__NAMESPACE__ . '\Entity01');
        $configuration->setClass('Foo');
        $this->assertEquals('Foo', $configuration->getClass());
    }

    public function testGeturlgeneratorimplReturnsTheUrlgeneratorimplAttributeSetUsingSeturlgeneratorimpl()
    {
        $configuration = new EntityConfiguration(__NAMESPACE__ . '\Entity01');
        $urlGenerator = new StandardURLGenerator($configuration);
        $configuration->setURLGeneratorImpl($urlGenerator);
        $this->assertSame($urlGenerator, $configuration->getURLGeneratorImpl());
    }

    public function testGetpasswordReturnsThePasswordSetUsingSetpassword()
    {
        $configuration = new EntityConfiguration(__NAMESPACE__ . '\Entity01');
        $configuration->setPassword('letmein');
        $this->assertEquals('letmein', $configuration->getPassword());
    }

    public function testCallThrowsAnExceptionIfTheCalledMethodDoesNotExist()
    {
        $configuration = new EntityConfiguration(__NAMESPACE__ . '\Entity01');

        try {
            $configuration->poop();
        } catch (\BadMethodCallException $ex) {
            return $this->assertEquals('The method "poop" is not implemented', $ex->getMessage());
        }

        $this->fail();
    }

    public function testMagicAttributeGettersThrowAnExceptionIfTheReferencedAttributeDoesNotExist()
    {
        $configuration = new EntityConfiguration(__NAMESPACE__ . '\Entity01');

        try {
            $configuration->getNonExistentAttribute();
        } catch (\OutOfBoundsException $ex) {
            return $this->assertEquals('The attribute "nonExistentAttribute" does not exist', $ex->getMessage());
        }

        $this->fail();
    }

    public function testCanBeInstantiatedUsingAnArrayOfDefaultAttributeValues()
    {
        $sourceAttributeValues = array(
            'class' => __NAMESPACE__ . '\Entity01',
            'username' => 'root',
            'password' => 'letmein',
        );

        $configuration = new EntityConfiguration($sourceAttributeValues);

        $this->assertEquals($sourceAttributeValues['class'], $configuration->getClass());
        $this->assertEquals($sourceAttributeValues['username'], $configuration->getUsername());
        $this->assertEquals($sourceAttributeValues['password'], $configuration->getPassword());
    }

    public function testConstructorThrowsAnExceptionIfTheAttributeValuesDoNotIncludeTheClassName()
    {
        try {
            new EntityConfiguration(array());
        } catch (\InvalidArgumentException $ex) {
            return $this->assertEquals('The entity class name was not specified', $ex->getMessage());
        }

        $this->fail();
    }

    /**
     * @dataProvider pluralizedEntityClassNames
     */
    public function testConstructorDerivesTheNameOfTheEntityIfNotSpecified($name, $className)
    {
        $configuration = new EntityConfiguration($className);
        $this->assertEquals($name, $configuration->getName());
    }

    public static function pluralizedEntityClassNames()
    {
        return array(
            array(
                'statuses',
                __NAMESPACE__ . '\Status',
            ),
            array(
                'people',
                __NAMESPACE__ . '\Person',
            ),
        );
    }
}

class Entity01 extends Entity
{
    private $foo;

    private $bar;
}

class Status extends Entity
{
    private $foo;
}

class Person extends Entity
{
    private $foo;
}
