<?php
defined('OAUTH_BASE_PATH') or exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 1:17 AM
 */
class Server
{
    const ACCESS_TYPE_CLIENT = 'client';
    const ACCESS_TYPE_TOKEN = 'token';

    private $smtp_protocol = 'smtp';
    private $smtp_timeout = 10;
    private $smtp_charset = 'utf-8';

    /**@var OAuth2\Storage\Pdo */
    private $oauthStorage;

    /**@var OAuth2\Server */
    private $oauthServer;

    /** @var \OAuth2\Request */
    protected $request;

    /** @var \OAuth2\Response */
    protected $response;

    /** @var array Current client info*/
    private $clientInfo;

    /** @var array Current client info*/
    private $tokenInfo;

    /** @var array Current acccess type */
    private $accessType;

    /**
     * Server constructor.
     * @param boolean $validateAccess Validate access to server
     * @param boolean $useJWT Use JWT Token
     * @param boolean $useOpenID Use OpenID Connect - issue id tokens
     */
    protected function __construct($validateAccess = false, $useJWT = false, $useOpenID = false)
    {

        //Create request & response objects
        $this->request = \OAuth2\Request::createFromGlobals();
        $this->response = new \OAuth2\Response();

        //Load custom model
        App::getInstance()->loadModel("OauthPdo");

        //Create PDO - MYSQL DB Storage
        $this->oauthStorage = new OauthPdo(array('dsn' => sprintf("mysql:dbname=%s;host=%s", Configs::DB_NAME(), Configs::DB_HOST()), 'username' => Configs::DB_USER(), 'password' => Configs::DB_PASS()));

        //Create server without implicit
        $this->oauthServer = new \OAuth2\Server($this->oauthStorage, array(
            'access_lifetime' => 86400, //1 day
            'refresh_token_lifetime' => 2419200, //28 days
            'auth_code_lifetime' => 3600, //1 hour
            'allow_credentials_in_request_body' => true,
            'allow_implicit' => false,
            'use_jwt_access_tokens' => $useJWT,
            'store_encrypted_token_string' => false,
            'use_openid_connect' => $useOpenID,
            'issuer' => OAUTH_BASE_URL
        ));

        // User Credentials grant type
        $this->oauthServer->addGrantType(new OAuth2\GrantType\UserCredentials($this->oauthStorage));

        // Client Credentials grant type
        $this->oauthServer->addGrantType(new OAuth2\GrantType\ClientCredentials($this->oauthStorage));

        // Authorization Code grant type
        $this->oauthServer->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->oauthStorage));

        // Refresh Token grant type - the refresh token grant request will have a "refresh_token" field
        $this->oauthServer->addGrantType(new OAuth2\GrantType\RefreshToken($this->oauthStorage, array(
            'always_issue_new_refresh_token' => true
        )));

        // Set up Scopes
        $this->oauthServer->setScopeUtil(new Scopes(new OAuth2\Storage\Memory(array(
            'default_scope' => Scopes::DEFAULT_SCOPE,
            'supported_scopes' => array_keys(Scopes::ALL_SCOPES)
        ))));

        // Validate Access
        if ($validateAccess && !$this->validateClient() && !$this->validateAccessToken()) {
            $this->response->setStatusCode(401);
            $this->response->send();
            die;
        }
    }


    /**
     * Get oauth server
     * @return OAuth2\Server
     */
    public function getOauthServer()
    {
        return $this->oauthServer;
    }

    /**
     * Get oauth storage
     * @return OauthPdo
     */
    protected function getOauthStorage()
    {
        return $this->oauthStorage;
    }

    /** 
     * Validate Scope or Permission
     * @param string|array $scope
     * @return bool
     */
    protected function validatePermission($scope = null)
    {
        $scope = is_array($scope) ? $this->implode($scope) : $scope;
        if (($this->accessType === self::ACCESS_TYPE_TOKEN && !$this->getOauthServer()->getScopeUtil()->checkScope($scope, $this->getTokenInfo('scope')))) {
            $this->response->setStatusCode(403);
            $this->response->setParameters($this->error("Access denied. Token doesn't have required '$scope' scope(s)", 'invalid_scope'));
            $this->response->send();
            die();
        }
        else if (($this->accessType === self::ACCESS_TYPE_CLIENT && !$this->getOauthServer()->getScopeUtil()->checkScope($scope, $this->getClientInfo('scope')))) {
            $this->response->setStatusCode(403);
            $this->response->setParameters($this->error("Access denied. Client doesn't have required '$scope' scope(s)", 'invalid_scope'));
            $this->response->send();
            die();
        }
        return true;
    }

    /** 
     * Validate Access Token
     * @return bool
     */
    protected function validateAccessToken()
    {
        if ($result = $this->getOauthServer()->getAccessTokenData($this->request, $this->response)) {
            $this->tokenInfo = $result;
            $this->clientInfo = $this->getOauthStorage()->getClientDetails($this->getTokenInfo('client_id') ?? null);
            $this->accessType = self::ACCESS_TYPE_TOKEN;
            return true;
        } else if (empty($this->response->getParameters())) {
            $this->response->setParameters($this->error('Unauthorized aceess', 'invalid_token'));
            return false;
        }
    }

    /** 
     * Validate Client credentials
     * @return bool
     */
    protected function validateClient()
    {
        // Get credentials from Authorization header
        $authorization = $this->request->headers("authorization", '');
        $credentials = strpos($authorization, 'Basic ') !== false ? $this->explode(base64_decode(str_replace('Basic ', '', $authorization)), ':') : [];
        $client_id = count($credentials) == 2 ? $credentials[0] : null;
        $client_secret = count($credentials) == 2 ? $credentials[1] : null;

        // Get from header or body
        if (empty($client_id) && empty($client_secret)) {
            $client_id = $this->request->headers("client_id") ?? $this->request->request("client_id");
            $client_secret = $this->request->headers("client_secret") ?? $this->request->request("client_secret");
        }

        if ($this->getOauthStorage()->checkClientCredentials($client_id, $client_secret)) {

            $this->clientInfo = $this->getOauthStorage()->getClientDetails($client_id);
            if (!empty($this->clientInfo)) {
                $this->accessType = self::ACCESS_TYPE_CLIENT;
                return true;
            } else {
                $this->response->setParameters($this->error('Failed to get client details', 'invalid_client'));
                return false;
            }
        } else {
            $this->response->setParameters($this->error('Unauthorized aceess', 'invalid_client'));
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
    public function sendMail(
        $subject,
        $message,
        $to,
        $from = null
    ) {

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

            $mail->Username = Configs::SMTP_KEY();
            $mail->Password = Configs::SMTP_SECRET();
            $mail->Host = Configs::SMTP_HOST();
            $mail->Port = Configs::SMTP_PORT();
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Timeout = $this->smtp_timeout;
            $mail->CharSet = $this->smtp_charset;

            //Recipients
            $from = !empty($from) ? $from : Configs::EMAIL_INFO();
            $mail->setFrom($from, Configs::COMPANY_NAME() ?? Configs::APP_NAME());
            $mail->addReplyTo($from, Configs::COMPANY_NAME() ?? Configs::APP_NAME());
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
                } else if (is_string($data)) {
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

    /**
     * Get success response
     */
    public function success($data)
    {
        if (is_string($data)) {
            return ['success' => true, 'message' => $data];
        } else {
            return ['success' => true, 'data' => $data];
        }
    }

    /**
     * Get success response
     */
    public function error($message, $type = 'unexpected_error')
    {
        return ['success' => false, 'error' => $type, 'error_description' => $message];
    }

    /**
     * Get the value of clientInfo
     */
    public function getClientInfo($param = null)
    {
        return $this->clientInfo ? ($param ? $this->clientInfo[$param] ?? null : $this->clientInfo) : null;
    }

    /**
     * Get the value of tokenInfo
     */
    public function getTokenInfo($param = null)
    {
        return $this->tokenInfo ? ($param ? $this->tokenInfo[$param] ?? null : $this->tokenInfo) : null;
    }
}
