<?php

use OAuth2\Controller\TokenController;

defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

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
    public function __construct(){
        parent::__construct(false, true);
    }


    /**Authorize token request
     * (If using authorization_code grant type)
     * or Implicit Authorization request
     * @api authorize/request
     * @method GET
     * @param user_id String Optional if email available 
     * @param email String Optional if user_id available 
     * @param state String Required
     * @param redirect_uri String Required
     * @param scope String Required (Space separated e.g 'user admin')
     * @param username String Private - Required for user credentials authorization login
     * @param password String Private - Required for user credentials authorization login
     * @param csrf_token String Private - Required for user credentials authorization login
     */
    public function request()
    {
        $user_id = $this->request->query("user_id");
        $email = $this->request->query("email");
        $scope = $this->request->query("scope");

        if ($userInfo = $this->processLoginRequest()) { //Process Login
            $user_id = $userInfo['user_id'];
            $scope = !empty($scope) ? $scope : $this->get_oauth_storage()->getDefaultScope();

            $is_authorized = $this->get_oauth_storage()->scopeExistsForUser($scope, $user_id);
            if ($is_authorized) {

                $is_authorized = $this->get_oauth_server()->validateAuthorizeRequest($this->request, $this->response);
                if ($is_authorized) {
                    $this->get_oauth_server()->handleAuthorizeRequest($this->request, $this->response, $is_authorized, $user_id);
                    $this->response->send();
                    die();
                } else {
                    $this->showAuthorizationForm(["msg" => $this->response->getParameter("error_description")]);
                }

            } else {
                $this->showAuthorizationForm(["msg" => "Scope(s) '$scope' not available for this user"]);
            }
        }
        else {
            if (!empty($email)) { //Validate and Send authorization url
                $state = $this->request->query("state");
                $redirect_uri = $this->request->query("redirect_uri");
                if ($userInfo = $this->processEmailRequest($email,$redirect_uri,$state,$scope)) {
                    $this->showEmailSuccess($userInfo);
                }
                else {
                    $this->showAuthorizationForm();
                }
            }
            else if (!empty($user_id)) { //validate and Show authorization form for user
                if ($userInfo = $this->get_oauth_storage()->getUser($user_id)) {
                    $this->showAuthorizationForm($userInfo);
                }
                else {
                    $this->showAuthorizationForm(["msg" => $this->response->getParameter("error_description")]);
                }
            }
            else {
                $this->showAuthorizationForm(["msg" => $this->response->getParameter("error_description")]);
            }
        }
    }

    /**Process Login Request
     * @return boolean|array Array of user info or false if failed
     */
    private function processLoginRequest()
    {
        if ($data = $this->loginRequestAvailable()) {
            if (OAUTH_APP::getInstance()->validate_csrf_token(@$data['csrf_token'])) {
                if ($userInfo = ($this->get_oauth_storage()->checkUserCredentials(@$data['username'], @$data['password']))) {
                    if (!empty($userInfo[@'user_id'])) {
                        return $userInfo;
                    } else
                        $this->response->setParameters(array(
                            'success' => false,
                            'error' => 'invalid_user',
                            'error_description' => "Invalid Username or Password"));
                } else {
                    $this->response->setParameters(array(
                        'success' => false,
                        'error' => 'invalid_user',
                        'error_description' => "Invalid Username or Password"));
                }
            } else
                $this->response->setParameters(array(
                    'success' => false,
                    'error' => 'validation_error',
                    'error_description' => "CSRF Validation failed"));
        }
        return false;
    }


    /**
     * Process Email Authorization Request
     *
     * @param [string] $email
     * @param [string] $redirect_uri
     * @param [string] $state
     * @param [string] $scope
     * @return boolean|array
     */
    private function processEmailRequest($email, $redirect_uri, $state, $scope)
    {
        if ($userInfo = $this->get_oauth_storage()->getUser($email)) {
            $hash = md5(sprintf("%s:/%s:/%s", $email, $redirect_uri, $state));
            if (OAUTH_APP::getInstance()->get_cookie('request_hash') != $hash) {
                $user_id = $userInfo['user_id'];
                if ($this->get_oauth_storage()->scopeExistsForUser($scope, $user_id)) {
                    if ($is_authorized = $this->get_oauth_server()->validateAuthorizeRequest($this->request, $this->response)) {
                        $this->response = new OAuth2\Response(); //reinitialize response
                        $this->get_oauth_server()->handleAuthorizeRequest($this->request, $this->response, $is_authorized, $user_id);
                        $link = $this->response->getHttpHeader("Location");
                        $message = $this->getEmailAuthView($link);
                        if ($this->sendMail("Email Authorization", $message, $email)) {
                            try {
                                OAUTH_APP::getInstance()->set_cookie("request_hash", $hash); //Save to cookie to prevent duplicate 
                                return $userInfo;
                            }
                            catch (Exception $e) {
                                $this->showEmailFailed([
                                    "msg"=>"Authorization failedd",
                                    "sub_msg"=>sprintf("Unknown error. Please contact <strong>%s</strong> for assistance",$this->getSupportEmail()),
                                    ]);
                            }
                        }
                        else {
                            $this->showEmailFailed([
                                "msg"=>"Authorization failedd",
                                "sub_msg"=>sprintf("Failed to send mail. Please contact <strong>%s</strong> for assistance",$this->getSupportEmail()),
                                ]);
                        }
                    }
                    else {
                        $msg = $this->response->getParameter("error_description");  
                        $msg = !empty($msg) ? $msg:"Unexpected error encountered";
                        $this->showEmailFailed([
                            "msg"=>"Authorization failed",
                            "sub_msg"=>sprintf("$msg. Please contact <strong>%s</strong> for assistance",$this->getSupportEmail()),
                        ]);
                    }
                }
                else {
                    $this->showEmailFailed([
                        "msg"=>"Authorization failed",
                        "sub_msg"=>sprintf("Requested scope(s) does not exist for the specified user. Please contact <strong>%s</strong> for assistance",$this->getSupportEmail()),
                    ]);
                }
            }
            else {
                $this->showEmailFailed([
                    "msg"=>"Authorization link already sent to <strong>$email</strong>",
                    "sub_msg"=>"Change your email or clear browser cookies and retry",
                ]);
            }
        }
        else {
            $this->showAuthorizationForm();
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
            echo OAUTH_APP::getInstance()->loadView("login", array_merge($vars, [
                'csrf_token' => OAUTH_APP::getInstance()->generate_csrf_token(),
                'action' => ""
            ]), true);
            die;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    /**Show Auth Email Success page
     * @param array $vars
     */
    private function showEmailSuccess($vars = array())
    {
        try {
            echo OAUTH_APP::getInstance()->loadView("success", $vars, true);
            die;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }
    /**Show Auth Email Failure page
     * @param array $vars
     */
    private function showEmailFailed($vars = array())
    {
        try {
            echo OAUTH_APP::getInstance()->loadView("failed", $vars, true);
            die;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    /**Get Email Authorization view to send as message to user
     * @param $link
     * @return string
     */
    private function getEmailAuthView($link)
    {
        $content = "<table border='0'>
                      <tr width='350'>
                        <td align='center'><h2>Click the url below to access your account</h2></td>
                      </tr>
                      <tr width='350'>
                        <td><a href='$link'>$link</a></td>
                      </tr>
                      <tr width='350'> 
                        <br/>
                        <br/>
                      </tr>
                      <tr width='350'>
                        <td align='center'><span style='font-size:14px !important; color: #0b2e13;'>This link can only be used <strong>ONCE</strong> and will expire in <strong>AN HOUR</strong></span></td>
                      </tr>
                      <tr width='350'>
                        <td align='center'><strong style='font-size:12px !important;; color: #9d223c;'>(Please ignore this message if it wasn't triggered or requested by you)</strong></td>
                      </tr>
                    </table>";
        try {
            return OAUTH_APP::getInstance()->loadView("simple_mail", ["content" => $content], true);
        } catch (Exception $e) {
            return $content;
        }
    }

    /**Check if login request available
     * @return array|boolean
     */
    private function loginRequestAvailable()
    {
        if (!empty($username = $this->request->request('username')) &&
            !empty($password = $this->request->request('password')) &&
            !empty($csrf_token = $this->request->request('csrf_token'))) {
            return [
                'username' => $username,
                'password' => $password,
                'csrf_token' => $csrf_token,
            ];
        }
        return false;
    }
}