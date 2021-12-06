<?php

use GuzzleHttp\RequestOptions;

defined('OAUTH_BASE_PATH') or exit('No direct script access allowed');

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
    const AUTH_REQ_TOKEN_PARAM = "auth_request_token";
    const EMAIL_REQ_TOKEN_PARAM = "email_request_token";

    public function __construct()
    {
        parent::__construct(false, true, true);
    }


    /**
     * Authorize token request
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
        $client_id = $this->request->query("client_id");
        $redirect_uri = $this->request->query("redirect_uri");
        $state = $this->request->query("state");
        $scope = $this->request->query("scope");
        $response_type = $this->request->query("response_type");

        // If email request - Validate and Send authorization url
        if (!empty($email = $this->request->query("email"))) {
            if ($userInfo = $this->processEmailRequest($email, $redirect_uri, $state, $scope)) {
                $this->showEmailSuccess($userInfo);
            } else {
                $this->showError("authorization_failed", $this->response->getParameter("error_description") ?? "Invalid request", $redirect_uri);
            }
        }

        //If Logged in
        else if ($user_id = App::getInstance()->getLoginUser()) {
            if ($this->processAuthRequest($user_id, $client_id, $redirect_uri, $state, $scope, $response_type)) {
                $this->response = $this->get_oauth_server()->handleAuthorizeRequest($this->request, $this->response, true, $user_id);
                $this->response->send();
                die();
            } else {
                $this->showError("authorization_failed", $this->response->getParameter("error_description") ?? "Invalid request", $redirect_uri);
            }
        }

        // Redirect to login
        else {
            App::getInstance()->redirect('authorize/login?redirect_url=' . urlencode(OAUTH_CURRENT_URL));
        }
    }

    /**
     * Login 
     *
     * @return void
     */
    public function login()
    {
        $redirect_url = $this->request->request("redirect_url", $this->request->query("redirect_url"));
        if (!empty($redirect_url)) {
            if ($userInfo = $this->processLoginRequest()) { //Process Login
                App::getInstance()->startLoginSession($userInfo['user_id'], 86400);
                App::getInstance()->redirect($redirect_url);
            } else {
                App::getInstance()->clearLoginSession();
                $this->showLogin([
                    "redirect_url" => $redirect_url,
                    "msg" => $this->response->getParameter("error_description")
                ]);
            }
        } else {
            $this->showError("login_failed", "A Redirect Url is required");
        }
    }

    /**
     * Login 
     *
     * @return void
     */
    public function logout()
    {
        $redirect_url = $this->request->request("redirect_url", $this->request->query("redirect_url"));
        if (!empty($redirect_url)) {
            App::getInstance()->clearLoginSession();
            App::getInstance()->delete_cookie(self::AUTH_REQ_TOKEN_PARAM);
            App::getInstance()->redirect('authorize/login?redirect_url=' . urlencode($redirect_url));
        } else {
            $this->showError("login_failed", "A Redirect Url is required");
        }
    }

    /**Process Login Request
     * @return boolean|array Array of user info or false if failed
     */
    private function processLoginRequest()
    {
        if ($data = $this->loginRequestAvailable()) {
            if ($this->validateRecaptcha(@$data['recaptcha_token']) && App::getInstance()->validate_csrf_token(@$data['csrf_token'])) {
                $max_count = 5;
                $timeout = 60;
                $count = App::getInstance()->get_cookie('request_count') ?? 0;
                if ($count < $max_count) { // Max request count
                    $count += 1;
                    App::getInstance()->set_cookie('request_count', $count, $timeout);
                    if ($userInfo = ($this->get_oauth_storage()->checkUserCredentials(@$data['username'], @$data['password']))) {
                        if (!empty($userInfo[@'user_id'])) {
                            return $userInfo;
                        } else {
                            $remaining_count = $max_count - $count;
                            $this->response->setParameters(array(
                                'success' => false,
                                'error' => 'invalid_user',
                                'error_description' => "Invalid Username or Password. $remaining_count tries left"
                            ));
                        }
                    } else {
                        $remaining_count = $max_count - $count;
                        $this->response->setParameters(array(
                            'success' => false,
                            'error' => 'invalid_user',
                            'error_description' => "Invalid Username or Password. $remaining_count tries left"
                        ));
                    }
                } else {
                    $min = intval($timeout / 60);
                    $this->response->setParameters(array(
                        'success' => false,
                        'error' => 'max_request',
                        'error_description' => "Maximum attempt reached. Please try again in $min minute(s)"
                    ));
                }
            } else {
                $this->response->setParameters(array(
                    'success' => false,
                    'error' => 'validation_error',
                    'error_description' => "Session validation failed. Please try again"
                ));
            }
        }
        return false;
    }

    /**
     * Process Email Authorization Request
     *
     * @param string $email
     * @param string $redirect_uri
     * @param string $state
     * @param string $scope
     * @return boolean|array
     */
    private function processEmailRequest($email, $redirect_uri, $state, $scope)
    {
        if ($userInfo = $this->get_oauth_storage()->getUser($email)) {

            $timeout = 600;
            $token = sha1(sprintf("%s:%s:%s:%s", $email, $redirect_uri, $state, $scope));
            if (App::getInstance()->get_cookie(self::EMAIL_REQ_TOKEN_PARAM) !== $token) {

                $user_id = $userInfo['user_id'];
                if ($this->get_oauth_storage()->scopeExistsForUser($scope, $user_id)) {

                    if ($is_authorized = $this->get_oauth_server()->validateAuthorizeRequest($this->request, $this->response)) {

                        $this->response = new OAuth2\Response(); //reinitialize response
                        $this->get_oauth_server()->handleAuthorizeRequest($this->request, $this->response, $is_authorized, $user_id);
                        $link = $this->response->getHttpHeader("Location");
                        $message = $this->getEmailAuthView($link);

                        if ($this->sendMail("Email Authorization", $message, $email)) {
                            try {
                                App::getInstance()->set_cookie(self::EMAIL_REQ_TOKEN_PARAM, $token, $timeout); //Save to cookie to prevent duplicate 
                                return $userInfo;
                            } catch (Exception $e) {
                                $this->showError("authorization_failed", sprintf("Unknown error. Please contact <a href='%s' target='_blank'>support</a> for assistance", App::getAppUrl('support')), $redirect_uri);
                            }
                        } else {
                            $this->showError("authorization_failed", sprintf("Failed to send mail. Please contact <a href='%s' target='_blank'>support</a> for assistance", App::getAppUrl('support')), $redirect_uri);
                        }
                    } else {
                        $msg = $this->response->getParameter("error_description");
                        $msg = !empty($msg) ? $msg : "Unexpected error encountered";
                        $this->showError("authorization_failed", sprintf("<span style='color:red'>$msg</span>. Please contact <a href='%s' target='_blank'>support</a> for assistance", App::getAppUrl('support')), $redirect_uri);
                    }
                } else {
                    $this->showError("authorization_failed", sprintf("Requested scope(s) does not exist for the specified user. Please contact <strong>%s</strong> for assistance", Configs::EMAIL_SUPPORT()), $redirect_uri);
                }
            } else {
                $min = intval($timeout / 60);
                $this->showError("duplicate_authorization", "Authorization link already sent to <strong>$email</strong>. Try again in $min minutes or clear browser cookies and retry");
            }
        }
        return false;
    }

    /**
     * Process Email Authorization Request
     *
     * @param string $user_id
     * @param string $client_id
     * @param string $redirect_uri
     * @param string $state
     * @param string $scope
     * @param string $response_type
     * @return boolean|array
     */
    private function processAuthRequest($user_id, $client_id, $redirect_uri, $state, $scope, $response_type)
    {
        $request_token = App::getInstance()->get_cookie(self::AUTH_REQ_TOKEN_PARAM);
        $token = sha1(sprintf("%s:%s:%s:%s:%s", $user_id, $client_id, $redirect_uri, $state, $scope, $response_type));

        $approve = $this->request->request("approve");
        $decline = $this->request->request("decline");

        // Authorization approved
        if ($approve && $request_token == $token) {

            $scope = !empty($scope) ? $scope : $this->get_oauth_storage()->getDefaultScope();

            if ($this->get_oauth_storage()->scopeExistsForUser($scope, $user_id)) {
                if ($this->get_oauth_server()->validateAuthorizeRequest($this->request, $this->response)) {
                    return true;
                } else {
                    App::getInstance()->delete_cookie(self::AUTH_REQ_TOKEN_PARAM);
                    $this->showError($this->response->getParameter("error"), $this->response->getParameter("error_description"), $redirect_uri);
                }
            } else {
                App::getInstance()->delete_cookie(self::AUTH_REQ_TOKEN_PARAM);
                $this->showError("invalid_scope", "Scope(s) '$scope' not available for this user", $redirect_uri);
            }
        }
        // Authorization approved
        else if ($decline && $request_token == $token) {
            App::getInstance()->delete_cookie(self::AUTH_REQ_TOKEN_PARAM);
            $this->showError("authorization_declined", "Access declined by user", $redirect_uri);
        }
        // Client Id available
        else if (!empty($client = $this->get_oauth_storage()->getClientDetails($client_id))) {

            App::getInstance()->set_cookie(self::AUTH_REQ_TOKEN_PARAM, $token, 300);
            $org = $this->get_oauth_storage()->getOrganizationDetails($client['org_id']);
            $user = $this->get_oauth_storage()->getUser($user_id);
            $scopes = $this->get_oauth_storage()->scopeExists($scope);
            return $this->showAuthorize([
                'client_name' => $client['client_name'],
                'org_name' => $org ? $org['org_name'] : null,
                'user_name' => $user ? $user['name'] : null,
                'user_email' => $user ? $user['email'] : null,
                'scopes' => $scopes ? array_map(function ($row) {
                    return $row['description'];
                }, $scopes) : [],
                'action' => OAUTH_CURRENT_URL,
            ]);
        }
        return false;
    }

    /**Show Login 
     * @param array $vars
     */
    private function showLogin($vars = array())
    {
        try {
            echo App::getInstance()->loadView("login", array_merge($vars, [
                'csrf_token' => App::getInstance()->generate_csrf_token(),
                'action' => ""
            ]), true);
            die;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    /**Show Authorization 
     * @param array $vars
     */
    private function showAuthorize($vars = array())
    {
        try {
            echo App::getInstance()->loadView("authorize", $vars, true);
            die;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }
    /**
     * Show Auth Email Success page
     * @param array $userInfo User Info
     * - name - string
     * - email - string
     * - dial_code - string
     * - phone - string
     */
    private function showEmailSuccess($userInfo)
    {
        try {
            echo App::getInstance()->loadView("success", $userInfo, true);
            die;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    /**
     * Show Error
     * @param string $error
     * @param string $error_description
     * @param string $redirect_uri
     */
    private function showError($error, $error_description = '', $redirect_uri = '')
    {
        if (!empty($redirect_uri)) {
            App::getInstance()->redirect(App::parseUrl($redirect_uri, [
                "error" => $error,
                "error_description" => $error_description
            ]));
        } else {
            try {
                echo App::getInstance()->loadView("failed", [
                    "msg" => ucfirst(str_replace('_', ' ', $error)),
                    "sub_msg" => $error_description
                ], true);
                die;
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }
        }
    }

    /**Check if login request available
     * @return array|boolean
     */
    private function loginRequestAvailable()
    {
        if (
            !empty($username = $this->request->request('username')) &&
            !empty($password = $this->request->request('password')) &&
            !empty($csrf_token = $this->request->request('csrf_token')) &&
            !empty($recaptcha_token = $this->request->request('recaptcha_token'))
        ) {
            return [
                'username' => $username,
                'password' => $password,
                'csrf_token' => $csrf_token,
                'recaptcha_token' => $recaptcha_token,
            ];
        }
        return false;
    }

    /**Get Email Authorization view to send as message to user
     * @param $link
     * @return string
     */
    private function getEmailAuthView($link)
    {
        $content = "<table style='max-width:500px;' border='0'>
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
            return App::getInstance()->loadView("simple_mail", ["content" => $content], true);
        } catch (Exception $e) {
            return $content;
        }
    }

    /** Validate Recaptcha
     * @return boolean
     */
    private function validateRecaptcha($token)
    {
        if (!empty($token)) {
            $client = new GuzzleHttp\Client();
            $res = $client->post('https://www.google.com/recaptcha/api/siteverify', [
                RequestOptions::FORM_PARAMS => [
                    'secret' => Configs::RECAPTCHA_SECRET_KEY(),
                    'response' => $token,
                    'remoteip' => IPADDRESS,
                ]
            ]);
            if ($res->getStatusCode() == 200) {
                $data = json_decode($res->getBody()->getContents(), true);
                if ($data && @$data['success']) {
                    return true;
                }
            }
        }
        return false;
    }
}
