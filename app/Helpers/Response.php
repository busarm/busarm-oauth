<?php

namespace App\Helpers;

use Busarm\PhpMini\Response as PhpMiniResponse;
use OAuth2\Response as OAuth2Response;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 19/8/2022
 * Time: 11:05 AM
 * 
 * Custom response class to combine OAuth2\Response & Busarm\PhpMini\Response 
 */
class Response extends OAuth2Response
{
    /**
     * Create request using PhpMiniResponse
     *
     * @param PhpMiniResponse $response
     * @return self
     */
    public static function withPhpMiniResponse(PhpMiniResponse $response)
    {
        return new self(
            $response->getParameters(),
            $response->getStatusCode(),
            $response->getHttpHeaders()
        );
    }
}
