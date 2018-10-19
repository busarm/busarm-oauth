<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

require_once OAUTH_BASE_PATH.'Server.php';

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/25/2018
 * Time: 8:52 PM
 *
 * **Note Authorization takes on the "scope" of the client
 *   and only that scope can be authorized
 */

class Authorize extends Server
{
    private $request;
    private $response;

    public function __construct()
    {
        parent::__construct();

        $this->request = OAuth2\Request::createFromGlobals();
        $this->response = new OAuth2\Response();
    }


    /**Authorize token request
     * (If using authorization_code grant type)
     * or Implicit Authorization request
     */
    public function request()
    {
        $user_id = $this->request->query("user_id");
        $scope = $this->request->query("scope");

        if ($userInfo = $this->processLoginRequest()){
            $user_id = $userInfo['user_id'];
            $scope = !empty($scope)?$scope:$this->get_oauth_storage()->getDefaultScope();
            $is_authorized = $this->get_oauth_storage()->scopeExistsForUser($scope,$user_id);
            if (!$is_authorized){
                $this->response->setParameters(array('success' => false,'error'=>'invalid_scope','error_description'=>"Unsupported scope requested"));
            }
        }
        else {
            if (!empty($user_id)) {
                if ($userInfo = $this->get_oauth_storage()->getUser($user_id,$user_id)){
                    $this->showAuthorizationForm(["msg"=>$this->response->getParameter("error_description")]);
                    die();
                }
                else {
                    $this->showAuthorizationForm(["msg"=>$this->response->getParameter("error_description")]);
                    die();
                }
            }
            else{
                $this->showAuthorizationForm(["msg"=>$this->response->getParameter("error_description")]);
                die();
            }
        }


        $is_authorized = $is_authorized && $this->get_oauth_server()->validateAuthorizeRequest($this->request, $this->response);
        if ($is_authorized) {
            $this->get_oauth_server()->handleAuthorizeRequest($this->request, $this->response, $is_authorized, $user_id);
        }
        else {
            $this->showAuthorizationForm(["msg"=>$this->response->getParameter("error_description")]);
            die();
        }

        $this->response->send();
        die();
    }

    /**Process Login Request
     * @return boolean|array Array of user info or false if failed
     */
    private function processLoginRequest(){
        if ($data = $this->loginRequestAvailable()) {
            if ($this->csrf_validation(@$data['csrf_token'])) {
                if ($userInfo = ($this->get_oauth_storage()->checkUserCredentials(@$data['username'], @$data['password']))) {
                    if (!empty($userInfo[@'user_id'])) {
                        return $userInfo;
                    }
                    else
                        $this->response->setParameters(array(
                            'success' => false,
                            'error'=>'invalid_user',
                            'error_description'=>"Invalid Username or Password"));
                }
                else
                    $this->response->setParameters(array(
                        'success' => false,
                        'error'=>'invalid_user',
                        'error_description'=>"Invalid Username or Password"));
            }
            else
                $this->response->setParameters(array(
                    'success' => false,
                    'error'=>'validation_error',
                    'error_description'=>"CSRF Validation failed"));
        }
        return false;
    }

    /**Show from for use to enter their
     * authorization credentials
     * @param array $vars
     */
    private function showAuthorizationForm($vars = array())
    {
        try {
            echo loadView("login",$vars,true);
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**Check if login request available
     * @return array|boolean
     */
    private function loginRequestAvailable()
    {
        if (!empty($username = $this->request->request('username')) &&
            !empty($password = $this->request->request('password')) &&
            !empty($csrf_token = $this->request->request('csrf_token')))
        {
            return[
                'username' => $username,
                'password' => $password,
                'csrf_token' => $csrf_token,
            ];
        }
        return false;
    }

    /**CSRF Validation
     * @param string $csrf_token
     * @return array|boolean
     */
    private function csrf_validation($csrf_token)
    {
        if (isset($_SESSION["csrf_token"])){
            return $csrf_token == encode_csrf_token($_SESSION['csrf_token']);
        }
        return false;
    }
}