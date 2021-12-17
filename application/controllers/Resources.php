<?php
defined('OAUTH_BASE_PATH') or exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 12:20 PM
 **/
class Resources extends Server
{

    public function __construct()
    {
        parent::__construct(true, true, true);
    }

    /**
     * Get all scopes
     * @api resources/scopes
     * @method Get 
     * */
    public function scopes()
    {
        if($this->validateAccessToken()) {
            $this->response->setParameters(array('success' => true, 'data' => Scopes::ALL_SCOPES));
        }
        $this->response->send();
        die;
    }

    /**
     * Delete Access token and refresh token
     * @api resources/removeAccess
     * @method POST
     * @param access_token String Required
     * @param refresh_token String Optional
     * */
    public function removeAccess()
    {
        // Validate permission
        $this->validatePermission([Scopes::SCOPE_SYSTEM, Scopes::SCOPE_SYSTEM]);

        $access_token = $this->request->request('access_token');
        $refresh_token = $this->request->request('refresh_token');
        $done = false;
        if (!empty($access_token)) {
            $done = $this->getOauthStorage()->unsetAccessToken($access_token);
        }
        if (!empty($refresh_token)) {
            $done = $this->getOauthStorage()->unsetRefreshToken($refresh_token);
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
     * @api resources/getUser
     * @method GET|POST 
     * */
    public function getUser()
    {
        $user_id = $this->request->request('user_id') ?? $this->request->query('user_id');
        if(!empty($user_id)) {

            // Validate permission if specific user is requested
            $this->validatePermission([Scopes::SCOPE_SYSTEM, Scopes::SCOPE_ADMIN]);
        }
        else {
            $user_id = $this->getTokenInfo('user_id');
        }

        $user = $this->getOauthStorage()->getSingleUserInfo($user_id);
        if ($user) {
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
     * @api resources/fetchUsers
     * @method GET|POST
     * @param user_ids Array or Comma separated string. Required 
     * */
    public function fetchUsers()
    {
        // Validate permission
        $this->validatePermission([Scopes::SCOPE_SYSTEM, Scopes::SCOPE_ADMIN]);

        if (!empty($user_ids = ($this->request->request('user_ids') ?? $this->request->query('user_ids')))) {
            $users = $this->getOauthStorage()->getMultipleUserInfo($this->explode($user_ids, ','));
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
     * Insert New Oauth User
     * @api resources/createUser
     * @method POST
     * @param user_id String Required
     * @param email String Required
     * @param password String Required
     * @param scope Array Required 
     * @param force Boolean Optional
     *  */
    public function createUser()
    {
        // Validate permission
        $this->validatePermission([Scopes::SCOPE_SYSTEM, Scopes::SCOPE_ADMIN]);

        $name = $this->request->request('name');
        $email = $this->request->request('email');
        $phone = $this->request->request('phone');
        $dial_code = $this->request->request('dial_code');
        $password = $this->request->request('password');
        $scope = $this->request->request('scope');
        $force = $this->request->request('force');

        //Create user id
        $prefix = !empty($email) ? $email : (!empty($phone) ? $phone : "");
        $user_id = sha1(uniqid($prefix));

        // Validate Parameters
        if(!$email || !$phone || !$dial_code || !$password || !$scope) {
            $this->response->setStatusCode(400);
            $this->response->setParameters($this->error('Invalid Parameters', 'invalid_request'));
            $this->response->send();
            die;
        }

        //Check if scope is valid
        $scope = array_keys(Scopes::findScope($scope) ?: []);
        if(empty($scope)) {
            $this->response->setStatusCode(400);
            $this->response->setParameters($this->error('Invalid requested scope(s)', 'invalid_scopes'));
            $this->response->send();
            die;
        }

        //Check if user exists
        if ($email && ($user = $this->getOauthStorage()->getUser($email))) {
            if ($force) {
                $this->response->setParameters($this->error(sprintf("User with email %s already exists", $email), 'duplicate_user'));
            } else {
                $this->response->setParameters($this->success(['user_id' => $user['user_id'], 'existing' => true]));
            }
        }
        else if ($user = $this->getOauthStorage()->getUser($user_id)) {
            if ($force) {
                $this->response->setParameters($this->error('User already exists', 'duplicate_user'));
            } else {
                $this->response->setParameters($this->success(['user_id' => $user['user_id'], 'existing' => true]));
            }
        } 
        else {
            //Insert User
            $scope = $this->implode($scope);
            $result = $this->getOauthStorage()->setUserCustom($user_id, $password, $email, $name, $phone, $dial_code, $scope);
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
     * Update Existing Oauth User
     * @api resources/updateUser
     * @method POST
     * @param email String Optional
     * @param password String Optional
     * @param scope Array Optional
     * @param remove_scope Array Optional
     */
    public function updateUser()
    {
        $user_id = $this->request->request('user_id') ?? $this->request->query('user_id');
        if(!empty($user_id)) {

            // Validate permission if specific user is requested
            $this->validatePermission([Scopes::SCOPE_SYSTEM, Scopes::SCOPE_ADMIN]);
        }
        else {
            $user_id = $this->getTokenInfo('user_id');
        }

        if (!empty($user_id)) {

            $password = $this->request->request('password');
            $name = $this->request->request('name');
            $email = $this->request->request('email');
            $phone = $this->request->request('phone');
            $dial_code = $this->request->request('dial_code');
            $scope = $this->request->request('scope');
            $remove_scope = $this->request->request('remove_scope');

            //Check if scope is valid if it's available
            $scope = array_keys(Scopes::findScope($scope) ?: []);

            //Check if user exists
            $userInfo = $this->getOauthStorage()->getUser(!empty($user_id) ? $user_id : $email);
            if (!empty($userInfo)) {

                //Merge current scope with new scopes
                $mergedScopes = array_unique(array_merge($this->explode($userInfo["scope"]), $scope ?: []));

                //Remove Scopes
                $newScopes = array_diff($mergedScopes, $this->explode($remove_scope));

                //Update User
                $result = $this->getOauthStorage()->setUserCustom($user_id, $password, $email, $name, $phone, $dial_code, $this->implode($newScopes));
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
     * Insert new oauth client credentials
     * @api resources/createClient
     * @method POST
     * @param client_id String Required
     * @param redirect_url String Required
     * @param grant_types Array|String Required
     * @param scope Array Required
     * @param user_id String Optional 
     * */
    public function createClient()
    {
        // Validate permission
        $this->validatePermission([Scopes::SCOPE_SYSTEM, Scopes::SCOPE_ADMIN]);
        
        $client_id = $this->request->request('client_id');
        $client_name = $this->request->request('client_name');
        $org_id = $this->request->request('org_id');
        $redirect_uri = $this->request->request('redirect_url');
        $grant_types = $this->request->request('grant_types');
        $scope = $this->request->request('scope');
        $user_id = $this->request->request('user_id');

        $client_secret = md5(uniqid($client_id));

        $grant_types = $this->implode($grant_types);
        $scope = $this->implode($scope);
        $redirect_uri = $this->implode($redirect_uri);

        //Check if scope is valid
        $scopes = $this->getOauthServer()->getScopeUtil()->scopeExists($scope) ? $scope : $this->getOauthServer()->getScopeUtil()->getDefaultScope();

        //Insert Client
        $result = $this->getOauthStorage()->setClientDetailsCustom($org_id, $client_id, $client_name, $client_secret, $redirect_uri, $grant_types, $scopes, $user_id);

        //Insert jwt public keys for client
        if ($result) {

            $algo = 'sha256';
            $rsa = new phpseclib\Crypt\RSA();
            $rsa->setHash($algo);
            $keys = $rsa->createKey(2048);
            if (!empty($keys) && $this->getOauthStorage()->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")) {
                $this->response->setParameters($this->success([
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'public_key' => base64_encode($keys['publickey']),
                    'algorithm' => $algo,
                    'encode' => 'base64'
                ]));
            } else {
                $this->response->setParameters($this->success([
                    'client_id' => $client_id,
                    'client_secret' => $client_secret
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
     * Update client's Public and Private Key pair
     * @api resources/updateClientKeys
     * @param client_id String Optional
     * @method POST
     * */
    public function updateClientKeys()
    {
        // Validate permission
        $this->validatePermission([Scopes::SCOPE_SYSTEM, Scopes::SCOPE_ADMIN]);

        $client_id = $this->request->request('client_id') ?? $this->request->query('client_id') ?? $this->getClientInfo('client_id');
        if (!empty($client_id)) {

            $algo = 'sha256';
            $rsa = new phpseclib\Crypt\RSA();
            $rsa->setHash($algo);
            $keys = $rsa->createKey(2048);
            
            if (!empty($keys) && $this->getOauthStorage()->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")) {
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
     * @api resources/getPublicKey
     * @param client_id String Optional
     * @method POST
     * */
    public function getPublicKey()
    {
        $client_id = $this->request->request('client_id') ?? $this->request->query('client_id');
        if(!empty($client_id)) {

            // Validate permission if specific client is requested
            $this->validatePermission([Scopes::SCOPE_SYSTEM, Scopes::SCOPE_ADMIN]);
        }
        else {
            $client_id = $this->getClientInfo('client_id');
        }

        if (!empty($client_id)) {
            if (!empty($key = $this->getOauthStorage()->getPublicKey($client_id))) {
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
     * @api resources/generateKeyPair
     * @method GET|POST
     * */
    public function generateKeyPair()
    {
        $size = intval($this->request->request('size') ?? $this->request->query('size') ?? 1024);

        $algo = 'sha256';
        $rsa = new phpseclib\Crypt\RSA();
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
