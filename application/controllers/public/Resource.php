<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 12:20 PM
 */

class Resource extends Server
{

    private $request;
    private $response;

    public function __construct(){
        parent::__construct();

        $this->request = OAuth2\Request::createFromGlobals();
        $this->response = new OAuth2\Response();
    }

    /**Verify Token only*/
    public function verifyToken()
    {
        if ($this->get_oauth_server()->verifyResourceRequest($this->request,$this->response)) {
            $this->response->setParameters(array('success' => true, 'message' => 'Api access granted'));
        }
        $this->response->send();
        die;
    }

    /**Verify token and obtain data*/
    public function getTokenData()
    {
        if ($result = $this->get_oauth_server()->getAccessTokenData($this->request,$this->response)) {
            $this->response->setParameters(array('success' => true, 'data' => $result));
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
        if (!empty($access_token)){
            $done = $this->get_oauth_storage()->unsetAccessToken($access_token);
        }
        if (!empty($refresh_token)){
            $done = $this->get_oauth_storage()->unsetRefreshToken($refresh_token);
        }

        $this->response->setParameters(array('success' => $done));
        $this->response->send();
        die;
    }


    /**Insert New Oauth User*/
    public function createUser()
    {
        if ($this->get_oauth_server()->verifyResourceRequest($this->request,$this->response)){
            $user_id = $this->request->request('user_id');
            $password = $this->request->request('password');
            $email = $this->request->request('email');
            $scope = $this->request->request('scope');

            $scopes = to_string($scope);

            //Check if scope is valid
            $scopes = $this->get_oauth_storage()->scopeExists($scopes)?$scopes:$this->get_oauth_storage()->getDefaultScope();

            //Check if user exists
            $userInfo = $this->get_oauth_storage()->getUser($user_id,$email);
            if ($userInfo) {
                $this->response->setParameters(array('success' => false,'error'=>'duplicate_user','error_description'=>sprintf("User with email %s already exists",$email)));
            }
            else {

                //Insert User
                $result = $this->get_oauth_storage()->setUser($user_id,$password,$email,$scopes);
                $this->response->setParameters(array('success' => $result));
            }
        }
        $this->response->send();
        die;
    }




    /**Update Existing Oauth User*/
    public function updateUser()
    {
        if ($this->get_oauth_server()->verifyResourceRequest($this->request,$this->response)){
            $user_id = $this->request->request('user_id');
            $password = $this->request->request('password');
            $email = $this->request->request('email');
            $scope = $this->request->request('scope');

            $scopes = to_string($scope);

            //Check if scope is valid if it's available
            if (!empty($scopes)) {
                $scopes = $this->get_oauth_storage()->scopeExists($scopes)?$scopes:null;
            }

            //Check if user exists
            $userInfo = $this->get_oauth_storage()->getUser($user_id,$email);
            if ($userInfo)
            {
                //Update User
                $result = $this->get_oauth_storage()->setUser($user_id,$password,$email,$scopes);
                $this->response->setParameters(array('success' => $result));
            }
            else
            {
                $this->response->setParameters(array('success' => false,'error'=>'invalid_user','error_description'=>"User doesn't exist"));
            }
        }
        $this->response->send();
        die;
    }


    /**Insert new oauth client credentials*/
    public function createClient()
    {
        if ($this->get_oauth_server()->verifyResourceRequest($this->request,$this->response)){
            $client_id = $this->request->request('client_id');
            $redirect_uri= $this->request->request('redirect_url');
            $grant_types = $this->request->request('grant_types');
            $scope = $this->request->request('scope');
            $user_id = $this->request->request('user_id');

            $client_secret = md5(uniqid($client_id));

            $grants = to_string($grant_types);
            $scopes = to_string($scope);

            //Check if scope is valid
            $scopes = $this->get_oauth_storage()->scopeExists($scopes)?$scopes:$this->get_oauth_storage()->getDefaultScope();

            //Insert Client
            $result = $this->get_oauth_storage()->setClientDetails($client_id,$client_secret,$redirect_uri,$grants,$scopes,$user_id);

            $this->response->setParameters(array('success' => $result));

            $this->response->send();
        }

        die;
    }

}