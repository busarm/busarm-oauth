<?php

namespace Application\Controllers\CLI;

use Application\Controllers\OAuthBaseController;
use phpseclib\Crypt\RSA;
use  Application\Services\OAuthScopeService;
use System\Utils;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 12:20 PM
 */

class Task extends OAuthBaseController
{
    public function __construct()
    {
        parent::__construct(false, true, true);
    }

    /**
     * Create Admin Client
     *
     * @param string $org_name
     * @return void
     */
    public function create_org($org_name)
    {
        $result = $this->storage->setOrganizationDetails($org_name);
        if ($result) {
            log_info("Successfully Added Organization");
            log_info("Organizatoin ID = $result");
        }
        die;
    }

    /**
     * Create Admin Client
     *
     * @param string $org_id
     * @param string $client_name
     * @param string $redirect_uri
     * @param string $scopes
     * @param string $grant_types
     * @return void
     */
    public function create_client($org_id, $client_name, $redirect_uri = null, $scopes = null, $grant_types = null)
    {
        $client_id = str_replace(' ', '_', strtolower($client_name)) . '_' . crc32(uniqid($client_name));
        $client_secret = md5(uniqid($client_id));
        $grant_types = !empty($grant_types) ? $grant_types : Utils::implode(array_keys($this->server->getGrantTypes()));
        $scopes = !empty($scopes) ? $scopes : OAuthScopeService::$defaultScope;

        //Insert Client
        $result = $this->storage->setCustomClientDetails($org_id, $client_id, $client_name, $client_secret, $redirect_uri, $grant_types, $scopes);
        if ($result) {

            //Insert jwt public keys for client
            $algo = 'sha256';
            $rsa = new RSA();
            $rsa->setHash($algo);
            $keys = $rsa->createKey(2048);
            if (!empty($keys) && $this->storage->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")) {
                log_info("Successfully Created Client");
                log_info("Client ID = $client_id");
                log_info("Client Secret = $client_secret");
                log_info("Client Grant_types = $grant_types");
                log_info("Client Scopes = $scopes");
                log_info("Client Redirect Url = $redirect_uri");
                log_info("Client Public Key = " . $keys['publickey']);
                log_info("Client Public Key ALGO = $algo");
            } else {
                log_info("Successfully Created Client");
                log_info("Client ID = $client_id");
                log_info("Client Secret = $client_secret");
                log_info("Client Grant_types = $grant_types");
                log_info("Client Scopes = $scopes");
                log_info("Client Redirect Url = $redirect_uri");
            }
            die;
        } else {
            exit("Failed to create client");
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
        if (!empty($client_id)) {
            $algo = 'sha256';
            $rsa = new RSA();
            $rsa->setHash($algo);
            $keys = $rsa->createKey(2048);
            if (!empty($keys) && $this->storage->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")) {
                log_info("Successfully Updated Client Keys");
                log_info("Client Public Key = " . $keys['publickey']);
                log_info("Client Public Key ALGO = $algo");
                die;
            } else {
                exit("Failed to update client keys");
            }
        } else {
            exit("Invalid Client");
        }
    }

    /**
     * Create Admin User
     *
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string $dial_code
     * @param string $phone
     * @param string $scopes
     * @return void
     */
    public function create_user($name, $email, $password = null, $dial_code = null, $phone = null, $scopes = null)
    {
        //Create user id
        $prefix = !empty($email) ? $email : (!empty($phone) ? $phone : "");
        $user_id = sha1(uniqid($prefix));
        $user_password = !empty($password) ? $password : bin2hex(random_bytes(5));
        $scopes = !empty($scopes) ? $scopes : Utils::implode([
            OAuthScopeService::$defaultScope,
            OAuthScopeService::SCOPE_OPENID,
            OAuthScopeService::SCOPE_CLAIM_NAME,
            OAuthScopeService::SCOPE_CLAIM_EMAIL,
            OAuthScopeService::SCOPE_CLAIM_PHONE,
        ]);

        //Insert User
        $result = $this->storage->setCustomUser($user_id, $user_password, $email, $name, $phone, $dial_code, $scopes);
        if ($result) {
            log_info("Successfully Created User");
            log_info("User ID = $result");
            log_info("User Name = $name");
            log_info("User Email = $email");
            if (empty($password)) {
                log_info("User Password = $user_password");
            }
            log_info("User Scopes = $scopes");
            die;
        } else {
            exit("Failed to create user");
        }
    }
}
