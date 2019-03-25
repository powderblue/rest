<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
*/

namespace Doctrine\REST\Client;

/**
 * Class responsible for managing the entities registered for REST services.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @todo        Remove identity map?  It appears to add complexity without providing any real benefits.
 */
class Manager
{
    /**
     * @var Doctrine\REST\Client\Client
     */
    private $_client;

    /**
     * @var array
     */
    private $_entityConfigurations = array();

    /**
     * @var array
     */
    private $_identityMap = array();

    /**
     * @var array
     */
    private $defaultEntityConfigurationAttributes = array();

    /**
     * @var Doctrine\REST\Client\ResponseCache
     */
    private $responseCache;

    /**
     * @param \Doctrine\REST\Client\Client $client
     * @param \Doctrine\REST\Client\ResponseCache $responseCache
     * @return void
     */
    public function __construct(Client $client, ResponseCache $responseCache)
    {
        $this->setClient($client);
        $this->setResponseCache($responseCache);
    }

    /**
     * @param \Doctrine\REST\Client\Client $client
     * @return void
     */
    private function setClient(Client $client)
    {
        $this->_client = $client;
    }

    /**
     * @return \Doctrine\REST\Client\Client
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * @param \Doctrine\REST\Client\ResponseCache $responseCache
     * @return void
     */
    private function setResponseCache(ResponseCache $responseCache)
    {
        $this->responseCache = $responseCache;
    }

    /**
     * @return \Doctrine\REST\Client\ResponseCache
     */
    public function getResponseCache()
    {
        return $this->responseCache;
    }

    /**
     * Registers the entity class with the specified name.
     * 
     * @param string $entityClassName
     * @return void
     */
    public function registerEntity($entityClassName)
    {
        $configurationAttributeValues = array_merge($this->getDefaultEntityConfigurationAttributes(), array(
            'class' => $entityClassName,
        ));

        $entityConfiguration = new EntityConfiguration($configurationAttributeValues);

        if (method_exists($entityClassName, 'configure')) {
            call_user_func(array($entityClassName, 'configure'), $entityConfiguration);
        }

        $this->_entityConfigurations[$entityClassName] = $entityConfiguration;

        $entityClassName::setManager($this);
    }

    /**
     * @param string|Doctrine\REST\Client\Entity $entity
     * @return Doctrine\REST\Client\EntityConfiguration
     * @throws \InvalidArgumentException If it could not find entity configuration for the specified entity
     */
    public function getEntityConfiguration($entity)
    {
        $entity = is_object($entity) ? get_class($entity) : $entity;

        if (! isset($this->_entityConfigurations[$entity])) {
            throw new \InvalidArgumentException("Could not find entity configuration for \"{$entity}\"");
        }

        return $this->_entityConfigurations[$entity];
    }

    /**
     * Sets the attributes that will be applied to all new entity configurations created by this manager.
     * 
     * @param array $attributes
     * @return void
     */
    public function setDefaultEntityConfigurationAttributes(array $attributes)
    {
        $this->defaultEntityConfigurationAttributes = $attributes;
    }

    /**
     * Returns the attributes that will be applied to all new entity configurations created by this manager.
     * 
     * @return array
     */
    public function getDefaultEntityConfigurationAttributes()
    {
        return $this->defaultEntityConfigurationAttributes;
    }

    public function entityExists($entity)
    {
        return $this->getEntityIdentifier($entity) ? true : false;
    }

    public function getEntityIdentifier($entity)
    {
        $configuration = $this->getEntityConfiguration($entity);
        $identifierKey = $configuration->getIdentifierKey();
        return $configuration->getValue($entity, $identifierKey);
    }

    /**
     * @param Doctrine\REST\Client\EntityConfiguration $entityConfiguration
     * @param string $httpMethod
     * @param string $url
     * @param array [$arguments = array()]
     * @return Doctrine\REST\Client\Request
     */
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

    /**
     * @param \Doctrine\REST\Client\Request $request
     * @return array|bool
     */
    private function sendRequest(Request $request)
    {
        return $this->getClient()->execute($request);
    }

    /**
     * @param string|Doctrine\REST\Client\Entity $entity
     * @param array $fieldValues
     * @return Doctrine\REST\Client\Entity
     */
    private function createEntityInstance($entity, array $fieldValues)
    {
        //If `$entity` is an instance of `Entity` then we'll hydrate /that/ instance
        $instance = $entity;

        $entityConfiguration = $this->getEntityConfiguration($entity);
        $entityClassName = $entityConfiguration->getClass();

        //If we weren't passed an instance then create/restore one.
        if (!($instance instanceof Entity)) {
            $entityIdFieldName = $entityConfiguration->getIdentifierKey();
            $entityId = $fieldValues[$entityIdFieldName];

            if (isset($this->_identityMap[$entityClassName][$entityId])) {
                $instance = $this->_identityMap[$entityClassName][$entityId];
            } else {
                $instance = $entityConfiguration->newInstance();
            }
        }

        //Hydrate the instance
        foreach ($fieldValues as $fieldName => $fieldValue) {
            $entityConfiguration->setValue($instance, $fieldName, $fieldValue);
        }

        //Add the instance to, or refresh the existing instance in, the identity map
        $entityId = $this->getEntityIdentifier($instance);
        $this->_identityMap[$entityClassName][$entityId] = $instance;

        return $instance;
    }

    /**
     * @param string|Doctrine\REST\Client\Entity $entity
     * @param string [$url = null]
     * @param string [$method = Doctrine\REST\Client\Client::GET]
     * @param array [$arguments = null]
     * @return array|Doctrine\REST\Client\Entity
     */
    public function execute($entity, $url = null, $method = Client::GET, $arguments = null)
    {
        $entityConfiguration = $this->getEntityConfiguration($entity);

        $arguments = is_array($arguments) ? $arguments : array();
        $request = $this->createRequest($entityConfiguration, $method, $url, $arguments);

        $responseIsCacheable = $request->getMethod() === Client::GET && $entityConfiguration->getCacheTtl() > 0;
        $responseCache = $this->getResponseCache();

        $response = false;

        //Attempt to get a cached response
        if ($responseIsCacheable) {
            $response = $responseCache->getIfFresh($entityConfiguration, $request);
        }

        //If there is no cache then make a request to the server
        if (!is_array($response)) {
            $response = $this->sendRequest($request);

            //Attempt to cache the response
            if ($responseIsCacheable) {
                $responseCache->set($entityConfiguration, $request, $response);
            }
        }

        //If the response still isn't an array then return immediately
        if (!is_array($response)) {
            return array();
        }

        $entityName = $entityConfiguration->getName();

        if (isset($response[$entityName]) && is_array($response[$entityName])) {
            $collection = array();

            foreach ($response[$entityName] as $fieldValues) {
                $collection[] = $this->createEntityInstance($entity, $fieldValues);
            }

            return $collection;
        } elseif ($response) {
            return $this->createEntityInstance($entity, $response);
        }

        return array();
    }
}
