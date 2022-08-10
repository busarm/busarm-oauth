<?php

namespace App\Controllers\CLI;

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

class User extends OAuthBaseController
{
    public function __construct()
    {
        parent::__construct(true);
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
            throw new Exception("Failed to create user");
        }
    }
}
