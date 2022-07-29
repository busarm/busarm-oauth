<?php

namespace Application\Controllers\HTTP;

use Application\Controllers\OAuthBaseController;
use Application\Services\MailService;
use Exception;
use GuzzleHttp\RequestOptions;
use  Application\Services\OAuthScopeService;
use Application\Helpers\URL;
use Application\Helpers\Utils;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/25/2018
 * Time: 8:52 PM
 *
 * **Note Authorization takes on the "scope" of the client
 *   and only that scope can be authorized
 */
class Authorize extends OAuthBaseController
{
    const AUTH_REQ_TOKEN_PARAM = "auth_request_token";
    const EMAIL_REQ_TOKEN_PARAM = "email_request_token";

    public function __construct()
    {
        parent::__construct(false);
    }

    /**
     * Authorize token request
     * (If using authorization_code grant type)
     * or Implicit Authorization request
     * 
     * @query email String Required for Email authorization
     * @query client_id String Required
     * @query state String Required
     * @query redirect_uri String Required
     * @query response_type String Required
     * @query scope String Required (Space separated e.g 'user admin')
     */
    public function request()
    {
        $client_id = $this->oauth->request->query("client_id");
        $redirect_uri = $this->oauth->request->query("redirect_uri");
        $state = $this->oauth->request->query("state");
        $scope = $this->oauth->request->query("scope");
        $response_type = $this->oauth->request->query("response_type");

        // Use openId and implicit grant if requested
        if (
            !$this->oauth->server->getScopeUtil()->checkScope($scope, SCOPE_OPENID)
            && str_contains($response_type, 'id_token')
        ) {
            $this->oauth->server->setConfig('allow_implicit', true);
            $this->oauth->server->setConfig('use_openid_connect', true);
        }

        // If email request - Validate and Send authorization url
        if (!empty($email = $this->oauth->request->query("email"))) {
            if ($userInfo = $this->processEmailRequest($email, $redirect_uri, $state, $scope)) {
                $this->showEmailSuccess($userInfo);
            } else {
                $this->showError("authorization_failed", $this->oauth->response->getParameter("error_description") ?? "Invalid request", $redirect_uri);
            }
        }

        // If Logged in
        else if ($user_id = $this->auth->getLoginUser()) {
            if ($this->processAuthRequest($user_id, $client_id, $redirect_uri, $state, $scope, $response_type)) {
                $this->oauth->server->handleAuthorizeRequest($this->oauth->request, $this->oauth->response, true, $user_id);
                $this->oauth->response->send();
                die();
            } else {
                $this->showError("authorization_failed", $this->oauth->response->getParameter("error_description") ?? "Invalid request", $redirect_uri);
            }
        }

        // Redirect to login
        else {
            URL::redirect('authorize/login?redirect_url=' . urlencode(CURRENT_URL));
        }
    }

    /**
     * Login 
     * @body username String Private - Required
     * @body password String Private - Required
     * @body csrf_token String Private - Required
     * @body recaptcha_token String Private - Required
     * @body redirect_url String - Required
     */
    public function login()
    {
        $redirect_url = $this->oauth->request->request("redirect_url", $this->oauth->request->query("redirect_url"));
        if (!empty($redirect_url)) {
            if ($userInfo = $this->processLoginRequest()) { //Process Login
                $this->auth->startLoginSession($userInfo['user_id'], 86400);
                URL::redirect($redirect_url);
            } else {
                $this->auth->clearLoginSession();
                $this->showLogin([
                    "redirect_url" => $redirect_url,
                    "msg" => $this->oauth->response->getParameter("error_description")
                ]);
            }
        } else {
            $this->showError("login_failed", "A Redirect Url is required");
        }
    }

    /**
     * Login 
     * @query redirect_url String - Required
     */
    public function logout()
    {
        $redirect_url = $this->oauth->request->request("redirect_url", $this->oauth->request->query("redirect_url"));
        if (!empty($redirect_url)) {
            $this->auth->clearLoginSession();
            Utils::deleteCookie(self::AUTH_REQ_TOKEN_PARAM);
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
            if ($this->validateRecaptcha(@$data['recaptcha_token']) && Utils::validateCsrf(@$data['csrf_token'])) {
                $max_count = 5;
                $timeout = 60;
                $count = Utils::getCookie('request_count') ?? 0;
                // Max request count
                if ($count < $max_count) { 
                    $count += 1;
                    Utils::setCookie('request_count', $count, $timeout);
                    if ($userInfo = ($this->oauth->storage->checkUserCredentials(@$data['username'], @$data['password']))) {
                        if (!empty($userInfo[@'user_id'])) {
                            return $userInfo;
                        } else {
                            $remaining_count = $max_count - $count;
                            $this->oauth->response->setParameters($this->error("Invalid Username or Password. $remaining_count tries left", 'invalid_user'));
                        }
                    } else {
                        $remaining_count = $max_count - $count;
                        $this->oauth->response->setParameters($this->error("Invalid Username or Password. $remaining_count tries left", 'invalid_user'));
                    }
                } else {
                    $min = intval($timeout / 60);
                    $this->oauth->response->setParameters($this->error("Maximum attempt reached. Please try again in $min minute(s)", 'max_request'));
                }
            } else {
                $this->oauth->response->setParameters($this->error('Session validation failed. Please try again', 'validation_error'));
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
        if ($user = $this->oauth->storage->getUser($email)) {

            $timeout = 600;
            $token = sha1(sprintf("%s:%s:%s:%s", $email, $redirect_uri, $state, $scope));
            if (Utils::getCookie(self::EMAIL_REQ_TOKEN_PARAM) !== $token) {

                $user_id = $user['user_id'];
                if (empty($scope) || $this->oauth->storage->scopeExistsForUser($scope, $user_id)) {

                    if ($is_authorized = $this->oauth->server->validateAuthorizeRequest($this->oauth->request, $this->oauth->response)) {

                        // Re-initialize response
                        $this->oauth->response = new \OAuth2\Response();
                        $this->oauth->server->handleAuthorizeRequest($this->oauth->request, $this->oauth->response, $is_authorized, $user_id);

                        $message = $this->getEmailAuthView($this->oauth->response->getHttpHeader("Location"));

                        // Send email
                        if ((new MailService)->send("Email Authorization", $message, $email)) {
                            try {
                                // Save to cookie to prevent duplicate 
                                Utils::setCookie(self::EMAIL_REQ_TOKEN_PARAM, $token, $timeout);
                                return $user;
                            } catch (Exception $e) {
                                app()->reportException($e); // Report
                                $this->showError("authorization_failed", sprintf("Unknown error. Please contact <a href='%s' target='_blank'>support</a> for assistance", URL::appUrl(URL::APP_SUPPORT_PATH)), $redirect_uri);
                            }
                        } else {
                            $this->showError("authorization_failed", sprintf("Failed to send mail. Please contact <a href='%s' target='_blank'>support</a> for assistance", URL::appUrl(URL::APP_SUPPORT_PATH)), $redirect_uri);
                        }
                    } else {
                        $msg = $this->oauth->response->getParameter("error_description");
                        $msg = !empty($msg) ? $msg : "Unexpected error encountered";
                        $this->showError("authorization_failed", sprintf("<span style='color:red'>$msg</span>. Please contact <a href='%s' target='_blank'>support</a> for assistance", URL::appUrl(URL::APP_SUPPORT_PATH)), $redirect_uri);
                    }
                } else {
                    $this->showError("authorization_failed", sprintf("Requested scope(s) does not exist for the specified user. Please contact <strong>%s</strong> for assistance", EMAIL_SUPPORT), $redirect_uri);
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
        $request_token = Utils::getCookie(self::AUTH_REQ_TOKEN_PARAM);
        $token = sha1(sprintf("%s:%s:%s:%s:%s", $user_id, $client_id, $redirect_uri, $state, $scope, $response_type));

        $approve = $this->oauth->request->request("approve");
        $decline = $this->oauth->request->request("decline");

        // Authorization approved
        if ($approve && $request_token == $token) {

            $scope = !empty($scope) ? $scope : $this->oauth->server->getScopeUtil()->getDefaultScope();

            if ($this->oauth->storage->scopeExistsForUser($scope, $user_id)) {
                if ($this->oauth->server->validateAuthorizeRequest($this->oauth->request, $this->oauth->response)) {
                    return true;
                } else {
                    Utils::deleteCookie(self::AUTH_REQ_TOKEN_PARAM);
                    $this->showError($this->oauth->response->getParameter("error"), $this->oauth->response->getParameter("error_description"), $redirect_uri);
                }
            } else {
                Utils::deleteCookie(self::AUTH_REQ_TOKEN_PARAM);
                $this->showError("invalid_scope", "Scope(s) '$scope' not available for this user", $redirect_uri);
            }
        }
        // Authorization approved
        else if ($decline && $request_token == $token) {
            Utils::deleteCookie(self::AUTH_REQ_TOKEN_PARAM);
            $this->showError("authorization_declined", "Access declined by user", $redirect_uri);
        }
        // Client Id available
        else if (!empty($client = $this->oauth->storage->getClientDetails($client_id))) {
            Utils::setCookie(self::AUTH_REQ_TOKEN_PARAM, $token, 300);
            $org = $this->oauth->storage->getOrganizationDetails($client['org_id']);
            $user = $this->oauth->storage->getUser($user_id);
            $scopes = OAuthScopeService::findScope($scope);
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
            echo app()->loader->view("login", array_merge($vars, [
                'csrf_token' => Utils::generateCsrfToken(),
                'action' => ""
            ]), true);
            die;
        } catch (Exception $e) {
            app()->reportException($e); // Report
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
            echo app()->loader->view("authorize", $vars, true);
            die;
        } catch (Exception $e) {
            app()->reportException($e); // Report
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
            echo app()->loader->view("success", $userInfo, true);
            die;
        } catch (Exception $e) {
            app()->reportException($e); // Report
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
        app()->reportError(ucfirst(str_replace('_', ' ', $error)), $error_description);

        if (!empty($redirect_uri)) {
            URL::redirect(URL::parseUrl($redirect_uri, [
                "error" => $error,
                "error_description" => $error_description
            ]));
        } else {
            try {
                echo app()->loader->view("failed", [
                    "msg" => ucfirst(str_replace('_', ' ', $error)),
                    "desc" => $error_description
                ], true);
                die;
            } catch (Exception $e) {
                app()->reportException($e); // Report
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
            !empty($username = $this->oauth->request->request('username')) &&
            !empty($password = $this->oauth->request->request('password')) &&
            !empty($csrf_token = $this->oauth->request->request('csrf_token')) &&
            !empty($recaptcha_token = $this->oauth->request->request('recaptcha_token'))
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
            $content = app()->loader->view("email/auth", ["link" => $link], true);
            return app()->loader->view("email/template/simple_mail", ["content" => $content], true);
        } catch (Exception $e) {
            app()->reportException($e); // Report
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
                    'secret' => RECAPTCHA_SECRET_KEY,
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
