<?php

namespace App\Helpers;

use Busarm\PhpMini\Interfaces\RequestInterface;
use OAuth2\Request as OAuth2Request;

/**
 * Custom request class to combine OAuth2\Request & Busarm\PhpMini\Request 
 * 
 * Created by VSCODE.
 * User: Samuel
 * Date: 19/8/2022
 * Time: 11:05 AM
 */
class Request extends OAuth2Request
{
    /**
     * Create request using PhpMiniRequest
     *
     * @param RequestInterface $request
     * @return self
     */
    public static function withPhpMiniRequest(RequestInterface $request)
    {
        return new self(
            $request->query()->all(),
            $request->request()->all(),
            [],
            $request->cookie()->all(),
            $request->file()->all(),
            $request->server()->all(),
            NULL,
            $request->header()->all()
        );
    }
}
