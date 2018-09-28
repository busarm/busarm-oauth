<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

require_once OAUTH_BASE_PATH.'Server.php';

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 12:20 PM
 */
class Resource extends Server
{

    public function __construct()
    {
        parent::__construct();
    }

    /**Verify Token only*/
    public function verifyToken()
    {
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();

        $result = $this->get_oauth_server()->verifyResourceRequest($request,$response);

        if ($result)
        {
            $response->setParameters(array('success' => true, 'message' => 'Api access granted'));
        }

        $response->send();
        die;
    }

    /**Verify token and obtain data*/
    public function getTokenData()
    {
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();

        $result = $this->get_oauth_server()->getAccessTokenData($request,$response);
        
        if ($result)
        {
            $response->setParameters(array('success' => true, 'data' => $result));
        }

        $response->send();
        die;
    }


    /**Delete Access token and refresh token*/
    public function removeAccess()
    {
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();

        $access_token = $request->request('access_token');
        $refresh_token = $request->request('refresh_token');

        $done = false;
        if (!empty($access_token)){
            $done = $this->get_oauth_storage()->unsetAccessToken($access_token);

        }
        if (!empty($refresh_token)){
            $done = $this->get_oauth_storage()->unsetRefreshToken($refresh_token);
        }

        $response->setParameters(array('success' => $done));
        $response->send();
        die;
    }




    /**Insert New Oauth User*/
    public function createUser()
    {
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();

        $user_id = $request->request('user_id');
        $password = $request->request('password');
        $email = $request->request('email');
        $scope = $request->request('scope');

        //Check if scope is valid
        $scope = $this->get_oauth_storage()->scopeExists($scope)?$scope:null;

        if (empty($scope))
        {
            $response->setParameters(array('success' => false,'error'=>'Invalid Scope','error_description'=>'Failed to register user with the specified scope'));
        }
        else
        {
            //Check if email with scope exists
            $userInfo = $this->get_oauth_storage()->getUserWithEmailAndScope($email,$scope);
            if ($userInfo)
            {
                $response->setParameters(array('success' => false,'error'=>'invalid_grant','error_description'=>sprintf("%s with email %s already exists",$scope,$email)));
            }
            else
            {
                //Insert User
                $result = $this->get_oauth_storage()->setUser($user_id,$password,$email,$scope);

                $response->setParameters(array('success' => $result));
            }
        }
        $response->send();

        die;
    }




    /**Update Existing Oauth User*/
    public function updateUser()
    {
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();

        $user_id = $request->request('user_id');
        $password = $request->request('password');
        $email = $request->request('email');
        $scope = $request->request('scope');

        //Check if scope is valid if it's available
        if (!empty($scope)) {
            $scope = $this->get_oauth_storage()->scopeExists($scope)?$scope:null;
        }

        //Check if email with scope exists
        $userInfo = $this->get_oauth_storage()->getUser($user_id);
        if ($userInfo)
        {
            //Update User
            $result = $this->get_oauth_storage()->setUser($user_id,$password,$email,$scope);
            $response->setParameters(array('success' => $result));
        }
        else
        {
            $response->setParameters(array('success' => false,'error'=>'invalid_grant','error_description'=>sprintf("%s with email %s doesn't exist",$scope,$email)));
        }
        $response->send();
        die;
    }


    /**Insert new oauth client credentials*/
    public function createClient()
    {

        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();

        $client_id = $request->request('client_id');
        $redirect_uri= $request->request('redirect_url');
        $grant_types = $request->request('grant_types');
        $scope = $request->request('scope');
        $user_id = $request->request('user_id');

        $client_secret = md5(uniqid($client_id));

        $grants = json_decode($grant_types);
        $grant_types_str  = "";

        //Check if scope is valid
        $scope = $this->get_oauth_storage()->scopeExists($scope)?$scope:$this->get_oauth_storage()->getDefaultScope();

        //Parse grant type array to string
        if (isset($grants))
        {
            foreach ($grants as $grant)
                $grant_types_str .= $grant . ' ';
        }
        $grant_types_str = trim($grant_types_str, ' ');

        //Insert Client
        $result = $this->get_oauth_storage()->setClientDetails($client_id,$client_secret,$redirect_uri,$grant_types_str,$scope,$user_id);

        $response->setParameters(array('success' => $result));

        $response->send();
        die;
    }

}