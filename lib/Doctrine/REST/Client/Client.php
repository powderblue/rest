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

use CurlHandle;
use Doctrine\REST\Exception\HttpException;
use Exception;
use RuntimeException;

use const CURLOPT_USERAGENT;
use const false;

/**
 * Basic class for issuing HTTP requests via PHP curl.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Client
{
    final public const string POST = 'POST';
    final public const string GET = 'GET';
    final public const string PUT = 'PUT';
    final public const string DELETE = 'DELETE';

    public function post(Request $request)
    {
        $request->setMethod(Client::POST);

        return $this->execute($request);
    }

    public function get(Request $request)
    {
        $request->setMethod(Client::GET);

        return $this->execute($request);
    }

    public function put(Request $request)
    {
        $request->setMethod(Client::PUT);

        return $this->execute($request);
    }

    public function delete(Request $request)
    {
        $request->setMethod(Client::DELETE);

        return $this->execute($request);
    }

    /**
     * @throws RuntimeException If it failed to initialise a cURL session
     * @throws RuntimeException If it failed to set a cURL option
     */
    private function createCurl(Request $request): CurlHandle
    {
        $options = array(
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_USERAGENT => 'Doctrine/REST',
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array('Expect:'),
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        );

        $username = $request->getUsername();
        $password = $request->getPassword();

        if ($username && $password) {
            $options[CURLOPT_USERPWD] = "{$username}:{$password}";
        }

        switch ($request->getMethod()) {
            case self::POST:
            case self::PUT:
                $options[CURLOPT_POST] = 1;
                $options[CURLOPT_POSTFIELDS] = http_build_query($request->getParameters());

                break;

            case self::DELETE:
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';

                break;
        }

        $curl = curl_init();

        if (false === $curl) {
            throw new RuntimeException('Failed to initialise a cURL session');
        }

        if (false === curl_setopt_array($curl, $options)) {
            throw new RuntimeException('Failed to set a cURL option');
        }

        return $curl;
    }

    /**
     * @throws Exception If it failed to perform a cURL session
     * @throws HttpException If the HTTP request was unsuccessful
     */
    public function execute(Request $request)
    {
        $curl = $this->createCurl($request);

        try {
            $result = curl_exec($curl);

            if ($result === false) {
                $errorMessage = curl_errno($curl) . ': ' . curl_error($curl);

                throw new Exception(implode(' | ', [
                    $errorMessage,
                    $request->getUrl(),
                    http_build_query($request->getParameters()),
                    $request->getUsername(),
                ]));
            }

            /** @var int */
            $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($httpStatusCode >= 400 && $httpStatusCode < 600) {
                throw new HttpException('The HTTP request was unsuccessful', $httpStatusCode);
            }

            return $request->getResponseTransformerImpl()->transform($result);
        } finally {
            curl_close($curl);
        }
    }
}
