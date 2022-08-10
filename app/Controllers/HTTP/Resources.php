<?php

namespace App\Controllers\HTTP;

use phpseclib\Crypt\RSA;
use App\Controllers\OAuthBaseController;
use App\Services\OAuthScopeService;
use App\Helpers\Utils;
use App\Dto\Request\CreateClientDto;
use App\Dto\Request\CreateUserDto;
use App\Dto\Request\UpdateClientDto;
use App\Dto\Request\UpdateUserDto;

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
        parent::__construct();
    }

    /**
     * Get all scopes
     * */
    public function scopes()
    {
        return $this->success(OAuthScopeService::$allScopes);
    }

    /**
     * Delete Access token and refresh token
     */
    public function removeAccess()
    {
        $access_token = $this->oauth->request->request('access_token');
        $refresh_token = $this->oauth->request->request('refresh_token');
        $done = false;
        if (!empty($access_token)) {
            $done = $this->oauth->storage->unsetAccessToken($access_token);
        }
        if (!empty($refresh_token)) {
            $done = $this->oauth->storage->unsetRefreshToken($refresh_token);
        }
        if ($done) {
            return $this->success('Successfully cleared access');
        } else {
            app()->sendHttpResponse(400, $this->error('Failed to clear access'));
        }
    }

    /**
     * Obtain user info
     */
    public function getUser()
    {
        $user =  $this->oauth->storage->getCustomUserWIthClaims(
            $this->oauth->getAuthToken('user_id') ?? $this->oauth->getAuthClient('user_id'),
            array_keys(OAuthScopeService::findOpenIdScope($this->oauth->getAuthToken('scope')) ?: []),
            false
        );
        if (!empty($user)) {
            return $this->success($user);
        } else {
            app()->sendHttpResponse(404, $this->error('Users does not exist', 'invalid_user'));
        }
    }

    /**
     * Obtain user info by Id
     */
    public function getUserById($user_id = null)
    {
        $user_id = $user_id ?? $this->oauth->request->request('user_id') ?? $this->oauth->request->query('user_id');
        if (!empty($user_id) && !empty($user = $this->oauth->storage->getCustomUser($user_id))) {
            return $this->success($user);
        } else {
            app()->sendHttpResponse(404, $this->error('Users does not exist', 'invalid_user'));
        }
    }

    /**
     * Get Bulk users info
     */
    public function fetchUsers()
    {
        if (!empty($user_ids = ($this->oauth->request->request('user_ids') ?? $this->oauth->request->query('user_ids')))) {
            $users = $this->oauth->storage->getMultipleUsers(Utils::explode($user_ids, ','));
            if (!empty($users)) {
                return $this->success($users);
            } else {
                app()->sendHttpResponse(404, $this->error('User(s) does not exist', 'invalid_users'));
            }
        } else {
            app()->sendHttpResponse(400, $this->error('User(s) not specified', 'invalid_request'));
        }
    }

    /**
     * Create OAuth User
     */
    public function createUser(CreateUserDto $dto)
    {
        $name = $dto->name;
        $email = $dto->email;
        $phone = $dto->phone;
        $dial_code = $dto->dial_code;
        $password = $dto->password;
        $scope = $dto->scope;
        $force = $dto->force;

        // Validate Parameters
        if (!$email || !$phone || !$dial_code || !$password || !$scope) {
            app()->sendHttpResponse(400, $this->error("Invalid Parameters. 'email', 'phone', 'dial_code', 'password', and 'scope' are required", 'invalid_request'));
        }

        //Check if scope is valid
        $scope = array_keys(OAuthScopeService::findScope($scope) ?: []);
        if (empty($scope)) {
            app()->sendHttpResponse(400, $this->error('Invalid requested scope(s)', 'invalid_scopes'));
        }

        // Add claim scopes if openid scope is included
        else if (in_array(SCOPE_OPENID, $scope)) {
            array_merge($scope, OAuthScopeService::CLAIM_SCOPES);
        }

        // Process params
        $scope = Utils::implode($scope);
        $user_id = sha1(uniqid(!empty($email) ? $email : (!empty($phone) ? $phone : $name)));

        // Check if user exists
        if ($email && ($user = $this->oauth->storage->getUser($email))) {
            if ($force) {
                app()->sendHttpResponse(400, $this->error(sprintf("User with email %s already exists", $email), 'duplicate_user'));
            } else {
                return $this->success([
                    'user_id' => $user['user_id'],
                    'existing' => true
                ]);
            }
        } else {
            // Insert User
            $result = $this->oauth->storage->setCustomUser($user_id, $password, $email, $name, $phone, $dial_code, $scope);
            if ($result) {
                return $this->success([
                    'user_id' => $user_id,
                    'existing' => false
                ]);
            } else {
                app()->sendHttpResponse(500, $this->error('Failed to create user', 'internal_error'));
            }
        }
    }

    /**
     * Update OAuth User
     */
    public function updateUser(UpdateUserDto $dto)
    {
        return $this->updateUserById($dto, $this->oauth->getAuthToken('user_id'));
    }

    /**
     * Update OAuth User by Id
     */
    public function updateUserById(UpdateUserDto $dto, $user_id = null)
    {
        $user_id = $user_id ?? $this->oauth->request->request('user_id') ?? $this->oauth->request->query('user_id');
        if (!empty($user_id)) {

            $name = $dto->name;
            $email = $dto->email;
            $phone = $dto->phone;
            $dial_code = $dto->dial_code;
            $password = $dto->password;
            $scope = $dto->scope;
            $remove_scope = $dto->remove_scope;

            // Check if scope is valid if it's available
            $scope = array_keys(OAuthScopeService::findScope($scope) ?: []);

            // Check if user exists
            $user = $this->oauth->storage->getUser(!empty($user_id) ? $user_id : $email);
            if (!empty($user)) {

                // Merge current scope with new scopes and remove scopes
                $new_scopes = array_diff(
                    array_unique(array_merge(Utils::explode($user["scope"]), $scope ?: [])), 
                    Utils::explode($remove_scope)
                );

                // Update User
                $result = $this->oauth->storage->setCustomUser(
                    $user_id,
                    $password,
                    $email ?? $user["email"],
                    $name ?? $user["name"],
                    $phone ?? $user["phone"],
                    $dial_code ?? $user["dial_code"],
                    Utils::implode($new_scopes)
                );

                if ($result) {
                    return $this->success('Update Successful');
                } else {
                    app()->sendHttpResponse(400, $this->error('Failed to update user', 'invalid_user'));
                }
            } else {
                app()->sendHttpResponse(404, $this->error('User does not exist', 'invalid_user'));
            }
        } else {
            app()->sendHttpResponse(404, $this->error('Invalid User Request', 'invalid_request'));
        }
    }

    /**
     * Create OAuth client
     */
    public function createClient(CreateClientDto $dto)
    {
        $org_id = $dto->org_id;
        $client_name = $dto->client_name;
        $redirect_uri = $dto->redirect_uri;
        $grant_types = $dto->grant_types;
        $user_id = $dto->user_id;
        $scope = $dto->scope;

        // Validate Parameters
        if (!$client_name || !$org_id || !$grant_types || !$scope) {
            app()->sendHttpResponse(404, $this->error('Invalid Parameters', 'invalid_request'));
        }

        // Check if scope is valid
        $scope = array_keys(OAuthScopeService::findScope($scope) ?: []);
        if (empty($scope)) {
            app()->sendHttpResponse(400, $this->error('Invalid requested scope(s)', 'invalid_scopes'));
        }

        // Add claim scopes if openid scope is included
        else if (in_array(SCOPE_OPENID, $scope)) {
            array_merge($scope, OAuthScopeService::CLAIM_SCOPES);
        }

        // Process params
        $client_id = str_replace(' ', '_', strtolower($client_name)) . '_' . crc32(uniqid($client_name));
        $client_secret = sha1(uniqid($client_id));
        $grant_types = Utils::implode($grant_types);
        $scope = Utils::implode($scope);
        $redirect_uri = Utils::implode($redirect_uri);

        // Insert Client
        $result = $this->oauth->storage->setCustomClientDetails($org_id, $client_id, $client_name, $client_secret, $redirect_uri, $grant_types, $scope, $user_id);
        if ($result) {

            // Insert jwt public keys for client
            $algo = 'sha256';
            $rsa = new RSA();
            $rsa->setHash($algo);
            $keys = $rsa->createKey(2048);
            if (!empty($keys) && $this->oauth->storage->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")) {
                return $this->success([
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'grant_types' => $grant_types,
                    'redirect_uri' => $redirect_uri,
                    'scope' => $scope,
                    'public_key' => base64_encode($keys['publickey']),
                    'algorithm' => $algo,
                    'encode' => 'base64'
                ]);
            } else {
                return $this->success([
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'grant_types' => $grant_types,
                    'redirect_uri' => $redirect_uri,
                    'scope' => $scope
                ]);
            }
        } else {
            app()->sendHttpResponse(500, $this->error('Failed to create client', 'server_error'));
        }
    }

    /**
     * Update OAuth Client
     */
    public function updateClient(UpdateClientDto $dto)
    {
        return $this->updateClientById($dto, $this->oauth->getAuthToken('client_id'));
    }

    /**
     * Update OAuth Client by Id
     */
    public function updateClientById(UpdateClientDto $dto, $client_id = null)
    {
        $client_id = $client_id ?? $this->oauth->request->request('client_id') ?? $this->oauth->request->query('client_id');
        if (!empty($client_id)) {

            $client_name = $dto->client_name;
            $client_secret = $dto->client_secret;
            $redirect_uri = $dto->redirect_uri;
            $grant_types = $dto->grant_types;
            $scope = $dto->scope;
            $remove_scope = $dto->remove_scope;

            // Check if scope is valid if it's available
            $scope = array_keys(OAuthScopeService::findScope($scope) ?: []);

            // Check if user exists
            $client = $this->oauth->storage->getCustomClientDetails($client_id);
            if (!empty($client)) {

                // Merge current scope with new scopes and remove scopes
                $new_scopes = array_diff(
                    array_unique(array_merge(Utils::explode($client["scope"]), $scope ?: [])), 
                    Utils::explode($remove_scope)
                );

                // Process params
                $grant_types = Utils::implode($grant_types);
                $new_scopes = Utils::implode($new_scopes);
                $redirect_uri = Utils::implode($redirect_uri);

                // Update User
                $result = $this->oauth->storage->setCustomClientDetails(
                    $client["org_id"],
                    $client["client_id"],
                    !empty($client_name) ? $client_name : $client["client_name"],
                    !empty($client_secret) ? $client_secret : $client["client_secret"],
                    !empty($redirect_uri) ? $redirect_uri : $client["redirect_uri"],
                    !empty($grant_types) ? $grant_types : $client["grant_types"],
                    $new_scopes
                );

                if ($result) {
                    return $this->success('Update Successful');
                } else {
                    app()->sendHttpResponse(400, $this->error('Failed to update client', 'invalid_client'));
                }
            } else {
                app()->sendHttpResponse(404, $this->error('Client does not exist', 'invalid_client'));
            }
        } else {
            app()->sendHttpResponse(400, $this->error('Invalid Client Request', 'invalid_request'));
        }
    }

    /**
     * Update OAuth client's Public and Private Key pair
     */
    public function updateClientKeys()
    {
        $client_id = $this->oauth->request->request('client_id') ?? $this->oauth->request->query('client_id') ?? $this->oauth->getAuthClient('client_id');
        if (!empty($client_id)) {

            $algo = 'sha256';
            $rsa = new RSA();
            $rsa->setHash($algo);
            $keys = $rsa->createKey(2048);

            if (!empty($keys) && $this->oauth->storage->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")) {
                return $this->success([
                    'client_id' => $client_id,
                    'public_key' => base64_encode($keys['publickey']),
                    'algorithm' => $algo,
                    'encode' => 'base64'
                ]);
            } else {
                app()->sendHttpResponse(500, $this->error('Failed to update client keys', 'server_error'));
            }
        } else {
            app()->sendHttpResponse(400, $this->error('Invalid Client', 'invalid_request'));
        }
    }

    /**
     * Get Public key for client
     */
    public function getPublicKey()
    {
        $client_id = $this->oauth->getAuthClient('client_id');
        if (!empty($client_id)) {
            if (!empty($key = $this->oauth->storage->getPublicKey($client_id))) {
                return $this->success([
                    'client_id' => $client_id,
                    'public_key' => base64_encode($key),
                    'encode' => 'base64'
                ]);
            } else {
                app()->sendHttpResponse(404, $this->error('No public key available for this client', 'not_found'));
            }
        } else {
            app()->sendHttpResponse(400, $this->error('Invalid Client', 'invalid_request'));
        }
    }

    /**
     * Get Public key for client by client Id
     */
    public function getPublicKeyById()
    {
        $client_id = $this->oauth->request->request('client_id') ?? $this->oauth->request->query('client_id');
        if (!empty($client_id)) {
            if (!empty($key = $this->oauth->storage->getPublicKey($client_id))) {
                return $this->success([
                    'client_id' => $client_id,
                    'public_key' => base64_encode($key),
                    'encode' => 'base64'
                ]);
            } else {
                app()->sendHttpResponse(404, $this->error('Invalid client or no public key available for client', 'not_found'));
            }
        } else {
            app()->sendHttpResponse(400, $this->error('Invalid Client', 'invalid_request'));
        }
    }


    /**
     * Get Public Private Key Pairs
     */
    public function generateKeyPair()
    {
        $size = intval($this->oauth->request->request('size') ?? $this->oauth->request->query('size') ?? 1024);

        $algo = 'sha256';
        $rsa = new RSA();
        $rsa->setHash($algo);
        $keys = $rsa->createKey($size < 16 || $size > 2048 ? 1024 : $size);

        if (!empty($keys)) {
            return $this->success([
                'private_key' => base64_encode($keys['privatekey']),
                'public_key' => base64_encode($keys['publickey']),
                'algorithm' => $algo,
                'encode' => 'base64'
            ]);
        } else {
            app()->sendHttpResponse(500, $this->error('Failed to generate key pair', 'server_error'));
        }
    }
}
