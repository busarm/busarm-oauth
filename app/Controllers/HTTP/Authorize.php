<?php

namespace App\Controllers\HTTP;

use Exception;
use GuzzleHttp\RequestOptions;
use App\Controllers\OAuthBaseController;
use App\Services\OAuthScopeService;
use App\Services\MailService;
use App\Helpers\URL;
use App\Helpers\Utils;
use App\Views\AuthorizePage;
use App\Views\FailedPage;
use App\Views\LoginPage;
use App\Views\SuccessPage;
use App\Dto\Request\AuthorizeLoginDto;
use App\Dto\Page\AuthorizePageDto;
use App\Dto\Page\FailedPageDto;
use App\Dto\Page\LoginPageDto;
use App\Dto\Page\SuccessPageDto;

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
        parent::__construct();
    }

    /**
     * Authorize token request
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
            return $this->processEmailRequest($email, $redirect_uri, $state, $scope);
        }

        // If Logged in
        else if ($user_id = $this->auth->getLoginUser()) {
            $approve = boolval($this->oauth->request->request("approve"));
            $decline = boolval($this->oauth->request->request("decline"));
            return $this->processAuthRequest($user_id, $client_id, $redirect_uri, $state, $scope, $response_type, $approve, $decline);
        }

        // Redirect to login
        else {
            return response()->redirect('authorize/login?redirect_url=' . urlencode(app()->request->currentUrl()));
        }
    }

    /**
     * Show Login
     */
    public function login()
    {
        $redirect_url = $this->oauth->request->request("redirect_url", $this->oauth->request->query("redirect_url"));
        if (!empty($redirect_url)) {
            $this->auth->clearLoginSession();
            $dto = new LoginPageDto;
            $dto->csrf_token = Utils::generateCsrfToken();
            $dto->redirect_url = $redirect_url;
            return new LoginPage($dto);
        } else {
            return $this->showError("login_failed", "A Redirect Url is required");
        }
    }

    /**
     * Process Login Request
     */
    public function processLogin(AuthorizeLoginDto $dto)
    {
        $page = new LoginPageDto;

        if ($this->validateRecaptcha($dto->recaptcha_token) && Utils::validateCsrf($dto->csrf_token)) {
            $max_count = 5;
            $timeout = 60;
            $count = Utils::getCookie('request_count') ?? 0;
            // Max request count
            if ($count < $max_count) {
                $count += 1;
                Utils::setCookie('request_count', $count, $timeout);
                if ($user = ($this->oauth->storage->checkUserCredentials($dto->username, $dto->password))) {
                    $this->auth->startLoginSession($user['user_id'], 86400);
                    return response()->redirect($dto->redirect_url);
                } else {
                    $remaining_count = $max_count - $count;
                    $page->msg = "Invalid Username or Password. $remaining_count attempt(s) left";
                }
            } else {
                $min = intval($timeout / 60);
                $page->msg = "Maximum attempt reached. Please try again in $min minute(s)";
            }
        } else {
            $page->msg = "Session validation failed. Please try again";
        }

        $this->auth->clearLoginSession();
        $page->csrf_token = Utils::generateCsrfToken(null, true);
        $page->redirect_url = $dto->redirect_url;
        return new LoginPage($page);
    }

    /**
     * Logout 
     */
    public function logout()
    {
        $redirect_url = $this->oauth->request->request("redirect_url", $this->oauth->request->query("redirect_url"));
        if (!empty($redirect_url)) {
            $this->auth->clearLoginSession();
            Utils::deleteCookie(self::AUTH_REQ_TOKEN_PARAM);
            return response()->redirect('authorize/login?redirect_url=' . urlencode($redirect_url));
        } else {
            return $this->showError("login_failed", "A Redirect Url is required");
        }
    }

    /**
     * Process Email Authorization Request
     *
     * @param string $email
     * @param string $redirect_uri
     * @param string $state
     * @param string $scope
     * @return SuccessPage|FailedPage
     */
    private function processEmailRequest($email, $redirect_uri, $state, $scope)
    {
        if ($user = $this->oauth->storage->getUser($email)) {

            $timeout = 600;
            $token = sha1(sprintf("%s:%s:%s:%s", $email, $redirect_uri, $state, $scope));
            if (Utils::getCookie(self::EMAIL_REQ_TOKEN_PARAM) !== $token) {

                // Validate requested scope - ensure user has them
                if (empty($scope) || $this->oauth->storage->scopeExistsForUser($scope, $user['user_id'])) {

                    // Validate authorization request
                    if ($this->oauth->server->validateAuthorizeRequest($this->oauth->request, $this->oauth->response)) {

                        // Re-initialize response
                        $this->oauth->response = new \App\Helpers\Response;
                        // Process authorization request
                        $this->oauth->server->handleAuthorizeRequest($this->oauth->request, $this->oauth->response, true, $user['user_id']);

                        // Prepare email message
                        $message = $this->getEmailAuthView($this->oauth->response->getHttpHeader("Location"));

                        // Send email
                        if (MailService::getInstance()->send("Email Authorization", $message, $email)) {
                            // Save to cookie to prevent duplicate 
                            Utils::setCookie(self::EMAIL_REQ_TOKEN_PARAM, $token, $timeout);
                            return $this->showEmailSuccess($user['email']);
                        } else {
                            return $this->showError("authorization_failed", sprintf("Failed to send mail. Please contact <a href='%s' target='_blank'>support</a> for assistance", URL::appUrl(URL::APP_SUPPORT_PATH)), $redirect_uri);
                        }
                    } else {
                        $msg = $this->oauth->response->getParameter("error_description") ?? "Unexpected error encountered";
                        return $this->showError("authorization_failed", sprintf("<span style='color:red'>$msg</span>. Please contact <a href='%s' target='_blank'>support</a> for assistance", URL::appUrl(URL::APP_SUPPORT_PATH)), $redirect_uri);
                    }
                } else {
                    return $this->showError("authorization_failed", sprintf("Requested scope(s) does not exist for the specified user. Please contact <strong>%s</strong> for assistance", EMAIL_SUPPORT), $redirect_uri);
                }
            } else {
                $min = intval($timeout / 60);
                return $this->showError("duplicate_authorization", "Authorization link already sent to <strong>$email</strong>. Try again in $min minutes or clear browser cookies and retry");
            }
        }
        return $this->showError("authorization_failed", $this->oauth->response->getParameter("error_description") ?? "Invalid request", $redirect_uri);
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
     * @param boolean $approve
     * @param boolean $decline
     * @return AuthorizePage|FailedPage|System\Interfaces\ResponseInterface
     */
    private function processAuthRequest($user_id, $client_id, $redirect_uri, $state, $scope, $response_type, $approve, $decline)
    {
        $request_token = Utils::getCookie(self::AUTH_REQ_TOKEN_PARAM);
        $token = sha1(sprintf("%s:%s:%s:%s:%s", $user_id, $client_id, $redirect_uri, $state, $scope, $response_type));

        // Authorization approved
        if ($approve && $request_token == $token) {

            $scope = !empty($scope) ? $scope : $this->oauth->server->getScopeUtil()->getDefaultScope();

            // Validate requested scope - ensure user has them
            if ($this->oauth->storage->scopeExistsForUser($scope, $user_id)) {

                // Validate authorization request
                if ($this->oauth->server->validateAuthorizeRequest($this->oauth->request, $this->oauth->response)) {

                    // Re-initialize response
                    $this->oauth->response = new \App\Helpers\Response;
                    // Process authorization request
                    $this->oauth->server->handleAuthorizeRequest($this->oauth->request, $this->oauth->response, true, $user_id);
                    return response()
                        ->setStatusCode($this->oauth->response->getStatusCode(), $this->oauth->response->getStatusCode())
                        ->setParameters($this->oauth->response->getParameters())
                        ->addHttpHeaders($this->oauth->response->getHttpHeaders());
                } else {
                    Utils::deleteCookie(self::AUTH_REQ_TOKEN_PARAM);
                    return $this->showError($this->oauth->response->getParameter("error"), $this->oauth->response->getParameter("error_description"), $redirect_uri);
                }
            } else {
                Utils::deleteCookie(self::AUTH_REQ_TOKEN_PARAM);
                return $this->showError("invalid_scope", "Scope(s) '$scope' not available for this user", $redirect_uri);
            }
        }

        // Authorization declined
        else if ($decline && $request_token == $token) {
            Utils::deleteCookie(self::AUTH_REQ_TOKEN_PARAM);
            return $this->showError("authorization_declined", "Access declined by user", $redirect_uri);
        }

        // Client Id available
        else if (!empty($client = $this->oauth->storage->getClientDetails($client_id))) {
            Utils::setCookie(self::AUTH_REQ_TOKEN_PARAM, $token, 300);
            $org = $this->oauth->storage->getOrganizationDetails($client['org_id']);
            $user = $this->oauth->storage->getUser($user_id);
            $scopes = OAuthScopeService::findScope($scope);

            $dto = new AuthorizePageDto;
            $dto->client_name = $client['client_name'] ?? null;
            $dto->org_name = $org ? $org['org_name'] : null;
            $dto->user_name = $user ? $user['name'] : null;
            $dto->user_email = $user ? $user['email'] : null;
            $dto->scopes = $scopes ? array_values($scopes) : [];
            $dto->action = app()->request->currentUrl();
            return new AuthorizePage($dto);
        }
        return $this->showError("authorization_failed", $this->oauth->response->getParameter("error_description") ?? "Invalid request", $redirect_uri);
    }

    /**
     * Show Auth Email Success page
     * @param string $email 
     * @param string $msg 
     * @return SuccessPage
     */
    private function showEmailSuccess($email, $msg = null)
    {
        $dto = new SuccessPageDto;
        $dto->email = $email;
        $dto->msg = $msg;
        return new SuccessPage($dto);
    }

    /**
     * Show Error
     * @param string $error
     * @param string $error_description
     * @param string $redirect_uri
     * @return FailedPage|\System\Interfaces\ResponseInterface
     */
    private function showError($error, $error_description = '', $redirect_uri = '')
    {
        // Report
        app()->reporter->reportError(ucfirst(str_replace('_', ' ', $error)), $error_description);

        if (!empty($redirect_uri)) {
            return response()->redirect(URL::parseUrl($redirect_uri, [
                "error" => $error,
                "error_description" => $error_description
            ]));
        } else {
            $dto = new FailedPageDto;
            $dto->msg = ucfirst(str_replace('_', ' ', $error));
            $dto->desc = $error_description;
            return new FailedPage($dto);
        }
    }

    /**
     * Get Email Authorization view to send as message to user
     * @param $link
     * @return string
     */
    private function getEmailAuthView($link)
    {
        try {
            $content = app()->loader->view("email/auth", ["link" => $link], true);
            return app()->loader->view("email/template/simple_mail", ["content" => $content], true);
        } catch (Exception $e) {
            app()->reporter->reportException($e);
            return null;
        }
    }

    /** 
     * Validate Recaptcha
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
                    'remoteip' => app()->request->ip(),
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
