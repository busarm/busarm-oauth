<?php

namespace Application\Controllers\HTTP;

use Application\Controllers\OAuthBaseController;
use phpseclib\Crypt\RSA;
use  Application\Services\OAuthScopeService;
use System\Utils;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 12:20 PM
 **/
class Resources extends OAuthBaseController
{

    public function __construct()
    {
        parent::__construct(true, true);
    }

    /**
     * Get all scopes
     * */
    public function scopes()
    {
        if ($this->validateAccessToken()) {
            $this->response->setParameters(array('success' => true, 'data' => OAuthScopeService::$allScopes));
        }
        $this->response->send();
        die;
    }

    /**
     * Delete Access token and refresh token
     */
    public function removeAccess()
    {
        // Validate permission
        $this->validatePermission([OAuthScopeService::SCOPE_SYSTEM, OAuthScopeService::SCOPE_SYSTEM]);

        $access_token = $this->request->request('access_token');
        $refresh_token = $this->request->request('refresh_token');
        $done = false;
        if (!empty($access_token)) {
            $done = $this->storage->unsetAccessToken($access_token);
        }
        if (!empty($refresh_token)) {
            $done = $this->storage->unsetRefreshToken($refresh_token);
        }
        if ($done) {
            $this->response->setParameters($this->success('Successfully cleared access'));
        } else {
            $this->response->setParameters($this->error('Failed to clear access'));
        }
        $this->response->send();
        die;
    }

    /**
     * Obtain user info
     */
    public function getUser()
    {
        $user_id = $this->request->request('user_id') ?? $this->request->query('user_id');

        // If user_id requested - Validate permission
        if (!empty($user_id) && $this->validatePermission([OAuthScopeService::SCOPE_SYSTEM, OAuthScopeService::SCOPE_ADMIN])) {
            $user = $this->storage->getCustomUser($user_id);
        } else {
            $user =  $this->storage->getCustomUserWIthClaims($this->getCurrentToken('user_id'), array_keys(OAuthScopeService::findOpenIdScope($this->getCurrentToken('scope')) ?: []), false);
        }

        if (!empty($user)) {
            $this->response->setParameters($this->success($user));
        } else {
            $this->response->setStatusCode(404);
            $this->response->setParameters($this->error('Users does not exist', 'invalid_user'));
        }
        $this->response->send();
        die;
    }

    /**
     * Get Bulk users info
     */
    public function fetchUsers()
    {
        // Validate permission
        $this->validatePermission([OAuthScopeService::SCOPE_SYSTEM, OAuthScopeService::SCOPE_ADMIN]);

        if (!empty($user_ids = ($this->request->request('user_ids') ?? $this->request->query('user_ids')))) {
            $users = $this->storage->getMultipleUsers(Utils::explode($user_ids, ','));
            if (!empty($users)) {
                $this->response->setParameters($this->success($users));
            } else {
                $this->response->setStatusCode(404);
                $this->response->setParameters($this->error('User(s) does not exist', 'invalid_users'));
            }
        } else {
            $this->response->setStatusCode(400);
            $this->response->setParameters($this->error('User(s) not specified', 'invalid_request'));
        }
        $this->response->send();
        die;
    }

    /**
     * Create OAuth User
     */
    public function createUser()
    {
        // Validate permission
        $this->validatePermission([OAuthScopeService::SCOPE_SYSTEM, OAuthScopeService::SCOPE_ADMIN]);

        $name = $this->request->request('name');
        $email = $this->request->request('email');
        $phone = $this->request->request('phone');
        $dial_code = $this->request->request('dial_code');
        $password = $this->request->request('password');
        $scope = $this->request->request('scope');
        $force = $this->request->request('force') ?: false;

        // Validate Parameters
        if (!$email || !$phone || !$dial_code || !$password || !$scope) {
            $this->response->setStatusCode(400);
            $this->response->setParameters($this->error("Invalid Parameters. 'email', 'phone', 'dial_code', 'password', and 'scope' are required", 'invalid_request'));
            $this->response->send();
            die;
        }

        //Check if scope is valid
        $scope = array_keys(OAuthScopeService::findScope($scope) ?: []);
        if (empty($scope)) {
            $this->response->setStatusCode(400);
            $this->response->setParameters($this->error('Invalid requested scope(s)', 'invalid_scopes'));
            $this->response->send();
            die;
        }
        // Add claim scopes if openid scope is included
        else if (in_array(OAuthScopeService::SCOPE_OPENID, $scope)) {
            array_merge($scope, OAuthScopeService::CLAIM_SCOPES);
        }

        // Process params
        $scope = Utils::implode($scope);
        $user_id = sha1(uniqid(!empty($email) ? $email : (!empty($phone) ? $phone : $name)));

        // Check if user exists
        if ($email && ($user = $this->storage->getUser($email))) {
            if ($force) {
                $this->response->setParameters($this->error(sprintf("User with email %s already exists", $email), 'duplicate_user'));
            } else {
                $this->response->setParameters($this->success(['user_id' => $user['user_id'], 'existing' => true]));
            }
        } else {
            // Insert User
            $result = $this->storage->setCustomUser($user_id, $password, $email, $name, $phone, $dial_code, $scope);
            if ($result) {
                $this->response->setParameters($this->success(['user_id' => $user_id, 'existing' => false]));
            } else {
                $this->response->setStatusCode(500);
                $this->response->setParameters($this->error('Failed to create user', 'internal_error'));
            }
        }
        $this->response->send();
        die;
    }


    /**
     * Update OAuth User
     */
    public function updateUser()
    {
        $user_id = $this->request->request('user_id') ?? $this->request->query('user_id');
        if (!empty($user_id)) {
            // Validate permission if specific user is requested
            $this->validatePermission([OAuthScopeService::SCOPE_SYSTEM, OAuthScopeService::SCOPE_ADMIN]);
        } else {
            $user_id = $this->getCurrentToken('user_id');
        }

        if (!empty($user_id)) {

            $name = $this->request->request('name') ?: null;
            $email = $this->request->request('email') ?: null;
            $password = $this->request->request('password') ?: null;
            $phone = $this->request->request('phone') ?: null;
            $dial_code = $this->request->request('dial_code') ?: null;
            $scope = $this->request->request('scope') ?: null;
            $remove_scope = $this->request->request('remove_scope') ?: null;

            // Check if scope is valid if it's available
            $scope = array_keys(OAuthScopeService::findScope($scope) ?: []);

            // Check if user exists
            $user = $this->storage->getUser(!empty($user_id) ? $user_id : $email);
            if (!empty($user)) {

                // Merge current scope with new scopes
                $mergedScopes = array_unique(array_merge(Utils::explode($user["scope"]), $scope ?: []));

                // Remove Scopes
                $newScopes = array_diff($mergedScopes, Utils::explode($remove_scope));

                //Update User
                $result = $this->storage->setCustomUser(
                    $user_id,
                    $password,
                    $email ?? $user["email"],
                    $name ?? $user["name"],
                    $phone ?? $user["phone"],
                    $dial_code ?? $user["dial_code"],
                    Utils::implode($newScopes)
                );
                if ($result) {
                    $this->response->setParameters($this->success('Update Successful'));
                } else {
                    $this->response->setParameters($this->error('Failed to update user', 'invalid_user'));
                }
            } else {
                $this->response->setParameters($this->error('User does not exist', 'invalid_user'));
            }
        } else {
            $this->response->setStatusCode(400);
            $this->response->setParameters($this->error('Invalid User Request', 'invalid_request'));
        }
        $this->response->send();
        die;
    }

    /**
     * Create OAuth client
     */
    public function createClient()
    {
        // Validate permission
        $this->validatePermission([OAuthScopeService::SCOPE_SYSTEM, OAuthScopeService::SCOPE_ADMIN]);

        $client_name = $this->request->request('client_name');
        $org_id = $this->request->request('org_id');
        $redirect_uri = $this->request->request('redirect_url');
        $grant_types = $this->request->request('grant_types');
        $user_id = $this->request->request('user_id') ?: null;
        $scope = $this->request->request('scope');

        // Validate Parameters
        if (!$client_name || !$org_id || !$grant_types || !$scope) {
            $this->response->setStatusCode(400);
            $this->response->setParameters($this->error('Invalid Parameters', 'invalid_request'));
            $this->response->send();
            die;
        }

        // Check if scope is valid
        $scope = array_keys(OAuthScopeService::findScope($scope) ?: []);
        if (empty($scope)) {
            $this->response->setStatusCode(400);
            $this->response->setParameters($this->error('Invalid requested scope(s)', 'invalid_scopes'));
            $this->response->send();
            die;
        }
        // Add claim scopes if openid scope is included
        else if (in_array(OAuthScopeService::SCOPE_OPENID, $scope)) {
            array_merge($scope, OAuthScopeService::CLAIM_SCOPES);
        }

        // Process params
        $client_id = str_replace(' ', '_', strtolower($client_name)) . '_' . crc32(uniqid($client_name));
        $client_secret = sha1(uniqid($client_id));
        $grant_types = Utils::implode($grant_types);
        $scope = Utils::implode($scope);
        $redirect_uri = Utils::implode($redirect_uri);

        // Insert Client
        $result = $this->storage->setCustomClientDetails($org_id, $client_id, $client_name, $client_secret, $redirect_uri, $grant_types, $scope, $user_id);
        if ($result) {

            // Insert jwt public keys for client
            $algo = 'sha256';
            $rsa = new RSA();
            $rsa->setHash($algo);
            $keys = $rsa->createKey(2048);
            if (!empty($keys) && $this->storage->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")) {
                $this->response->setParameters($this->success([
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'grant_types' => $grant_types,
                    'redirect_uri' => $redirect_uri,
                    'scope' => $scope,
                    'public_key' => base64_encode($keys['publickey']),
                    'algorithm' => $algo,
                    'encode' => 'base64'
                ]));
            } else {
                $this->response->setParameters($this->success([
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'grant_types' => $grant_types,
                    'redirect_uri' => $redirect_uri,
                    'scope' => $scope
                ]));
            }
        } else {
            $this->response->setStatusCode(500);
            $this->response->setParameters($this->error('Failed to create client', 'server_error'));
        }
        $this->response->send();
        die;
    }

    /**
     * Update OAuth Client
     */
    public function updateClient()
    {
        $client_id = $this->request->request('client_id') ?? $this->request->query('client_id');
        if (!empty($client_id)) {
            // Validate permission if specific user is requested
            $this->validatePermission([OAuthScopeService::SCOPE_SYSTEM, OAuthScopeService::SCOPE_ADMIN]);
        } else {
            $client_id = $this->getCurrentToken('client_id');
        }

        if (!empty($client_id)) {

            $client_name = $this->request->request('client_name') ?: null;
            $client_secret = $this->request->request('client_secret') ?: null;
            $redirect_uri = $this->request->request('redirect_url') ?: null;
            $grant_types = $this->request->request('grant_types') ?: null;
            $scope = $this->request->request('scope');
            $remove_scope = $this->request->request('remove_scope') ?: null;

            // Check if scope is valid if it's available
            $scope = array_keys(OAuthScopeService::findScope($scope) ?: []);

            // Check if user exists
            $client = $this->storage->getCustomClientDetails($client_id);
            if (!empty($client)) {

                // Merge current scope with new scopes
                $mergedScopes = array_unique(array_merge(Utils::explode($client["scope"]), $scope ?: []));

                // Remove Scopes
                $newScopes = array_diff($mergedScopes, Utils::explode($remove_scope));

                // Process params
                $grant_types = Utils::implode($grant_types);
                $newScopes = Utils::implode($newScopes);
                $redirect_uri = Utils::implode($redirect_uri);

                // Update User
                $result = $this->storage->setCustomClientDetails(
                    $client["org_id"],
                    $client["client_id"],
                    !empty($client_name) ? $client_name : $client["client_name"],
                    !empty($client_secret) ? $client_secret : $client["client_secret"],
                    !empty($redirect_uri) ? $redirect_uri : $client["redirect_uri"],
                    !empty($grant_types) ? $grant_types : $client["grant_types"],
                    $newScopes
                );

                if ($result) {
                    $this->response->setParameters($this->success('Update Successful'));
                } else {
                    $this->response->setParameters($this->error('Failed to update client', 'invalid_client'));
                }
            } else {
                $this->response->setParameters($this->error('Client does not exist', 'invalid_client'));
            }
        } else {
            $this->response->setStatusCode(400);
            $this->response->setParameters($this->error('Invalid Client Request', 'invalid_request'));
        }
        $this->response->send();
        die;
    }

    /**
     * Update OAuth client's Public and Private Key pair
     */
    public function updateClientKeys()
    {
        // Validate permission
        $this->validatePermission([OAuthScopeService::SCOPE_SYSTEM, OAuthScopeService::SCOPE_ADMIN]);

        $client_id = $this->request->request('client_id') ?? $this->request->query('client_id') ?? $this->getCurrentClient('client_id');
        if (!empty($client_id)) {

            $algo = 'sha256';
            $rsa = new RSA();
            $rsa->setHash($algo);
            $keys = $rsa->createKey(2048);

            if (!empty($keys) && $this->storage->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")) {
                $this->response->setParameters($this->success([
                    'client_id' => $client_id,
                    'public_key' => base64_encode($keys['publickey']),
                    'algorithm' => $algo,
                    'encode' => 'base64'
                ]));
            } else {
                $this->response->setParameters($this->error('Failed to update client keys', 'server_error'));
            }
        } else {
            $this->response->setStatusCode(400);
            $this->response->setParameters($this->error('Invalid Client', 'invalid_request'));
        }
        $this->response->send();
        die;
    }

    /**
     * Get Public key for client
     */
    public function getPublicKey()
    {
        $client_id = $this->request->request('client_id') ?? $this->request->query('client_id');
        if (!empty($client_id)) {
            // Validate permission if specific client is requested
            $this->validatePermission([OAuthScopeService::SCOPE_SYSTEM, OAuthScopeService::SCOPE_ADMIN]);
        } else {
            $client_id = $this->getCurrentClient('client_id');
        }

        if (!empty($client_id)) {
            if (!empty($key = $this->storage->getPublicKey($client_id))) {
                $this->response->setParameters($this->success([
                    'client_id' => $client_id,
                    'public_key' => base64_encode($key),
                    'encode' => 'base64'
                ]));
            } else {
                $this->response->setParameters($this->error('No public key available for this client', 'not_found'));
            }
        } else {
            $this->response->setStatusCode(400);
            $this->response->setParameters($this->error('Invalid Client', 'invalid_request'));
        }
        $this->response->send();
        die;
    }


    /**
     * Get Public Private Key Pairs
     */
    public function generateKeyPair()
    {
        $size = intval($this->request->request('size') ?? $this->request->query('size') ?? 1024);

        $algo = 'sha256';
        $rsa = new RSA();
        $rsa->setHash($algo);
        $keys = $rsa->createKey($size < 16 || $size > 2048 ? 1024 : $size);

        if (!empty($keys)) {
            $this->response->setParameters($this->success([
                'private_key' => base64_encode($keys['privatekey']),
                'public_key' => base64_encode($keys['publickey']),
                'algorithm' => $algo,
                'encode' => 'base64'
            ]));
        } else {
            $this->response->setStatusCode(500);
            $this->response->setParameters($this->error('Failed to generate key pair', 'server_error'));
        }
        $this->response->send();
        die;
    }
}
