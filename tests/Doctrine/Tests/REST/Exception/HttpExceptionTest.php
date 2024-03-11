<?php

namespace Doctrine\Tests\REST\Exception\HttpException;

use Doctrine\REST\Exception\HttpException;
use PHPUnit\Framework\TestCase;

class HttpExceptionTest extends TestCase
{
    public function testIsAPhpException()
    {
        $this->assertTrue(is_subclass_of('Doctrine\REST\Exception\HttpException', 'RuntimeException'));
    }

    public function testIsInstantiatedUsingAMessageAndAnHttpStatusCode()
    {
        $exception = new HttpException('Not found', 404);

        $this->assertSame('Not found', $exception->getMessage());
        $this->assertSame(404, $exception->getCode());
    }

    public function testGethttpstatuscodeReturnsTheHttpStatusCodePassedToTheConstructor()
    {
        $exception = new HttpException('Not found', 404);
        $this->assertSame(404, $exception->getHttpStatusCode());
    }
}
