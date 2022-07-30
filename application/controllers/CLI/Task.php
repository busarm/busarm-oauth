<?php

namespace Application\Controllers\CLI;

use phpseclib\Crypt\RSA;
use Application\Helpers\Utils;
use Application\Controllers\OAuthBaseController;
use Application\Services\OAuthScopeService;

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
        parent::__construct(true);
    }

    /**
     * Create Admin Client
     *
     * @param string $org_name
     * @return void
     */
    public function create_org($org_name)
    {
        $result = $this->oauth->storage->setOrganizationDetails($org_name);
        if ($result) {
            log_debug("Successfully Added Organization");
            log_debug("Organizatoin ID = $result");
        } else {
            exit("Failed to create org");
        }
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
        $grant_types = !empty($grant_types) ? $grant_types : Utils::implode(array_keys($this->oauth->server->getGrantTypes()));
        $scopes = !empty($scopes) ? $scopes : OAuthScopeService::$defaultScope;

        //Insert Client
        $result = $this->oauth->storage->setCustomClientDetails($org_id, $client_id, $client_name, $client_secret, $redirect_uri, $grant_types, $scopes);
        if ($result) {

            //Insert jwt public keys for client
            $algo = 'sha256';
            $rsa = new RSA();
            $rsa->setHash($algo);
            $keys = $rsa->createKey(2048);
            if (!empty($keys) && $this->oauth->storage->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")) {
                log_debug("Successfully Created Client");
                log_debug("Client ID = $client_id");
                log_debug("Client Secret = $client_secret");
                log_debug("Client Grant_types = $grant_types");
                log_debug("Client Scopes = $scopes");
                log_debug("Client Redirect Url = $redirect_uri");
                log_debug("Client Public Key = " . $keys['publickey']);
                log_debug("Client Public Key ALGO = $algo");
            } else {
                log_debug("Successfully Created Client");
                log_debug("Client ID = $client_id");
                log_debug("Client Secret = $client_secret");
                log_debug("Client Grant_types = $grant_types");
                log_debug("Client Scopes = $scopes");
                log_debug("Client Redirect Url = $redirect_uri");
            }
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
            if (!empty($keys) && $this->oauth->storage->setClientPublickKey($client_id, $keys['privatekey'], $keys['publickey'], "RS256")) {
                log_debug("Successfully Updated Client Keys");
                log_debug("Client Public Key = " . $keys['publickey']);
                log_debug("Client Public Key ALGO = $algo");
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
            SCOPE_OPENID,
            SCOPE_CLAIM_NAME,
            SCOPE_CLAIM_EMAIL,
            SCOPE_CLAIM_PHONE,
        ]);

        //Insert User
        $result = $this->oauth->storage->setCustomUser($user_id, $user_password, $email, $name, $phone, $dial_code, $scopes);
        if ($result) {
            log_debug("Successfully Created User");
            log_debug("User ID = $result");
            log_debug("User Name = $name");
            log_debug("User Email = $email");
            if (empty($password)) {
                log_debug("User Password = $user_password");
            }
            log_debug("User Scopes = $scopes");
        } else {
            exit("Failed to create user");
        }
    }
}
