<?php

namespace App\Helpers;

use Busarm\PhpMini\Request as PhpMiniRequest;
use OAuth2\Request as OAuth2Request;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 19/8/2022
 * Time: 11:05 AM
 * 
 * Custom request class to combine OAuth2\Request & Busarm\PhpMini\Request 
 */
class Request extends OAuth2Request
{
    /**
     * Create request using PhpMiniRequest
     *
     * @param PhpMiniRequest $request
     * @return self
     */
    public static function withPhpMiniRequest(PhpMiniRequest $request)
    {
        return new self(
            $request->getQueryList(),
            $request->getRequestList(),
            $request->getAttributeList(),
            $request->getCookieList(),
            $request->getFileList(),
            $request->getServerList(),
            $request->getContent(),
            $request->getHeaderList()
        );
    }
}
