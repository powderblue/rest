<?php

namespace Doctrine\Tests\REST\Client\Entity;

use Doctrine\REST\Client\Manager;
use Doctrine\REST\Client\Entity;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Factory method.
     * 
     * @return \Doctrine\REST\Client\Manager
     */
    private function createManager()
    {
        $manager = Manager::create();
        return $manager;
    }

    protected function setUp()
    {
        Entity::removeManager();
    }

    public function testGetmanagerReturnsTheManagerSetWithSetmanager()
    {
        $firstManager = $this->createManager();
        $secondManager = $this->createManager();

        $this->assertNotSame($secondManager, $firstManager);

        Test01::setManager($firstManager);
        Test02::setManager($secondManager);

        $this->assertSame($firstManager, Test01::getManager());
        $this->assertSame($secondManager, Test02::getManager());
    }

    public function testGetmanagerThrowsAnExceptionIfTheEntityDoesNotHaveAManager()
    {
        try {
            Test03::getManager();
        } catch (\RuntimeException $ex) {
            return $this->assertEquals('Doctrine\Tests\REST\Client\Entity\Test03 does not have its own entity manager and there is no default', $ex->getMessage());
        }

        $this->fail();
    }

    public function testGetmanagerReturnsTheManagerForEntityInTheAbsenceOfAManagerForTheCalledClass()
    {
        $defaultManager = $this->createManager();
        Entity::setManager($defaultManager);

        $manager = Test04::getManager();

        $this->assertSame($defaultManager, $manager);
    }

    public function testRemovemanagerRemovesTheManagerForTheCalledClass()
    {
        $manager = $this->createManager();

        Entity::setManager($manager);

        $this->assertSame($manager, Entity::getManager());

        Entity::removeManager();

        try {
            Entity::getManager();
        } catch (\RuntimeException $ex) {
            return $this->assertEquals('Doctrine\REST\Client\Entity does not have its own entity manager and there is no default', $ex->getMessage());
        }

        $this->fail();
    }

    public function testHasmanagerReturnsTrueIfTheCalledClassHasAManager()
    {
        $this->assertFalse(Test05::hasManager());

        Test05::setManager($this->createManager());

        $this->assertTrue(Test05::hasManager());
    }
}

class Test01 extends Entity
{
}

class Test02 extends Entity
{
}

class Test03 extends Entity
{
}

class Test04 extends Entity
{
}

class Test05 extends Entity
{
}
