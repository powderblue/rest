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

use Doctrine\REST\Client\URLGenerator\StandardURLGenerator;
use Doctrine\REST\Client\ResponseTransformer\StandardResponseTransformer;
use Doctrine\REST\Client\URLGenerator\AbstractURLGenerator;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;

/**
 * Entity configuration class holds all the configuration information for a PHP5
 * object entity that maps to a REST service.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class EntityConfiguration
{
//    /**
//     * @var Doctrine\REST\Client\EntityConfiguration
//     */
//    private $_prototype;
//
    /**
     * @var ReflectionClass
     */
    private $_reflection;

    /**
     * @var array
     */
    private $_reflectionProperties = array();

    /**
     * @var array
     */
    private $_attributes = array(
        'class' => null,
        'url' => null,
        'name' => null,
        'username' => null,
        'password' => null,
        'identifierKey' => null,
        'responseType' => null,
        'urlGeneratorImpl' => null,
        'responseTransformerImpl' => null,
        'cacheTtl' => null,
    );

    /**
     * @param string|array $attributeValues The entity class name or an array of attribute values including the entity class name
     * @return void
     */
    public function __construct($attributeValues)
    {
        $effectiveAttributeValues = is_array($attributeValues) ? $attributeValues : array('class' => $attributeValues);

        if (!array_key_exists('class', $effectiveAttributeValues)) {
            throw new \InvalidArgumentException('The entity class name was not specified');
        }

        $defaultAttributeValues = array(
            'name' => $this->deriveEntityNameFromClassName($effectiveAttributeValues['class']),
            'identifierKey' => 'id',
            'responseType' => 'xml',
            'urlGeneratorImpl' => new StandardURLGenerator($this),
            'responseTransformerImpl' => new StandardResponseTransformer($this),
        );

        $this->setAttributeValues(array_merge($defaultAttributeValues, $effectiveAttributeValues));

        $this->_reflection = new \ReflectionClass($this->getClass());

        foreach ($this->_reflection->getProperties() as $reflectionProperty) {
            if ($reflectionProperty->getDeclaringClass()->getName() !== $this->getClass()) {
                continue;
            }

            $propertyName = $reflectionProperty->getName();
            $reflectionProperty->setAccessible(true);
            $this->_reflectionProperties[$propertyName] = $reflectionProperty;
        }
    }

    public function getReflection()
    {
        return $this->_reflection;
    }

    public function getReflectionProperties()
    {
        return $this->_reflectionProperties;
    }

    public function getProperties()
    {
        return array_keys($this->_reflectionProperties);
    }

    public function setValue($entity, $field, $value)
    {
        if (isset($this->_reflectionProperties[$field])) {
            $this->_reflectionProperties[$field]->setValue($entity, $value);
        } else {
            $entity->$field = $value;
        }
    }

    public function getValue($entity, $field)
    {
        return $this->_reflectionProperties[$field]->getValue($entity);
    }

    public function generateUrl(array $options)
    {
        return $this->_attributes['urlGeneratorImpl']->generate($options);
    }

    /**
     * Returns TRUE if the attribute with the specified name exists, or FALSE otherwise.
     *
     * @param string $name
     * @return bool
     */
    private function hasAttribute($name)
    {
        return array_key_exists($name, $this->_attributes);
    }

    /**
     * Throws an exception if the attribute with the specified name does not exist, or returns TRUE if it does.
     *
     * @param string $name
     * @return bool
     * @throws OutOfBoundsException If the specified attribute does not exist
     */
    private function assertHasAttribute($name)
    {
        if (!$this->hasAttribute($name)) {
            throw new \OutOfBoundsException("The attribute \"{$name}\" does not exist");
        }

        return true;
    }

    /**
     * Sets the value of the attribute with the specified name.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws OutOfBoundsException If the specified attribute does not exist
     */
    private function setAttributeValue($name, $value)
    {
        $this->assertHasAttribute($name);
        $this->_attributes[$name] = $value;
    }

    /**
     * Returns the value of the attribute with the specified name.
     *
     * @param string $name
     * @return mixed
     * @throws OutOfBoundsException If the specified attribute does not exist
     */
    private function getAttributeValue($name)
    {
        $this->assertHasAttribute($name);
        return $this->_attributes[$name];
    }

    /**
     * Sets the value of each of the attributes named in the specified array.
     *
     * @param array $attributes
     * @return void
     */
    public function setAttributeValues(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            //Attempt to use a setter, which may perform filtering, for example, rather than setting the value of the
            //attribute directly
            $setterName = 'set' . ucfirst($name);
            $this->$setterName($value);
        }
    }

    /**
     * Returns an array containing all attribute values.
     *
     * @return array
     */
    public function getAttributeValues()
    {
        return $this->_attributes;
    }

    /**
     * Implements setters and getters for the attributes.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws BadMethodCallException If the called method is not implemented
     */
    public function __call($name, array $arguments)
    {
        $matches = array();
        $matched = preg_match('/^([sg]et)([A-Z].*)$/', $name, $matches);

        if ($matched) {
            $subject = lcfirst($matches[2]);

            switch ($matches[1]) {
                case 'set':
                    return $this->setAttributeValue($subject, reset($arguments));
                case 'get':
                    return $this->getAttributeValue($subject);
            }
        }

        throw new \BadMethodCallException("The method \"{$name}\" is not implemented");
    }

    /**
     * Attribute setter, sets the base URL of the resource.
     *
     * @param string $url
     * @return void
     */
    public function setUrl($url)
    {
        $this->setAttributeValue('url', rtrim($url, '/'));
    }

    /**
     * Attribute setter, sets the URL generator for the entity.
     *
     * For backwards compatibility.  The case used in the name of this method is inconsistent with the case used in the
     * name of the attribute it sets.
     *
     * @param Doctrine\REST\Client\URLGenerator\AbstractURLGenerator $value
     * @return void
     */
    public function setURLGeneratorImpl(AbstractURLGenerator $value)
    {
        $this->setAttributeValue('urlGeneratorImpl', $value);
    }

    /**
     * Attribute getter, returns the URL generator for the entity.
     *
     * For backwards compatibility.  The case used in the name of this method is inconsistent with the case used in the
     * name of the attribute it gets.
     *
     * @return Doctrine\REST\Client\URLGenerator\AbstractURLGenerator
     */
    public function getURLGeneratorImpl()
    {
        return $this->getAttributeValue('urlGeneratorImpl');
    }

    /**
     * @param int $ttl
     * @return void
     * @throws \InvalidArgumentException If the TTL is not greater than or equal to zero
     */
    public function setCacheTtl($ttl)
    {
        if (!(is_numeric($ttl) && $ttl >= 0)) {
            throw new \InvalidArgumentException('The TTL is not greater than or equal to zero');
        }

        $this->setAttributeValue('cacheTtl', $ttl);
    }

    /**
     * Returns a new instance of the entity class this configuration is associated with.
     *
     * @return \Doctrine\REST\Client\Entity
     */
    public function newInstance()
    {
//        if ($this->_prototype === null) {
//            $this->_prototype = unserialize(sprintf(
//                'O:%d:"%s":0:{}',
//                strlen($this->_attributes['class']),
//                $this->_attributes['class']
//            ));
//        }
//        return clone $this->_prototype;
        $entityClassName = $this->getClass();
        return new $entityClassName();
    }

    /**
     * Derives the name of an entity from its class name.
     *
     * @param string $entityClassName
     * @return string
     */
    private function deriveEntityNameFromClassName($entityClassName)
    {
        $nameParts = explode('\\', $entityClassName);
        $lastNamePart = array_pop($nameParts);
        $singularName = strtolower($lastNamePart);

        $pluralName = InflectorFactory::create()->build()->pluralize($singularName);

        return $pluralName;
    }
}
