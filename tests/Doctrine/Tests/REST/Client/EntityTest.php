<?php

namespace Doctrine\Tests\REST\Client\Entity;

use Doctrine\REST\Client\Manager;
use Doctrine\REST\Client\Entity;
use Doctrine\REST\Client\ResponseCache;
use Doctrine\REST\Client\Client;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    /**
     * Factory method.
     *
     * @return \Doctrine\REST\Client\Manager
     */
    private function createManager()
    {
        return new Manager(new Client(), new ResponseCache(sys_get_temp_dir()));
    }

    protected function setUp(): void
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

    public function testGetmanagerReturnsTheManagerForTheEntityParentClassInTheAbsenceOfAManagerForTheCalledClass()
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

// phpcs:disable
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
// phpcs:enable
