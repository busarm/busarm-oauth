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

    private $request;
    private $response;

    public function __construct()
    {
        parent::__construct();

        $this->request = OAuth2\Request::createFromGlobals();
        $this->response = new OAuth2\Response();
    }

    /**Verify Token only*/
    public function verifyToken()
    {
        if ($this->get_oauth_server()->verifyResourceRequest($this->request, $this->response)) {
            $this->response->setParameters(array('success' => true, 'message' => 'Api access granted'));
        }
        $this->response->send();
        die;
    }

    /**Verify token and obtain data*/
    public function getTokenData()
    {
        if ($result = $this->get_oauth_server()->getAccessTokenData($this->request, $this->response)) {
            $this->response->setParameters(array('success' => true, 'data' => $result));
        }
        $this->response->send();
        die;
    }

    /**Verify token and obtain user info*/
    public function getUserInfo()
    {
        if ($result = $this->get_oauth_server()->getAccessTokenData($this->request, $this->response)) {
            $userInfo = $this->get_oauth_storage()->getSingletUserInfo(@$result["user_id"]);
            if($userInfo){
                $this->response->setParameters(array('success' => true, 'data' => $userInfo));
            }
            else{
                $this->response->setParameters(array('success' => false, 'error' => 'invalid_user', 'error_description' => "User doesn't exist"));
            }
        }
        $this->response->send();
        die;
    }

    /**Get Bulk users info*/
    public function getUsers()
    {
        if ($this->get_oauth_server()->verifyResourceRequest(
            $this->request,
            $this->response,
            $this->admin_scope))
        {
            if (!empty($user_id = $this->request->request('user_ids')) || !empty($user_id = $this->request->request('emails'))) {
                $users = $this->get_oauth_storage()->getMultipleUserInfo(
                    $this->explode($this->request->request('user_ids')),
                    $this->explode($this->request->request('emails'))
                );
                if(!empty($users)){
                    $this->response->setParameters(array('success' => true, 'data' => $users));
                }
                else{
                    $this->response->setParameters(array('success' => false, 'error' => 'invalid_users', 'error_description' => "Users doesn't exist"));
                }
            }
            else{
                $this->response->setParameters(array('success' => false, 'error' => 'invalid_request', 'error_description' => "Please specify valid  users"));
            }
        }
        $this->response->send();
        die;
    }


    /**Delete Access token and refresh token*/
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


    /**Insert New Oauth User*/
    public function createUser()
    {
        if ($this->get_oauth_server()->verifyResourceRequest($this->request, $this->response)) {

            $user_id = $this->request->request('user_id');
            $password = $this->request->request('password');
            $email = $this->request->request('email');
            $scope = $this->request->request('scope');
            $scopes = $this->implode($scope);

            //Check if scope is valid
            $scopes = $this->get_oauth_storage()->scopeExists($scopes) ? $scopes : $this->get_oauth_storage()->getDefaultScope();

            //Check if user exists
            $userInfo = $this->get_oauth_storage()->getUser($user_id, $email);
            if ($userInfo) {
                $this->response->setParameters(array('success' => false, 'error' => 'duplicate_user', 'error_description' => sprintf("User with email %s already exists", $email)));
            } else {

                //Insert User
                $result = $this->get_oauth_storage()->setUser($user_id, $password, $email, $scopes);
                $this->response->setParameters(array('success' => $result));
            }
        }
        $this->response->send();
        die;
    }


    /**Update Existing Oauth User*/
    public function updateUser()
    {
        if (!empty($user_id = $this->request->request('user_id'))) { //If User Id Provided, verify scope
            if (!$this->get_oauth_server()->verifyResourceRequest(
                $this->request,
                $this->response,
                "$this->admin_scope $this->staff_scope"))
            {
                $user_id = null;
            }
        }
        else{
            $result = $this->get_oauth_server()->getAccessTokenData(
                $this->request,
                $this->response);
            $user_id = @$result['user_id'];
        }

        if (!empty($user_id)) {

            $password = $this->request->request('password');
            $email = $this->request->request('email');
            $scope = $this->request->request('scope');
            $remove_scope = $this->request->request('remove_scope');

            //Check if scope is valid if it's available
            $scopes = $this->implode($scope);
            if (!empty($scopes)) {
                $scopes = $this->get_oauth_storage()->scopeExists($scopes) ? $scopes : "";
            }

            //Check if user exists
            $userInfo = $this->get_oauth_storage()->getUser($user_id, $email);
            if (!empty($userInfo)) {

                //Merge current scope with new scopes
                $mergedScopes = array_unique(array_merge($this->explode($userInfo["scope"]), $this->explode($scopes)),SORT_REGULAR);

                //Remove Scopes
                $newScopes = array_diff($mergedScopes, $this->explode($remove_scope));

                //Update User
                $result = $this->get_oauth_storage()->setUser($user_id, $password, $email, $this->implode($newScopes));

                $this->response->setParameters(array('success' => $result));

            } else {
                $this->response->setParameters(array('success' => false, 'error' => 'invalid_user', 'error_description' => "User doesn't exist"));
            }
        }
        $this->response->send();
        die;
    }

    /**Insert new oauth client credentials*/
    public function createClient()
    {
        if ($this->get_oauth_server()->verifyResourceRequest(
            $this->request,
            $this->response,
            "$this->admin_scope $this->staff_scope $this->developer_scope")) {

            $client_id = $this->request->request('client_id');
            $redirect_uri = $this->request->request('redirect_url');
            $grant_types = $this->request->request('grant_types');
            $scope = $this->request->request('scope');
            $user_id = $this->request->request('user_id');

            $client_secret = md5(uniqid($client_id));

            $grants = $this->implode($grant_types);
            $scopes = $this->implode($scope);

            //Check if scope is valid
            $scopes = $this->get_oauth_storage()->scopeExists($scopes) ? $scopes : $this->get_oauth_storage()->getDefaultScope();

            //Insert Client
            $result = $this->get_oauth_storage()->setClientDetails($client_id, $client_secret, $redirect_uri, $grants, $scopes, $user_id);

            $this->response->setParameters(
                array('success' => $result,
                    'data' => [
                        'client_id' => $client_id,
                        'client_secret' => $client_secret
                    ]));

            $this->response->send();
        }

        die;
    }

}