<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

require_once OAUTH_BASE_PATH.'Server.php';

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/25/2018
 * Time: 8:52 PM
 */

class Authorize extends Server
{
    public function __construct()
    {
        parent::__construct();
    }

    /**Authorize token request
     * (If using authorization_code grant type)
     * or Implicit Authorization request
     */
    public function request()
    {
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();
        $is_authorized = false;
        $user_id = null;

        if($data = $this->loginRequestAvailable()) {
            if ($userInfo = ($this->get_oauth_storage()->checkUserCredentials(@$data['username'], @$data['password']))) {
                if (!empty($userInfo[@'user_id'])) {
                    $user_id = $userInfo['user_id'];
                    $is_authorized = true;
                }
            }
        }
        else {
            $this->showAuthorizationForm();
            die();
        }

        $is_authorized = $is_authorized && $this->get_oauth_server()->validateAuthorizeRequest($request, $response);
        if ($is_authorized) {
            $this->get_oauth_server()->handleAuthorizeRequest($request, $response, $is_authorized, $user_id);
        }
        else {
            $resp = $response->getParameter("error_description");
            echo "<script>alert('Authentication Failed. $resp')</script>";

            $this->showAuthorizationForm();
            die();
        }

        $response->send();
        die();
    }


    /**Show from for use to enter their
     * authorization credentials
     */
    private function showAuthorizationForm()
    {
        try
        {
            loadView("login");
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
        if (isset($_POST['username']) &&
            !empty($username = $_POST['username']) &&
            isset($_POST['username']) &&
            !empty($password = $_POST['password']))
        {
            return[
                'username' => $username,
                'password' => $password
            ];
        }
        return false;
    }
}