<?php

namespace App\Services;

use App\Exceptions\AuthorizationException;
use System\Traits\Singleton;
use OAuth2\Server;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\RefreshToken;
use OAuth2\GrantType\UserCredentials;
use System\Interfaces\SingletonInterface;
use App\Helpers\Utils;
use System\Env;

class OAuthService implements SingletonInterface
{
    use Singleton;

    const ACCESS_TYPE_CLIENT = 'client';
    const ACCESS_TYPE_TOKEN = 'token';

    /** @var \OAuth2\Request */
    public $request;

    /** @var \OAuth2\Response */
    public $response;

    /** @var \OAuth2\Server */
    public $server;

    /** @var OAuthStorageService */
    public $storage;

    /** @var array Current client info*/
    private $authClient;

    /** @var array Current client info*/
    private $authToken;

    /** @var array Current acccess type */
    private $accessType;

    public function __construct()
    {
        // Create request & response objects
        $this->request = \OAuth2\Request::createFromGlobals();
        $this->response = new \OAuth2\Response();

        // Create PDO - MYSQL DB Storage
        $this->storage = new OAuthStorageService(array('dsn' => sprintf("mysql:dbname=%s;host=%s", DB_NAME, DB_HOST), 'username' => DB_USER, 'password' => DB_PASS));

        // Create server without implicit
        $this->server = new Server($this->storage, array(
            'access_lifetime' => (app()->env == Env::LOCAL || app()->env == Env::DEV) ? 86400 * 30 : 86400,
            'refresh_token_lifetime' => (app()->env == Env::LOCAL || app()->env == Env::DEV) ? 86400 * 90 : 86400 * 30,
            'auth_code_lifetime' => 3600, //1 hour
            'allow_credentials_in_request_body' => true,
            'allow_implicit' => false,
            'use_jwt_access_tokens' => true,
            'store_encrypted_token_string' => false,
            'issuer' => app()->request->baseUrl()
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
    }


    /** 
     * Validate Scope or Permission
     * @param string|array $scope
     * @return bool
     */
    public function validatePermission($scope = null)
    {
        $scope = is_array($scope) ? Utils::implode($scope) : $scope;
        switch ($this->accessType ?? '') {
            case self::ACCESS_TYPE_TOKEN: {
                    if (!$this->server->getScopeUtil()->checkScope($scope, $this->getAuthToken('scope'))) {
                        throw new AuthorizationException("Access denied. Token doesn't have required '$scope' scope(s)");
                    }
                    return true;
                }
            case self::ACCESS_TYPE_CLIENT: {
                    if (!$this->server->getScopeUtil()->checkScope($scope, $this->getAuthClient('scope'))) {
                        throw new AuthorizationException("Access denied. Client doesn't have required '$scope' scope(s)");
                    }
                    return true;
                }
        }
        return false;
    }

    /** 
     * Validate Access Token
     * @return bool
     */
    public function validateAccessToken()
    {
        if (!empty($this->authToken) && $this->accessType == self::ACCESS_TYPE_TOKEN) return true;
        else if ($result = $this->server->getAccessTokenData($this->request, $this->response)) {
            $this->authToken = $result;
            $this->authClient = $this->storage->getCustomClientDetails($this->getAuthToken('client_id') ?? null, null, true);
            $this->accessType = self::ACCESS_TYPE_TOKEN;
            return true;
        }
        return false;
    }

    /** 
     * Validate Client credentials
     * @return bool
     */
    public function validateClient()
    {
        if (!empty($this->authClient) && $this->accessType == self::ACCESS_TYPE_CLIENT) return true;

        // Get credentials from Authorization header
        $authorization = app()->request->headers("authorization", '');
        $credentials = strpos($authorization, 'Basic ') !== false ? Utils::explode(base64_decode(str_replace('Basic ', '', $authorization)), ':') : [];
        $clientId = count($credentials) == 2 ? $credentials[0] : null;
        $clientSecret = count($credentials) == 2 ? $credentials[1] : null;

        // Get from header or body
        if (empty($clientId) && empty($clientSecret)) {
            $clientId = app()->request->headers("client_id") ?? app()->request->request("client_id");
            $clientSecret = app()->request->headers("client_secret") ?? app()->request->request("client_secret");
        }

        if ($this->storage->checkClientCredentials($clientId, $clientSecret)) {
            $this->authClient = $this->storage->getCustomClientDetails($clientId, null, true);
            if (!empty($this->authClient)) {
                $this->accessType = self::ACCESS_TYPE_CLIENT;
                return true;
            }
        }
        return false;
    }

    /**
     * Get the value of authClient
     */
    public function getAuthClient($param = null)
    {
        return $this->authClient ? ($param ? $this->authClient[$param] ?? null : $this->authClient) : null;
    }

    /**
     * Get the value of authToken
     */
    public function getAuthToken($param = null)
    {
        return $this->authToken ? ($param ? $this->authToken[$param] ?? null : $this->authToken) : null;
    }

    /**
     * Get the value of accessType
     */
    public function getAccessType()
    {
        return $this->accessType;
    }
}
