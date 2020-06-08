<?php
/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 1:17 AM
 */

defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;

class Server
{
    private $smtp_protocol = 'smtp';
    private $smtp_timeout = 10;
    private $smtp_charset = 'utf-8';

    /**@var OAuth2\Storage\Pdo */
    private $oauth_storage;

    /**@var OAuth2\Server */
    private $oauth_server;

    public $public_scope = 'public';
    public $system_scope = 'system';
    public $user_scope = 'user';
    public $admin_scope = 'admin';
    public $agent_scope = 'agent';
    public $partner_scope = 'partner';
    public $tester_scope = 'tester';
    public $developer_scope = 'developer';
    public $staff_scope = 'staff';

    protected $request;
    protected $response;

    /**@var array Current oraganization info of client*/
    private $org_info;

    /**@var array Current client info*/
    private $client_info;

    //TODO ADD Client settings
    
    /**
     * Server constructor.
     * @param boolean $validateClient Validate client
     * @param boolean $useJWT Use JWT Token
     * @param boolean $useOpenID Use OpenID Connect - issue id tokens
     */
    protected function __construct($validateClient = false, $useJWT = false, $useOpenID = false)
    { 

        //Create request & response objects
        $this->request = OAuth2\Request::createFromGlobals();
        $this->response = new OAuth2\Response();

        try {

            //Load custom model
            OAUTH_APP::getInstance()->loadModel("OauthPdo");

            //Create storage
            $dsn = ENVIRONMENT == ENV_DEV ? 'mysql:dbname=13243546576879_oauth;host=localhost' : (ENVIRONMENT == ENV_TEST ? "mysql:dbname=13243546576879_oauth;host=".OAUTH_APP_CONFIGS::STAGE_DB_HOST() : "mysql:dbname=13243546576879_oauth;host=".OAUTH_APP_CONFIGS::DB_HOST());
            $username = ENVIRONMENT == ENV_DEV? "root": (ENVIRONMENT == ENV_TEST? OAUTH_APP_CONFIGS::STAGE_DB_USER():OAUTH_APP_CONFIGS::DB_USER());
            $password = ENVIRONMENT == ENV_DEV? "root": (ENVIRONMENT == ENV_TEST? OAUTH_APP_CONFIGS::STAGE_DB_PASS():OAUTH_APP_CONFIGS::DB_PASS());

            //Create PDO - MYSQL DB Storage
            $this->oauth_storage = new OauthPdo(array('dsn' => $dsn, 'username' => $username, 'password' => $password));
                        
            //Create server without implicit
            $this->oauth_server = new OAuth2\Server($this->oauth_storage, array(
                'access_lifetime' => 86400, //1 day
                'refresh_token_lifetime' => 2419200, //28 days
                'auth_code_lifetime' => 3600, //1 hour
                'allow_credentials_in_request_body' => true,
                'allow_implicit' => false,
                'use_jwt_access_tokens' => $useJWT,
                'use_openid_connect' => $useOpenID,
            ));

            /*Use Memory storage for JWT Tokens since it's reduntant to store and for security*/
            if($useJWT){
                $this->oauth_server->addStorage(new OAuth2\Storage\Memory(), 'access_token');
            }

            /*User Credentials grant type*/
            $this->oauth_server->addGrantType(new OAuth2\GrantType\UserCredentials($this->oauth_storage));

            /*Client Credentials grant type*/
            $this->oauth_server->addGrantType(new OAuth2\GrantType\ClientCredentials($this->oauth_storage));

            /*Authorization Code grant type*/
            $this->oauth_server->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->oauth_storage));

            /*Refresh Token grant type - the refresh token grant request will have a "refresh_token" field*/
            $this->oauth_server->addGrantType(new OAuth2\GrantType\RefreshToken($this->oauth_storage, array(
                'always_issue_new_refresh_token' => true
            )));

            /**Check if client is valid*/
            if($validateClient){
                if (!$this->validateClient($this->system_scope)) {
                    $this->response->send();
                    die;
                }
            }

        } catch (Exception $e) {
            $this->response->setParameters(['success' => false, 'error' => 'internal_error', 'error_description' => $e->getMessage()]);
            $this->response->send();
            die();
        }
    }


    /**get oauth server
     * @return OAuth2\Server
     */
    public function get_oauth_server()
    {
        return $this->oauth_server;
    }

    /**get oauth storage
     * @return OauthPdo
     */
    protected function get_oauth_storage()
    {
        return $this->oauth_storage;
    }


    /** Validate Client credentials
     * @param string|array $scope
     * @return bool
     */
    protected function validateClient($scope = null)
    {
        if($this->get_oauth_storage()->checkClientCredentials(
            !empty($client_id = $this->request->headers("client_id")) ? $client_id : $client_id = $this->request->request("client_id"),
            !empty($client_secret = $this->request->headers("client_secret")) ? $client_secret : $client_secret = $this->request->request("client_secret")
        )){
            if (!empty($scope)){
                $scope = is_array($scope)?$this->implode($scope):$scope;
                if ($this->get_oauth_storage()->scopeExistsForClient($scope,$client_id)){
                    $this->client_info = $this->get_oauth_storage()->getClientDetails($client_id);
                    return true;
                }
                else {
                    $this->response->setParameters(array('success' => false, 'error' => 'invalid_scope', 'error_description' => "Scope($scope) does not exist for client $client_id"));
                    return false;
                }
            }
            else {
                return true;
            }
        }
        else {
            $this->response->setParameters(array('success' => false, 'error' => 'invalid_client', 'error_description' => "Invalid Client Credentials"));
            return false;
        }
    }


    /**Send Mail to an intended client
     * @param string $subject
     * @param string $message
     * @param string $to
     * @param string $from
     * @return bool
     */
    public function sendMail($subject,
                             $message,
                             $to,
                             $from = null)
    {

        try {

            $mail = new PHPMailer(true);

            //Server settings
            if ($this->smtp_protocol == "mail") {
                $mail->isMail();
            } else if ($this->smtp_protocol == "smtp") {
                $mail->isSMTP();
            } else if ($this->smtp_protocol == "sendmail") {
                $mail->isSendmail();
            }

            $mail->Username = OAUTH_APP_CONFIGS::AWS_SMTP_KEY();
            $mail->Password = OAUTH_APP_CONFIGS::AWS_SMTP_SECRET();
            $mail->Host = OAUTH_APP_CONFIGS::AWS_SMTP_HOST();
            $mail->Port = OAUTH_APP_CONFIGS::AWS_SMTP_PORT();
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Timeout = $this->smtp_timeout;
            $mail->CharSet = $this->smtp_charset;

            //Recipients
            $from = !empty($from)?$from:$this->getInfoEmail();
            $mail->setFrom($from, 'Wecari');
            $mail->addReplyTo($from, 'Wecari');
            $mail->addAddress($to);

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            return $mail->send();
        } catch (Exception $e) {
        }

        return false;
    }

    /**Get Info Email
     * @return string
     */
    public function getInfoEmail(){
        return $this->get_oauth_storage()->getConfig("email_info");
    }

    /**Get Support Email
     * @return string
     */
    public function getSupportEmail(){
        return $this->get_oauth_storage()->getConfig("email_support");
    }


    /**
     * Explode array
     * @param mixed $data
     * @param string $delimiter
     * @return array
     */
    public function explode($data, $delimiter = " ")
    {
        $res = [];
        if (!empty($data)) {
            if (is_array($data)) {
                $res = $data;
            } else {
                if (is_string($data) && !empty($arr = json_decode($data, true))) {
                    $res = $arr;
                } else if(is_string($data)) {
                    $res = explode($delimiter, $data);
                }
            }
        } 
        return $res;
    }

    /**
     * Implode data
     * @param mixed $data
     * @param string $glue
     * @return string
     */
    public function implode($data, $glue = " ")
    {
        $res = "";
        if (!empty($data)) {
            if (is_array($data)) {
                $res = implode($glue, $data);
            } else {
                if (is_string($data) && !empty($arr = json_decode($data))) {
                    $res = implode($glue, $arr);
                } else if (is_string($data)) {
                    $res = $data;
                }
            }
        }
        return trim($res);
    }
}