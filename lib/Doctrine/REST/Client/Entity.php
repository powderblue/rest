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
 * Abstract entity class for REST entities to extend from to give ActiveRecord
 * style interface for working with REST services.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
abstract class Entity
{
    /**
     * @var array
     */
    private static $managers = array();

    /**
     * Sets the manager for the called class.
     *
     * @param \Doctrine\REST\Client\Manager $manager
     * @return void
     */
    public static function setManager(Manager $manager)
    {
        self::$managers[get_called_class()] = $manager;
    }

    /**
     * Returns TRUE if the called class has a manager, or FALSE otherwise.
     *
     * @return bool
     */
    public static function hasManager()
    {
        return array_key_exists(get_called_class(), self::$managers);
    }

    /**
     * Removes the manager for the called class.
     *
     * @return void
     */
    public static function removeManager()
    {
        unset(self::$managers[get_called_class()]);
    }

    /**
     * Returns the manager for the called `Entity` subclass, or the manager for `Entity`, if it has one.
     *
     * @return Doctrine\REST\Client\Entity
     * @throws \RuntimeException If the called class does not have its own entity manager and there is no default
     * @todo Remove the fallback?
     */
    public static function getManager()
    {
        $calledClassName = get_called_class();

        if (!static::hasManager()) {
            $thisClassName = __CLASS__;

            if (!array_key_exists($thisClassName, self::$managers)) {
                throw new \RuntimeException("{$calledClassName} does not have its own entity manager and there is no default");
            }

            return self::$managers[$thisClassName];
        }

        return self::$managers[$calledClassName];
    }

    public function toArray()
    {
        return get_object_vars($this);
    }

    public function exists()
    {
        return static::getManager()->entityExists($this);
    }

    public function getIdentifier()
    {
        return static::getManager()->getEntityIdentifier($this);
    }

    public static function generateUrl(array $options = array())
    {
        $configuration = static::getManager()->getEntityConfiguration(get_called_class());

        return $configuration->generateUrl($options);
    }

    public static function find($id, $action = null)
    {
        return static::getManager()->execute(
            get_called_class(),
            static::generateUrl(get_defined_vars()),
            Client::GET
        );
    }

    public static function findAll($action = null, $parameters = null)
    {
        return static::getManager()->execute(
            get_called_class(),
            static::generateUrl(get_defined_vars()),
            Client::GET,
            $parameters
        );
    }

    public function save()
    {
        $parameters = $this->toArray();
        $exists = $this->exists();
        $method = $exists ? Client::POST : Client::PUT;
        $id = $exists ? $this->getIdentifier() : null;
        $path = static::generateUrl(get_defined_vars());

        return static::getManager()->execute($this, $path, $method, $parameters);
    }

    public function delete($action = null)
    {
        $id = $this->getIdentifier();

        return static::getManager()->execute(
            $this,
            static::generateUrl(get_defined_vars()),
            Client::DELETE
        );
    }

    public function post($action = null)
    {
        $id = $this->getIdentifier();

        return static::getManager()->execute(
            $this,
            static::generateUrl(get_defined_vars()),
            Client::POST,
            $this->toArray()
        );
    }

    public function get($action = null)
    {
        return static::getManager()->execute(
            $this,
            static::generateUrl(get_defined_vars()),
            Client::GET,
            $this->toArray()
        );
    }

    public function put($action = null)
    {
        return static::getManager()->execute(
            $this,
            static::generateUrl(get_defined_vars()),
            Client::PUT,
            $this->toArray()
        );
    }

    public static function execute($method, $action, $parameters = null)
    {
        return static::getManager()->execute(
            get_called_class(),
            static::generateUrl(get_defined_vars()),
            $method,
            $parameters
        );
    }
}
