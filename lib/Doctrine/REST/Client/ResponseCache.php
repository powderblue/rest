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
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Dan Bettles <danbettles@yahoo.co.uk>
 * @todo        Create a method that removes only expired cache files
 */
class ResponseCache
{
    /**
     * @var string
     */
    const CACHE_FILE_EXT = '.tmp';

    /**
     * @var string
     */
    private $dir;

    /**
     * @param string $dir
     */
    public function __construct($dir)
    {
        $this->setDir($dir);
    }

    /**
     * Sets the path of the directory in which cache files are, or will be, stored.
     * 
     * @param string $dir
     * @return void
     * @throws \RuntimeException If the directory does not exist
     */
    private function setDir($dir)
    {
        if (!is_dir($dir)) {
            throw new \RuntimeException('The directory does not exist');
        }

        $this->dir = rtrim($dir, '/');
    }

    /**
     * Returns the path of the directory in which cache files are, or will be, stored.
     * 
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * Returns the path of the directory in which cache files associated with the specified entity class will be stored.
     * 
     * @param string $entityClassName
     * @return string
     */
    private function createCacheFileDirPath($entityClassName)
    {
        return $this->getDir() . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $entityClassName);
    }

    /**
     * Returns the filename of the file in which a response to the specified request is, or will be, stored.
     * 
     * @param string $entityClassName
     * @param \Doctrine\REST\Client\Request $request
     * @return string
     */
    private function createCacheFilePath($entityClassName, Request $request)
    {
        $basename = $request->getRequestId() . self::CACHE_FILE_EXT;
        return $this->createCacheFileDirPath($entityClassName) . DIRECTORY_SEPARATOR . $basename;
    }

    /**
     * Stores a response in the cache.
     * 
     * @param \Doctrine\REST\Client\EntityConfiguration $entityConfiguration
     * @param \Doctrine\REST\Client\Request $request
     * @param array $response
     * @return void
     * @throws \RuntimeException If it failed to create the directory in which cache files would have been stored
     * @throws \RuntimeException If it failed to create a cache file
     */
    public function set(EntityConfiguration $entityConfiguration, Request $request, array $response)
    {
        $filename = $this->createCacheFilePath($entityConfiguration->getClass(), $request);
        $dir = dirname($filename);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0750, true)) {
                throw new \RuntimeException("Failed to create directory \"{$dir}\"");  //@todo Test this
            }
        }

        if (file_put_contents($filename, serialize($response)) === false) {
            throw new \RuntimeException("Failed to create cache file \"{$filename}\"");  //@todo Test this
        }
    }

    /**
     * Returns the parsed contents of the file with the specified filename.
     * 
     * @param string $filename
     * @return mixed
     * @throws \RuntimeException If it failed to get the contents of the specified filename
     */
    private function fileParseContents($filename)
    {
        $contents = file_get_contents($filename);

        if ($contents === false) {
            throw new \RuntimeException("Failed to get the contents of the file \"{$filename}\"");  //@todo Test this
        }

        return unserialize($contents);
    }

    /**
     * Returns the content associated with the arguments, if it hasn't expired, or FALSE otherwise.
     * 
     * @param \Doctrine\REST\Client\EntityConfiguration $entityConfiguration
     * @param \Doctrine\REST\Client\Request $request
     * @return mixed
     */
    public function getIfFresh(EntityConfiguration $entityConfiguration, Request $request)
    {
        $filename = $this->createCacheFilePath($entityConfiguration->getClass(), $request);

        if (!is_file($filename)) {
            return false;
        }

        $ttl = $entityConfiguration->getCacheTtl() ?: 0;

        //If the cache TTL is (now) zero then caching is disabled for this entity
        if ($ttl <= 0) {
            //@todo Remove the cache file
            return false;
        }

        if (filemtime($filename) < (time() - $ttl)) {
            //@todo Remove the cache file
            return false;
        }

        return $this->fileParseContents($filename);
    }

    /**
     * Removes all cache files associated with each of the specified entity classes.
     * 
     * Calling this is a quick-and-dirty way of performing housekeeping.
     * 
     * @param array $entityClassNames
     * @return void
     */
    public function emptyByEntityClassNames(array $entityClassNames)
    {
        foreach ($entityClassNames as $entityClassName) {
            $cacheFileDir = $this->createCacheFileDirPath($entityClassName);
            $globPattern = $cacheFileDir . DIRECTORY_SEPARATOR . '*' . self::CACHE_FILE_EXT;
            $filenames = glob($globPattern);

            if ($filenames === false) {
                continue;
            }

            foreach ($filenames as $filename) {
                unlink($filename);
            }
        }
    }
}
