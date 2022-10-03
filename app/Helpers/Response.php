<?php

namespace App\Helpers;

use Busarm\PhpMini\Interfaces\ResponseInterface;
use OAuth2\Response as OAuth2Response;

/**
 * Custom response class to combine OAuth2\Response & Busarm\PhpMini\Response 
 * 
 * Created by VSCODE.
 * User: Samuel
 * Date: 19/8/2022
 * Time: 11:05 AM
 */
class Response extends OAuth2Response
{
    /**
     * Create request using PhpMiniResponse
     *
     * @param ResponseInterface $response
     * @return self
     */
    public static function withPhpMiniResponse(ResponseInterface $response)
    {
        return new self(
            $response->getParameters(),
            $response->getStatusCode(),
            $response->getHttpHeaders()
        );
    }
}
