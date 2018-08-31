<?php
/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 1:17 AM
 */

defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

class Server
{

//    private $dsn = 'mysql:dbname=ebusghco_13243546576879_oauth;host=localhost';  //TODO UNCOMMENT FOR PRODUCTION
//    private $username = 'ebusghco_eb_pub';  //TODO UNCOMMENT FOR PRODUCTION
//    private $password = 'ebusgh@2018';  //TODO UNCOMMENT FOR PRODUCTION

    private $dsn = 'mysql:dbname=13243546576879_oauth;host=localhost';
    private $username = 'root';
    private $password = '';

    private $oauth_storage;
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
                'auth_code_lifetime' => 300, //5 mins
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
            exit(json_encode(['status'=>'error','message'=>$e->getMessage()]));
        }
    }


    /**get oauth server
     * @return OAuth2\Server
     */
    protected function get_oauth_server()
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

}