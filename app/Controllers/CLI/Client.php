<?php

namespace App\Controllers\CLI;

use phpseclib\Crypt\RSA;
use App\Helpers\Utils;
use App\Controllers\OAuthBaseController;
use App\Services\OAuthScopeService;
use Exception;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 12:20 PM
 */

class Client extends OAuthBaseController
{
    public function __construct()
    {
        parent::__construct(true);
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
            throw new Exception("Failed to create client");
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
                throw new Exception("Failed to update client keys");
            }
        } else {
            throw new Exception("Invalid Client");
        }
    }
}
