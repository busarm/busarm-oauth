<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 12:20 PM
 */

class Console extends Server
{
    public function __construct(){
        if(is_cli()){
            parent::__construct(false, true, true);
        }
        else {
            exit('Unauthorized Request');
        }
    }
    
    /**
     * Create Admin Client
     *
     * @return void
     */
    public function create_org($org_name)
    {
        $result = $this->get_oauth_storage()->setOrganizationDetails($org_name);
        if($result){
            echo "Successfully Added Organization".PHP_EOL;
            echo "Organizatoin ID = $result".PHP_EOL;
        }
        die;
    }

    /**
     * Create Admin Client
     *
     * @return void
     */
    public function create_client($org_id, $client_id, $redirect_uri)
    {
        $client_secret = md5(uniqid($client_id));
        $grant_types = "password client_credentials authorization_code refresh_code";
        $scopes = "$this->openid_scope $this->system_scope $this->admin_scope $this->staff_scope $this->developer_scope $this->tester_scope $this->user_scope $this->agent_scope $this->partner_scope $this->public_scope";

        //Insert Client
        $result = $this->get_oauth_storage()->setClientDetailsCustom($org_id, $client_id, $client_secret, $redirect_uri, $grant_types, $scopes);

        //Insert jwt public keys for client
        if($result){

            $algo = 'sha256';
            $rsa = new phpseclib\Crypt\RSA();
            $rsa->setHash($algo);
            $keys = $rsa->createKey(2048);
            if(!empty($keys) && $this->get_oauth_storage()->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")){
                echo "Successfully Created Client".PHP_EOL;
                echo "Client ID = $client_id".PHP_EOL;
                echo "Client Secret = $client_secret".PHP_EOL;
                echo "Client Grant_types = $grant_types".PHP_EOL;
                echo "Client Scopes = $scopes".PHP_EOL;
                echo "Client Public Key = ".$keys['publickey'].PHP_EOL;
                echo "Client Public Key ALGO = $algo".PHP_EOL; 
            }
            else {
                echo "Successfully Created Client".PHP_EOL;
                echo "Client ID = $client_id".PHP_EOL;
                echo "Client Secret = $client_secret".PHP_EOL;
                echo "Client Grant_types = $grant_types".PHP_EOL;
                echo "Client Scopes = $scopes".PHP_EOL;
            }
            die;
        }
        else {
            exit ("Failed to create client");
        }
    }

    /**
     * Update Client Keys
     *
     * @return void
     */
    public function update_client_key($client_id)
    {
        if(!empty($client_id)){
            $algo = 'sha256';
            $rsa = new phpseclib\Crypt\RSA();
            $rsa->setHash($algo);
            $keys = $rsa->createKey(2048);
            if(!empty($keys) && $this->get_oauth_storage()->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")){
                echo "Successfully Updated Client Keys".PHP_EOL;
                echo "Client Public Key = ".$keys['publickey'].PHP_EOL;
                echo "Client Public Key ALGO = $algo".PHP_EOL; 
            }
            else {
                exit ("Failed to update client keys");
            }
        }
        else {
            exit ("Invalid Client");
        }
    }
    
    /**
     * Create Admin User
     *
     * @return void
     */
    public function create_user($name, $email, $pass = null)
    {
        //Create user id
        $prefix = !empty($email)?$email:(!empty($phone)?$phone:"");
        $user_id = sha1(uniqid($prefix));
        $password = $pass ?? bin2hex(random_bytes(5));
        $scopes = "$this->openid_scope $this->admin_scope $this->staff_scope $this->developer_scope $this->tester_scope $this->user_scope";

        //Insert User
        $result = $this->get_oauth_storage()->setUserCustom($user_id, $password, $email, $name, null, null, $scopes);
        if($result){
            echo "Successfully Created User".PHP_EOL;
            echo "User ID = $user_id".PHP_EOL;
            echo "User Name = $name".PHP_EOL;
            echo "User Email = $email".PHP_EOL;
            if(!$pass){
                echo "User Password = $password".PHP_EOL;
            }
            echo "User Scopes = $scopes".PHP_EOL;
        }
        else {
            exit ("Failed to create user");
        }
    }
}