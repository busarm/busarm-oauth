<?php

namespace Application\Controllers;

use Application\Services\OAuthStorageService;
use Application\Services\OAuthService;
use Application\Services\AuthService;
use Application\Helpers\Utils;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 1:17 AM
 */
class OAuthBaseController
{
    const ACCESS_TYPE_CLIENT = 'client';
    const ACCESS_TYPE_TOKEN = 'token';

    /** @var OAuthService */
    protected $oauth;

    /** @var AuthService */
    protected $auth;

    /**
     * Server constructor.
     * @param boolean $validateAccess Validate access to server
     * @param boolean $useJWT Use JWT Token
     * @param boolean $isCLI Is CLI Application
     */
    protected function __construct($validateAccess = false,  $isCLI = false)
    {
        // Check cli
        if ($isCLI && !is_cli()) {
            app()->showMessage(403, false, 'Unauthorized request');
        }

        // Create OAuth Service
        $this->oauth = OAuthService::getInstance();
        
        // Create Auth Service
        $this->auth = AuthService::getInstance();

        // Validate Access
        if ($validateAccess && !$this->oauth->validateClient() && !$this->oauth->validateAccessToken()) {
            app()->sendHttpResponse(401, $this->oauth->response->getParameters());
        }
    }

    /**
     * Get success response
     */
    public function success($data)
    {
        if (is_string($data)) {
            return ['success' => true, 'message' => $data];
        } else {
            return ['success' => true, 'data' => $data];
        }
    }

    /**
     * Get success response
     */
    public function error($message, $type = 'unexpected_error')
    {
        return ['success' => false, 'error' => $type, 'error_description' => $message];
    }
}
