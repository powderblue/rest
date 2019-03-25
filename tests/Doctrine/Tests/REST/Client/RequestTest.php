<?php

namespace Doctrine\Tests\REST\Client\Request;

use Doctrine\REST\Client\Request;
use Doctrine\REST\Client\Client;
use Doctrine\REST\Client\ResponseTransformer\StandardResponseTransformer;
use Doctrine\REST\Client\EntityConfiguration;
use Doctrine\REST\Client\Entity;

class TestCase extends \PHPUnit_Framework_TestCase
{
    public function testGetrequestidReturnsAnIdForTheRequest()
    {
        $originalResponseTransformer = new StandardResponseTransformer(new EntityConfiguration(__NAMESPACE__ . '\Entity01'));
        $originalRequest = new Request();
        $originalRequest->setUrl('http://localhost');
        $originalRequest->setMethod(Client::GET);
        $originalRequest->setParameters(array('foo' => 'bar', 'baz' => 'bip'));
        $originalRequest->setUsername('root');
        $originalRequest->setPassword('letmein');
        $originalRequest->setResponseType('json');
        $originalRequest->setResponseTransformerImpl($originalResponseTransformer);

        $originalRequestId = $originalRequest->getRequestId();

        $this->assertEquals('4fe8889a0cbf3922a4f7ff524c6fad76', $originalRequestId);

        $similarResponseTransformer = new StandardResponseTransformer(new EntityConfiguration(__NAMESPACE__ . '\Entity01'));
        $similarRequest = new Request();
        $similarRequest->setUrl('http://localhost');
        $similarRequest->setMethod(Client::GET);
        $similarRequest->setParameters(array('baz' => 'bip', 'foo' => 'bar'));  //(Values same as before but order different)
        $similarRequest->setUsername('root');
        $similarRequest->setPassword('letmein');
        $similarRequest->setResponseType('json');
        $similarRequest->setResponseTransformerImpl($similarResponseTransformer);

        $this->assertEquals($originalRequestId, $similarRequest->getRequestId());

        $similarResponseTransformer = new StandardResponseTransformer(new EntityConfiguration(__NAMESPACE__ . '\Entity01'));
        $similarRequest = new Request();
        $similarRequest->setUrl('http://localhost/');  //(The URL has a trailing slash)
        $similarRequest->setMethod(Client::GET);
        $similarRequest->setParameters(array('foo' => 'bar', 'baz' => 'bip'));
        $similarRequest->setUsername('root');
        $similarRequest->setPassword('letmein');
        $similarRequest->setResponseType('json');
        $similarRequest->setResponseTransformerImpl($similarResponseTransformer);

        $this->assertEquals($originalRequestId, $similarRequest->getRequestId());
    }

    public function testGetrequestidReturnsADifferentIdIfTheValuesOfPropertiesAreDifferent()
    {
        $originalRequest = new Request();
        $originalRequest->setUrl('http://localhost');
        $originalRequest->setMethod(Client::GET);
        $originalRequest->setParameters(array('foo' => 'bar', 'baz' => 'bip'));
        $originalRequest->setUsername('root');
        $originalRequest->setPassword('letmein');
        $originalRequest->setResponseType('json');
        $responseTransformer = new StandardResponseTransformer(new EntityConfiguration(__NAMESPACE__ . '\Entity01'));
        $originalRequest->setResponseTransformerImpl($responseTransformer);

        $originalRequestId = $originalRequest->getRequestId();

        $anotherRequest = clone $originalRequest;
        $anotherRequest->setUrl('http://example.com');

        $this->assertNotEquals($originalRequestId, $anotherRequest->getRequestId());

        $anotherRequest = clone $originalRequest;
        $anotherRequest->setMethod(Client::POST);

        $this->assertNotEquals($originalRequestId, $anotherRequest->getRequestId());

        $anotherRequest = clone $originalRequest;
        $anotherRequest->setParameters(array('foo' => 'bar'));

        $this->assertNotEquals($originalRequestId, $anotherRequest->getRequestId());

        $anotherRequest = clone $originalRequest;
        $anotherRequest->setUsername('joebloggs');

        $this->assertNotEquals($originalRequestId, $anotherRequest->getRequestId());

        $anotherRequest = clone $originalRequest;
        $anotherRequest->setPassword('password');

        $this->assertNotEquals($originalRequestId, $anotherRequest->getRequestId());

        $anotherRequest = clone $originalRequest;
        $anotherRequest->setResponseType('xml');

        $this->assertNotEquals($originalRequestId, $anotherRequest->getRequestId());

        $anotherRequest = clone $originalRequest;
        $responseTransformer = new StandardResponseTransformer(new EntityConfiguration(__NAMESPACE__ . '\Entity02'));
        $anotherRequest->setResponseTransformerImpl($responseTransformer);

        $this->assertNotEquals($originalRequestId, $anotherRequest->getRequestId());
    }

    public function testSeturlStripsTrailingSlashes()
    {
        $request = new Request();

        $request->setUrl('http://localhost/');
        $this->assertEquals('http://localhost', $request->getUrl());

        $request->setUrl('http://localhost');
        $this->assertEquals('http://localhost', $request->getUrl());
    }

    public function testSetparametersSortsTheArgumentsInKeyOrder()
    {
        $arguments = array('foo' => 'bar', 'baz' => 'bip');
        $request = new Request();
        $request->setParameters($arguments);

        //The values should be the same...
        $this->assertEquals($arguments, $request->getParameters());

        //...But the order of the keys should be different
        $this->assertEquals(array('baz', 'foo'), array_keys($request->getParameters()));
    }
}

class Entity01 extends Entity
{
    private $foo;

    private $bar;
}

class Entity02 extends Entity
{
    private $foo;

    private $bar;
}
