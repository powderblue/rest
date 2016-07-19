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

use Doctrine\REST\Exception\HttpException;

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
    /**#@+
     * HTTP method
     * 
     * @var string
     */
    const POST   = 'POST';
    const GET    = 'GET';
    const PUT    = 'PUT';
    const DELETE = 'DELETE';
    /**#@-*/

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

    private function createCurl(Request $request)
    {
        $options = array(
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array('Expect:'),
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
        curl_setopt_array($curl, $options);

        return $curl;
    }

    public function execute(Request $request)
    {
        $curl = $this->createCurl($request);
        $result = curl_exec($curl);

        if ($result === false) {
            $errorMessage = curl_errno($curl) . ': ' . curl_error($curl);
            curl_close($curl);
            throw new \Exception($errorMessage);
        }

        $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (in_array(substr($httpStatusCode, 0, 1), array(4, 5))) {
            curl_close($curl);
            throw new HttpException('The HTTP request was unsuccessful', $httpStatusCode);
        }

        curl_close($curl);

        return $request->getResponseTransformerImpl()->transform($result);
    }
}
