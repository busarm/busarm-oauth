<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

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
        parent::__construct(true);
    }

    /**
     * Verify token and obtain info
     * @api resources/getTokenInfo
     * @method GET*/
    public function getTokenInfo()
    {
        if ($result = $this->get_oauth_server()->getAccessTokenData($this->request, $this->response)) {
            $user = null;
            if(!empty($user_id = $result["user_id"])){
                $user = $this->get_oauth_storage()->getSingleUserInfo($user_id);
                if(!empty($user)){
                    unset($user["scope"]); //remove scope
                }
            }
            $result["user"] = $user;
            unset($result["user_id"]); //remove user id
            $this->response->setParameters(array('success' => true, 'data' => $result));
        }
        $this->response->send();
        die;
    }

    /**
     * Obtain user info
     * @api resources/getUser
     * @method GET */
    public function getUser()
    {
        if ($result = $this->get_oauth_server()->getAccessTokenData($this->request, $this->response)) {
            $userInfo = $this->get_oauth_storage()->getSingleUserInfo(@$result["user_id"]);
            if ($userInfo) {
                $this->response->setParameters(array('success' => true, 'data' => $userInfo));
            } else {
                $this->response->setParameters(array('success' => false, 'error' => 'invalid_user', 'error_description' => "User doesn't exist"));
            }
        }
        $this->response->send();
        die;
    }

    /**
     * Get Bulk users info
     * @api resources/fetchUsers
     * @method POST
     * @param user_ids Array Required */
    public function fetchUsers()
    {
        if ($this->get_oauth_server()->verifyResourceRequest(
                $this->request,
                $this->response,
                $this->admin_scope)) {
            if (!empty($user_ids = $this->request->request('user_ids'))) {
                $users = $this->get_oauth_storage()->getMultipleUserInfo(
                    $this->explode($user_ids)
                );
                if (!empty($users)) {
                    $this->response->setParameters(array('success' => true, 'data' => $users));
                } else {
                    $this->response->setParameters(array('success' => false, 'error' => 'invalid_users', 'error_description' => "Users doesn't exist"));
                }
            } else {
                $this->response->setParameters(array('success' => false, 'error' => 'invalid_request', 'error_description' => "Please specify valid users"));
            }
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
        $access_token = $this->request->request('access_token');
        $refresh_token = $this->request->request('refresh_token');
        $done = false;
        if (!empty($access_token)) {
            $done = $this->get_oauth_storage()->unsetAccessToken($access_token);
        }
        if (!empty($refresh_token)) {
            $done = $this->get_oauth_storage()->unsetRefreshToken($refresh_token);
        }
        $this->response->setParameters(array('success' => $done));
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
     * @param scope Array Required */
    public function createUser()
    {
        if ($this->get_oauth_server()->verifyResourceRequest($this->request, $this->response)) {
            $name = $this->request->request('name');
            $email = $this->request->request('email');
            $phone = $this->request->request('phone');
            $dial_code = $this->request->request('dial_code');
            $password = $this->request->request('password');
            $scope = $this->request->request('scope');
            $scopes = $this->implode($scope);

            //Create user id
            $prefix = !empty($email)?$email:(!empty($phone)?$phone:"");
            $user_id = sha1(uniqid($prefix));

            //Check if scope is valid
            $scopes = $this->get_oauth_storage()->scopeExists($scopes) ? $scopes : $this->get_oauth_storage()->getDefaultScope();

            //Check if user exists
            $userInfo = $this->get_oauth_storage()->getUser(!empty($user_id) ? $user_id : $email);
            if ($userInfo) {
                $this->response->setParameters(array('success' => false, 'error' => 'duplicate_user', 'error_description' => sprintf("User with email %s already exists", $email)));
            } else {
                //Insert User
                $result = $this->get_oauth_storage()->setUser_custom($user_id,$password,$email,$name,$phone,$dial_code,$scope);
                if ($result){
                    $this->response->setParameters(array('success' => true, 'data' => ['user_id'=>$user_id]));
                }
                else {
                    $this->response->setParameters(array('success' => false, 'error' => 'internal_error', 'error_description' => 'Failed to create user'));
                }
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
        if (!empty($user_id = $this->request->request('user_id'))) { //If User Id Provided, verify admin scope
            if (!$this->get_oauth_server()->verifyResourceRequest($this->request, $this->response, $this->admin_scope)) {
                $user_id = null;
            }
        } else {
            $result = $this->get_oauth_server()->getAccessTokenData(
                $this->request,
                $this->response);
            $user_id = @$result['user_id'];
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
            $scopes = $this->implode($scope);
            if (!empty($scopes)) {
                $scopes = $this->get_oauth_storage()->scopeExists($scopes) ? $scopes : "";
            }

            //Check if user exists
            $userInfo = $this->get_oauth_storage()->getUser(!empty($user_id) ? $user_id : $email);
            if (!empty($userInfo)) {

                //Merge current scope with new scopes
                $mergedScopes = array_unique(array_merge($this->explode($userInfo["scope"]), $this->explode($scopes)));

                //Remove Scopes
                $newScopes = array_diff($mergedScopes, $this->explode($remove_scope));

                //Update User
                $result = $this->get_oauth_storage()->setUser_custom($user_id,$password,$email,$name,$phone,$dial_code,$this->implode($newScopes));

                $this->response->setParameters(array('success' => $result));

            } else {
                $this->response->setParameters(array('success' => false, 'error' => 'invalid_user', 'error_description' => "User doesn't exist"));
            }
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
        if ($this->get_oauth_server()->verifyResourceRequest(
                $this->request,
                $this->response,
                "$this->admin_scope $this->staff_scope $this->developer_scope")) {

            $client_id = $this->request->request('client_id');
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
            $scopes = $this->get_oauth_storage()->scopeExists($scope) ? $scope : $this->get_oauth_storage()->getDefaultScope();

            //Insert Client
            $result = $this->get_oauth_storage()->setClientDetails($client_id, $client_secret, $org_id, $redirect_uri, $grant_types, $scopes, $user_id);

            //Insert jwt public keys for client
            $rsa = new phpseclib\Crypt\RSA();
            $keys = $rsa->createKey(2048);
            if(!empty($keys) && $this->get_oauth_storage()->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")){
                $this->response->setParameters(
                    array('success' => $result,
                        'data' => [
                            'client_id' => $client_id,
                            'client_secret' => $client_secret,
                            'keys' => $keys
                        ]));
            }
            else {
                $this->response->setParameters(
                    array('success' => $result,
                        'data' => [
                            'client_id' => $client_id,
                            'client_secret' => $client_secret,
                        ]));
            }
        }
        $this->response->send();
        die;
    }
    
}