<?php

namespace Application\Controllers;

use Exception;
use GuzzleHttp\RequestOptions;
use System\App;
use System\CIPHER;
use System\Configs;
use System\Scopes;
use System\Server;
use System\URL;
use System\Utils;

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
        parent::__construct(false, true);
    }

    /**
     * Authorize token request
     * (If using authorization_code grant type)
     * or Implicit Authorization request
     * @api authorize/request
     * @method GET
     * @query email String Required for Email authorization
     * @query client_id String Required
     * @query state String Required
     * @query redirect_uri String Required
     * @query response_type String Required
     * @query scope String Required (Space separated e.g 'user admin')
     */
    public function request()
    {
        $client_id = $this->request->query("client_id");
        $redirect_uri = $this->request->query("redirect_uri");
        $state = $this->request->query("state");
        $scope = $this->request->query("scope");
        $response_type = $this->request->query("response_type");

        // Use openId and implicit grant if requested
        if (
            !$this->getOauthServer()->getScopeUtil()->checkScope($scope, Scopes::SCOPE_OPENID)
            && str_contains($response_type, 'id_token')
        ) {
            $this->getOauthServer()->setConfig('allow_implicit', true);
            $this->getOauthServer()->setConfig('use_openid_connect', true);
        }

        // If email request - Validate and Send authorization url
        if (!empty($email = $this->request->query("email"))) {
            if ($userInfo = $this->processEmailRequest($email, $redirect_uri, $state, $scope)) {
                $this->showEmailSuccess($userInfo);
            } else {
                $this->showError("authorization_failed", $this->response->getParameter("error_description") ?? "Invalid request", $redirect_uri);
            }
        }

        //If Logged in
        else if ($user_id = $this->getLoginUser()) {
            if ($this->processAuthRequest($user_id, $client_id, $redirect_uri, $state, $scope, $response_type)) {
                $this->response = $this->getOauthServer()->handleAuthorizeRequest($this->request, $this->response, true, $user_id);
                $this->response->send();
                die();
            } else {
                $this->showError("authorization_failed", $this->response->getParameter("error_description") ?? "Invalid request", $redirect_uri);
            }
        }

        // Redirect to login
        else {
            URL::redirect('authorize/login?redirect_url=' . urlencode(CURRENT_URL));
        }
    }

    /**
     * Login 
     *
     * @api authorize/login
     * @method GET
     * @body username String Private - Required
     * @body password String Private - Required
     * @body csrf_token String Private - Required
     * @body recaptcha_token String Private - Required
     * @body redirect_url String - Required
     */
    public function login()
    {
        $redirect_url = $this->request->request("redirect_url", $this->request->query("redirect_url"));
        if (!empty($redirect_url)) {
            if ($userInfo = $this->processLoginRequest()) { //Process Login
                $this->startLoginSession($userInfo['user_id'], 86400);
                URL::redirect($redirect_url);
            } else {
                $this->clearLoginSession();
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
     * @api authorize/logout
     * @method GET
     * @query redirect_url String - Required
     */
    public function logout()
    {
        $redirect_url = $this->request->request("redirect_url", $this->request->query("redirect_url"));
        if (!empty($redirect_url)) {
            $this->clearLoginSession();
            Utils::delete_cookie(self::AUTH_REQ_TOKEN_PARAM);
            URL::redirect('authorize/login?redirect_url=' . urlencode($redirect_url));
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
            if ($this->validateRecaptcha(@$data['recaptcha_token']) && Utils::validate_csrf_token(@$data['csrf_token'])) {
                $max_count = 5;
                $timeout = 60;
                $count = Utils::get_cookie('request_count') ?? 0;
                if ($count < $max_count) { // Max request count
                    $count += 1;
                    Utils::set_cookie('request_count', $count, $timeout);
                    if ($userInfo = ($this->getOauthStorage()->checkUserCredentials(@$data['username'], @$data['password']))) {
                        if (!empty($userInfo[@'user_id'])) {
                            return $userInfo;
                        } else {
                            $remaining_count = $max_count - $count;
                            $this->response->setParameters($this->error("Invalid Username or Password. $remaining_count tries left", 'invalid_user'));
                        }
                    } else {
                        $remaining_count = $max_count - $count;
                        $this->response->setParameters($this->error("Invalid Username or Password. $remaining_count tries left", 'invalid_user'));
                    }
                } else {
                    $min = intval($timeout / 60);
                    $this->response->setParameters($this->error("Maximum attempt reached. Please try again in $min minute(s)', 'max_request"));
                }
            } else {
                $this->response->setParameters($this->error('Session validation failed. Please try again', 'validation_error'));
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
        if ($user = $this->getOauthStorage()->getUser($email)) {

            $timeout = 600;
            $token = sha1(sprintf("%s:%s:%s:%s", $email, $redirect_uri, $state, $scope));
            if (Utils::get_cookie(self::EMAIL_REQ_TOKEN_PARAM) !== $token) {

                $user_id = $user['user_id'];
                if (empty($scope) || $this->getOauthStorage()->scopeExistsForUser($scope, $user_id)) {

                    if ($is_authorized = $this->getOauthServer()->validateAuthorizeRequest($this->request, $this->response)) {

                        $this->response = new \OAuth2\Response(); //reinitialize response
                        $this->getOauthServer()->handleAuthorizeRequest($this->request, $this->response, $is_authorized, $user_id);

                        $message = $this->getEmailAuthView($this->response->getHttpHeader("Location"));

                        if ($this->sendMail("Email Authorization", $message, $email)) {
                            try {
                                Utils::set_cookie(self::EMAIL_REQ_TOKEN_PARAM, $token, $timeout); //Save to cookie to prevent duplicate 
                                return $user;
                            } catch (Exception $e) {
                                App::reportException($e); // Report
                                $this->showError("authorization_failed", sprintf("Unknown error. Please contact <a href='%s' target='_blank'>support</a> for assistance", URL::appUrl(URL::APP_SUPPORT_PATH)), $redirect_uri);
                            }
                        } else {
                            $this->showError("authorization_failed", sprintf("Failed to send mail. Please contact <a href='%s' target='_blank'>support</a> for assistance", URL::appUrl(URL::APP_SUPPORT_PATH)), $redirect_uri);
                        }
                    } else {
                        $msg = $this->response->getParameter("error_description");
                        $msg = !empty($msg) ? $msg : "Unexpected error encountered";
                        $this->showError("authorization_failed", sprintf("<span style='color:red'>$msg</span>. Please contact <a href='%s' target='_blank'>support</a> for assistance", URL::appUrl(URL::APP_SUPPORT_PATH)), $redirect_uri);
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
        $request_token = Utils::get_cookie(self::AUTH_REQ_TOKEN_PARAM);
        $token = sha1(sprintf("%s:%s:%s:%s:%s", $user_id, $client_id, $redirect_uri, $state, $scope, $response_type));

        $approve = $this->request->request("approve");
        $decline = $this->request->request("decline");

        // Authorization approved
        if ($approve && $request_token == $token) {

            $scope = !empty($scope) ? $scope : $this->getOauthServer()->getScopeUtil()->getDefaultScope();

            if ($this->getOauthStorage()->scopeExistsForUser($scope, $user_id)) {
                if ($this->getOauthServer()->validateAuthorizeRequest($this->request, $this->response)) {
                    return true;
                } else {
                    Utils::delete_cookie(self::AUTH_REQ_TOKEN_PARAM);
                    $this->showError($this->response->getParameter("error"), $this->response->getParameter("error_description"), $redirect_uri);
                }
            } else {
                Utils::delete_cookie(self::AUTH_REQ_TOKEN_PARAM);
                $this->showError("invalid_scope", "Scope(s) '$scope' not available for this user", $redirect_uri);
            }
        }
        // Authorization approved
        else if ($decline && $request_token == $token) {
            Utils::delete_cookie(self::AUTH_REQ_TOKEN_PARAM);
            $this->showError("authorization_declined", "Access declined by user", $redirect_uri);
        }
        // Client Id available
        else if (!empty($client = $this->getOauthStorage()->getClientDetails($client_id))) {

            Utils::set_cookie(self::AUTH_REQ_TOKEN_PARAM, $token, 300);
            $org = $this->getOauthStorage()->getOrganizationDetails($client['org_id']);
            $user = $this->getOauthStorage()->getUser($user_id);
            $scopes = Scopes::findScope($scope);
            return $this->showAuthorize([
                'client_name' => $client['client_name'],
                'org_name' => $org ? $org['org_name'] : null,
                'user_name' => $user ? $user['name'] : null,
                'user_email' => $user ? $user['email'] : null,
                'scopes' => $scopes ? array_values($scopes) : [],
                'action' => CURRENT_URL,
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
            echo app()->loadView("login", array_merge($vars, [
                'csrf_token' => Utils::generate_csrf_token(),
                'action' => ""
            ]), true);
            die;
        } catch (Exception $e) {
            App::reportException($e); // Report
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
            echo app()->loadView("authorize", $vars, true);
            die;
        } catch (Exception $e) {
            App::reportException($e); // Report
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
            echo app()->loadView("success", $userInfo, true);
            die;
        } catch (Exception $e) {
            App::reportException($e); // Report
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
        // Report
        App::reportError(ucfirst(str_replace('_', ' ', $error)), $error_description);

        if (!empty($redirect_uri)) {
            URL::redirect(URL::parseUrl($redirect_uri, [
                "error" => $error,
                "error_description" => $error_description
            ]));
        } else {
            try {
                echo app()->loadView("failed", [
                    "msg" => ucfirst(str_replace('_', ' ', $error)),
                    "desc" => $error_description
                ], true);
                die;
            } catch (Exception $e) {
                App::reportException($e); // Report
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
        try {
            $content = app()->loadView("email/auth", ["link" => $link], true);
            return app()->loadView("email/template/simple_mail", ["content" => $content], true);
        } catch (Exception $e) {
            App::reportException($e); // Report
            return $content;
        }
    }

    /** Validate Recaptcha
     * @return boolean
     */
    private function validateRecaptcha($token)
    {
        if (!empty($token)) {
            $client = new \GuzzleHttp\Client();
            $res = $client->post(URL::GOOGLE_RECAPTCHA_VERFY_URL, [
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
