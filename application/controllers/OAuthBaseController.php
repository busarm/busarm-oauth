<?php

namespace Application\Controllers;

use Application\Services\OAuthStorageService;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\RefreshToken;
use OAuth2\GrantType\UserCredentials;
use OAuth2\Server as OAuth2Server;
use System\Configs;
use  Application\Services\OAuthScopeService;
use System\Utils;

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

    /** @var \OAuth2\Server */
    protected $server;

    /** @var OAuthStorageService */
    protected $storage;


    /** @var \OAuth2\Request */
    protected $request;

    /** @var \OAuth2\Response */
    protected $response;

    
    /** @var array Current client info*/
    private $currentClient;

    /** @var array Current client info*/
    private $currentToken;

    /** @var array Current acccess type */
    private $currentAccessType;

    /**
     * Server constructor.
     * @param boolean $validateAccess Validate access to server
     * @param boolean $useJWT Use JWT Token
     * @param boolean $isCLI Is CLI Application
     */
    protected function __construct($validateAccess = false, $useJWT = false, $isCLI = false)
    {
        // Check cli
        if ($isCLI && !is_cli()) {
            app()->showMessage(403, false, 'Unauthorized request');
        }

        // Create request & response objects
        $this->request = \OAuth2\Request::createFromGlobals();
        $this->response = new \OAuth2\Response();

        // Create PDO - MYSQL DB Storage
        $this->storage = new OAuthStorageService(array('dsn' => sprintf("mysql:dbname=%s;host=%s", DB_NAME, DB_HOST), 'username' => DB_USER, 'password' => DB_PASS));

        // Create server without implicit
        $this->server = new OAuth2Server($this->storage, array(
            'access_lifetime' => ENVIRONMENT == ENV_DEV ? 86400 * 30 : 86400,
            'refresh_token_lifetime' => ENVIRONMENT == ENV_DEV ? 86400 * 90 : 86400 * 30,
            'auth_code_lifetime' => 3600, //1 hour
            'allow_credentials_in_request_body' => true,
            'allow_implicit' => false,
            'use_jwt_access_tokens' => $useJWT,
            'store_encrypted_token_string' => false,
            'issuer' => BASE_URL
        ));

        // User Credentials grant type
        $this->server->addGrantType(new UserCredentials($this->storage));

        // Client Credentials grant type
        $this->server->addGrantType(new ClientCredentials($this->storage));

        // Authorization Code grant type
        $this->server->addGrantType(new AuthorizationCode($this->storage));

        // Refresh Token grant type - the refresh token grant request will have a "refresh_token" field
        $this->server->addGrantType(new RefreshToken($this->storage, array(
            'always_issue_new_refresh_token' => true
        )));

        // Set up Scopes with db scope data
        $this->server->setScopeUtil(new OAuthScopeService($this->storage->getAllScopesList(), $this->storage->getDefaultScope()));

        // Validate Access
        if ($validateAccess && !$this->validateClient() && !$this->validateAccessToken()) {
            $this->response->setStatusCode(401);
            $this->response->send();
            die;
        }
    }

    /** 
     * Validate Scope or Permission
     * @param string|array $scope
     * @return bool
     */
    protected function validatePermission($scope = null)
    {
        $scope = is_array($scope) ? Utils::implode($scope) : $scope;
        if (($this->currentAccessType === self::ACCESS_TYPE_TOKEN && !$this->server->getScopeUtil()->checkScope($scope, $this->getCurrentToken('scope')))) {
            $this->response->setStatusCode(403);
            $this->response->setParameters($this->error("Access denied. Token doesn't have required '$scope' scope(s)", 'invalid_scope'));
            $this->response->send();
            die();
        } else if (($this->currentAccessType === self::ACCESS_TYPE_CLIENT && !$this->server->getScopeUtil()->checkScope($scope, $this->getCurrentClient('scope')))) {
            $this->response->setStatusCode(403);
            $this->response->setParameters($this->error("Access denied. Client doesn't have required '$scope' scope(s)", 'invalid_scope'));
            $this->response->send();
            die();
        }
        return true;
    }

    /** 
     * Validate Access Token
     * @return bool
     */
    protected function validateAccessToken()
    {
        if ($result = $this->server->getAccessTokenData($this->request, $this->response)) {
            $this->currentToken = $result;
            $this->currentClient = $this->storage->getClientDetails($this->getCurrentToken('client_id') ?? null);
            $this->currentAccessType = self::ACCESS_TYPE_TOKEN;
            return true;
        } else if (empty($this->response->getParameters())) {
            $this->response->setParameters($this->error('Unauthorized aceess', 'invalid_token'));
            return false;
        }
    }

    /** 
     * Validate Client credentials
     * @return bool
     */
    protected function validateClient()
    {
        // Get credentials from Authorization header
        $authorization = $this->request->headers("authorization", '');
        $credentials = strpos($authorization, 'Basic ') !== false ? Utils::explode(base64_decode(str_replace('Basic ', '', $authorization)), ':') : [];
        $client_id = count($credentials) == 2 ? $credentials[0] : null;
        $client_secret = count($credentials) == 2 ? $credentials[1] : null;

        // Get from header or body
        if (empty($client_id) && empty($client_secret)) {
            $client_id = $this->request->headers("client_id") ?? $this->request->request("client_id");
            $client_secret = $this->request->headers("client_secret") ?? $this->request->request("client_secret");
        }

        if ($this->storage->checkClientCredentials($client_id, $client_secret)) {

            $this->currentClient = $this->storage->getClientDetails($client_id);
            if (!empty($this->currentClient)) {
                $this->currentAccessType = self::ACCESS_TYPE_CLIENT;
                return true;
            } else {
                $this->response->setParameters($this->error('Failed to get client details', 'invalid_client'));
                return false;
            }
        } else {
            $this->response->setParameters($this->error('Unauthorized aceess', 'invalid_client'));
            return false;
        }
    }

    /**
     * Get the value of currentClient
     */
    public function getCurrentClient($param = null)
    {
        return $this->currentClient ? ($param ? $this->currentClient[$param] ?? null : $this->currentClient) : null;
    }

    /**
     * Get the value of currentToken
     */
    public function getCurrentToken($param = null)
    {
        return $this->currentToken ? ($param ? $this->currentToken[$param] ?? null : $this->currentToken) : null;
    }

    /**
     * Start Login session
     *
     * @param string $user User Id
     * @param string $duration Session duration in seconds. default = 1hr
     * @return void
     */
    public function startLoginSession($user, $duration = 3600)
    {
        if (!$user) return;
        return Utils::setCookie('login_user', $user, $duration, IPADDRESS);
    }

    /**
     * Clear Login session
     * 
     * @return void
     */
    public function clearLoginSession()
    {
        Utils::deleteCookie('login_user');
    }

    /**
     * Get Login User
     *
     * @return string|bool
     */
    public function getLoginUser()
    {
        return Utils::getCookie('login_user', IPADDRESS);
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
