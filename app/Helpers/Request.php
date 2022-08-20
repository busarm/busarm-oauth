<?php

namespace App\Helpers;

use OAuth2\RequestInterface;
use System\Request as SystemRequest;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 19/8/2022
 * Time: 11:05 AM
 * 
 * Custom request class to combine OAuth2\Request & System\Request 
 */
class Request extends SystemRequest implements RequestInterface
{
    /**
     * @return array
     */
    public function getAllQueryParameters()
    {
        return $this->query;
    }
}
