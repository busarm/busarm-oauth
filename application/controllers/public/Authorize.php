<?php
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
        $email = $this->request->query("email");
        $scope = $this->request->query("scope");
        $state = $this->request->query("state");
        $redirect_uri = $this->request->query("redirect_uri");

        if ($userInfo = $this->processLoginRequest()) { //Process Login
            $user_id = $userInfo['user_id'];
            $scope = !empty($scope) ? $scope : $this->get_oauth_storage()->getDefaultScope();
            $is_authorized = $this->get_oauth_storage()->scopeExistsForUser($scope, $user_id) &&
                $this->get_oauth_server()->validateAuthorizeRequest($this->request, $this->response);

            if ($is_authorized) {
                $this->get_oauth_server()->handleAuthorizeRequest($this->request, $this->response, $is_authorized, $user_id);
                $this->response->send();
                die();
            } else {
                $this->showAuthorizationForm(["msg" => $this->response->getParameter("error_description")]);
                die();
            }
        } else {
            if (!empty($email)) { //Validate and Send authorization url
                if ($userInfo = $this->get_oauth_storage()->getUser(null, $email)) {

                    $hash = md5(sprintf("%s/%s/%s", $email, $redirect_uri, $state));
                    if (@$_SESSION['request_hash'] != $hash) {

                        $user_id = $userInfo['user_id'];
                        $is_authorized = $this->get_oauth_storage()->scopeExistsForUser($scope, $user_id) &&
                            $this->get_oauth_server()->validateAuthorizeRequest($this->request, $this->response);

                        if ($is_authorized) {
                            $this->response = new OAuth2\Response(); //reinitialize response
                            $this->get_oauth_server()->handleAuthorizeRequest($this->request, $this->response, $is_authorized, $user_id);
                            $link = $this->response->getHttpHeader("Location");
                            $message = $this->getEmailAuthView($link);
                            if ($this->sendMail("Email Authorization", $message, $email)) {
                                try {
                                    $_SESSION['request_hash'] = $hash; //Save to session to prevent duplicate
                                    $this->showEmailSuccess($userInfo);
                                    die();
                                }
                                catch (Exception $e) {
                                    $this->showEmailFailed([
                                        "msg"=>"Authorization failed",
                                        "sub_msg"=>"contact <strong>support@ebusgh.com</strong> for assistance",
                                    ]);
                                    die();
                                }
                            }
                            else {
                                $this->showEmailFailed([
                                    "msg"=>"Authorization failed",
                                    "sub_msg"=>"contact <strong>support@ebusgh.com</strong> for assistance",
                                ]);
                                die();
                            }
                        }
                        else {
                            $this->showEmailFailed([
                                "msg"=>"Authorization failed",
                                "sub_msg"=>"contact <strong>support@ebusgh.com</strong> for assistance",
                            ]);
                            die();
                        }
                    }
                    else {
                        $this->showEmailFailed([
                            "msg"=>"Authorization link already sent to <strong>$email</strong>",
                            "sub_msg"=>"Please check your email or clear browser cookie and retry",
                        ]);
                        die();
                    }
                }
                else {
                    $this->showAuthorizationForm();
                    die();
                }
            }
            else if (!empty($user_id)) { //validate and Show authorization form for user
                if ($userInfo = $this->get_oauth_storage()->getUser($user_id)) {
                    $this->showAuthorizationForm($userInfo);
                    die();
                }
                else {
                    $this->showAuthorizationForm(["msg" => $this->response->getParameter("error_description")]);
                    die();
                }
            }
            else {
                $this->showAuthorizationForm(["msg" => $this->response->getParameter("error_description")]);
                die();
            }
        }
    }

    /**Process Login Request
     * @return boolean|array Array of user info or false if failed
     */
    private function processLoginRequest()
    {
        if ($data = $this->loginRequestAvailable()) {
            if ($this->csrf_validation(@$data['csrf_token'])) {
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

    /**Show from for use to enter their
     * authorization credentials
     * @param array $vars
     */
    private function showAuthorizationForm($vars = array())
    {
        try {
            echo loadView("login", $vars, true);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**Show Auth Email Success page
     * @param array $vars
     */
    private function showEmailSuccess($vars = array())
    {
        try {
            echo loadView("success", $vars, true);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    /**Show Auth Email Failure page
     * @param array $vars
     */
    private function showEmailFailed($vars = array())
    {
        try {
            echo loadView("failed", $vars, true);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**Get Email Authorization view to send as message to user
     * @param $link
     * @return string
     */
    private function getEmailAuthView($link)
    {
        $content = "<table border='0' >
                      <tr>
                        <td align='center'><h2>Click the url below to access your account</h2></td>
                      </tr>
                      <tr>
                        <td><a href='$link'>$link</a></td>
                      </tr>
                      <tr> 
                        <br/>
                        <br/>
                      </tr>
                      <tr>
                        <td align='center'><span style='font-size:14px !important; color: #0b2e13;'>This link can only be used <strong>ONCE</strong> and will expire in <strong>AN HOUR</strong></span></td>
                      </tr>
                      <tr>
                        <td align='center'><strong style='font-size:12px !important;; color: #9d223c;'>(Please ignore this message if it wasn't triggered or requested by you)</strong></td>
                      </tr>
                    </table>";
        try {
            return loadView("simple_mail", ["content" => $content], true);
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

    /**CSRF Validation
     * @param string $csrf_token
     * @return array|boolean
     */
    private function csrf_validation($csrf_token)
    {
        if (isset($_SESSION["csrf_token"])) {
            return $csrf_token == encode_csrf_token($_SESSION['csrf_token']);
        }
        return false;
    }
}