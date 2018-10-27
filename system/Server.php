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

    const EBUSGH_INFO_EMAIL = "info@ebusgh.com";

    private $dsn = ENVIRONMENT == ENV_DEV ?
        'mysql:dbname=13243546576879_oauth;host=localhost':
        'mysql:dbname=ebusghco_13243546576879_oauth;host=localhost';
    private $username = ENVIRONMENT == ENV_DEV ?'root':'ebusghco_eb_pub';
    private $password = ENVIRONMENT == ENV_DEV ?'':'ebusgh@2018';

    protected $protocol = 'mail';
    protected $smtp_host = "stormerhost.com";
    protected $smtp_port = 465; //SSL/TLS
    protected $smtp_user = "info@ebusgh.com";
    protected $smtp_pass = "ebugh@2018";
    protected $smtp_timeout = 10;
    protected $charset = 'utf-8';


    /**@var OAuth2\Storage\Pdo*/
    private $oauth_storage;

    /**@var OAuth2\Server*/
    private $oauth_server;

    /**
     * Server constructor.
     */
    protected function __construct()
    {
        try {
            $this->oauth_storage = new OAuth2\Storage\Pdo(array('dsn' => $this->dsn, 'username' => $this->username, 'password' => $this->password));

            //create server without implicit
            $this->oauth_server = new OAuth2\Server($this->oauth_storage,array(
                'access_lifetime'=> 86400, //1 day
                'refresh_token_lifetime' => 2419200, //28 days
                'auth_code_lifetime' => 3600, //1 hour
                'allow_credentials_in_request_body' => true,
                'allow_implicit' => false,
            ));

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

        } catch (Exception $e) {
            header("Content-Type: application/json",true);
            exit(json_encode(['status'=>'error','message'=>$e->getMessage()]));
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
     * @return  OAuth2\Storage\Pdo
     */
    protected function get_oauth_storage()
    {
        return $this->oauth_storage;
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
                             $from = Server::EBUSGH_INFO_EMAIL)
    {

        try {

            loadLibrary("PHPMailer/PHPMailer");
            loadLibrary("PHPMailer/SMTP");
            loadLibrary("PHPMailer/Exception");

            $mail = new PHPMailer(true);

            //Server settings
            if ($this->protocol == "mail") {
                $mail->isMail();
            } else if ($this->protocol == "smtp") {
                $mail->isSMTP();
            } else if ($this->protocol == "sendmail") {
                $mail->isSendmail();
            }
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_user;
            $mail->Password = $this->smtp_pass;
            $mail->SMTPSecure = 'tls';
            $mail->Port = $this->smtp_port;
            $mail->Timeout = $this->smtp_timeout;
            $mail->CharSet = $this->charset;

            //Recipients
            $mail->setFrom($from, 'EbusGh');
            $mail->addReplyTo($from, 'Ebusgh');
            $mail->addAddress($to);

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            return $mail->send();
        }
        catch (Exception $e) {}

        return false;
    }

}