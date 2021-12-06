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
     * Print to console
     *
     * @param mixed $msg
     * @return void
     */
    private function print($msg){
        print_r($msg);
        print_r(PHP_EOL);
    }
    
    /**
     * Create Admin Client
     *
     * @param string $org_name
     * @return void
     */
    public function create_org($org_name)
    {
        $result = $this->get_oauth_storage()->setOrganizationDetails($org_name);
        if($result){
            $this->print("Successfully Added Organization");
            $this->print("Organizatoin ID = $result");
        }
        die;
    }

    /**
     * Create Admin Client
     *
     * @param string $org_id
     * @param string $client_id
     * @param string $client_name
     * @param string $redirect_uri
     * @return void
     */
    public function create_client($org_id, $client_id, $client_name, $redirect_uri = null)
    {
        $client_secret = md5(uniqid($client_id));
        $grant_types = "password client_credentials authorization_code refresh_code";
        $scopes = "*";

        //Insert Client
        $result = $this->get_oauth_storage()->setClientDetailsCustom($org_id, $client_id, $client_name, $client_secret, $redirect_uri, $grant_types, $scopes);

        //Insert jwt public keys for client
        if($result){

            $algo = 'sha256';
            $rsa = new phpseclib\Crypt\RSA();
            $rsa->setHash($algo);
            $keys = $rsa->createKey(2048);
            if(!empty($keys) && $this->get_oauth_storage()->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")){
                $this->print("Successfully Created Client");
                $this->print("Client ID = $client_id");
                $this->print("Client Secret = $client_secret");
                $this->print("Client Grant_types = $grant_types");
                $this->print("Client Scopes = $scopes");
                $this->print("Client Redirect Url = $redirect_uri");
                $this->print("Client Public Key = ".$keys['publickey']);
                $this->print("Client Public Key ALGO = $algo"); 
            }
            else {
                $this->print("Successfully Created Client");
                $this->print("Client ID = $client_id");
                $this->print("Client Secret = $client_secret");
                $this->print("Client Grant_types = $grant_types");
                $this->print("Client Scopes = $scopes");
                $this->print("Client Redirect Url = $redirect_uri");
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
     * @param string $client_id
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
                $this->print("Successfully Updated Client Keys");
                $this->print("Client Public Key = ".$keys['publickey']);
                $this->print("Client Public Key ALGO = $algo"); 
                die;
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
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string $scopes
     * @return void
     */
    public function create_user($name, $email, $password = null, $scopes = "*")
    {
        //Create user id
        $prefix = !empty($email)?$email:(!empty($phone)?$phone:"");
        $user_id = sha1(uniqid($prefix));
        $user_password = $password ?? bin2hex(random_bytes(5));
        $scopes = "*";

        //Insert User
        $result = $this->get_oauth_storage()->setUserCustom($user_id, $user_password, $email, $name, null, null, $scopes);
        if($result){
            $this->print("Successfully Created User");
            $this->print("User ID = $user_id");
            $this->print("User Name = $name");
            $this->print("User Email = $email");
            if(!$password){
                $this->print("User Password = $user_password");
            }
            $this->print("User Scopes = $scopes");
            die;
        }
        else {
            exit ("Failed to create user");
        }
    }
}