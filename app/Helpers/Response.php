<?php

namespace App\Helpers;

use InvalidArgumentException;
use OAuth2\ResponseInterface;
use System\Response as SystemResponse;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 19/8/2022
 * Time: 11:05 AM
 */
class Response extends SystemResponse implements ResponseInterface
{

    /**
     * @param int $statusCode
     * @param string $error
     * @param string $errorDescription
     * @param string $errorUri
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function setError($statusCode, $error, $errorDescription = null, $errorUri = null)
    {
        $parameters = array(
            'error' => $error,
            'error_description' => $errorDescription,
        );

        if (!is_null($errorUri)) {
            if (strlen($errorUri) > 0 && $errorUri[0] == '#') {
                // we are referencing an oauth bookmark (for brevity)
                $errorUri = 'http://tools.ietf.org/html/rfc6749' . $errorUri;
            }
            $parameters['error_uri'] = $errorUri;
        }

        $httpHeaders = array(
            'Cache-Control' => 'no-store'
        );

        $this->setStatusCode($statusCode);
        $this->addParameters($parameters);
        $this->addHttpHeaders($httpHeaders);

        if (!$this->isClientError() && !$this->isServerError()) {
            throw new InvalidArgumentException(sprintf('The HTTP status code is not an error ("%s" given).', $statusCode));
        }
    }

    /**
     * @param int $statusCode
     * @param string $url
     * @param string $state
     * @param string $error
     * @param string $errorDescription
     * @param string $errorUri
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function setRedirect($statusCode, $url, $state = null, $error = null, $errorDescription = null, $errorUri = null)
    {
        if (empty($url)) {
            throw new InvalidArgumentException('Cannot redirect to an empty URL.');
        }

        $parameters = array();

        if (!is_null($state)) {
            $parameters['state'] = $state;
        }

        if (!is_null($error)) {
            $this->setError(400, $error, $errorDescription, $errorUri);
        }
        $this->setStatusCode($statusCode);
        $this->addParameters($parameters);

        if (count($this->parameters) > 0) {
            // add parameters to URL redirection
            $parts = parse_url($url);
            $sep = isset($parts['query']) && !empty($parts['query']) ? '&' : '?';
            $url .= $sep . http_build_query($this->parameters);
        }

        $this->addHttpHeaders(array('Location' =>  $url));

        if (!$this->isRedirection()) {
            throw new InvalidArgumentException(sprintf('The HTTP status code is not a redirect ("%s" given).', $statusCode));
        }
    }
}
