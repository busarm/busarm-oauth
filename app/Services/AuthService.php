<?php

namespace App\Services;

use Busarm\PhpMini\Interfaces\SingletonInterface;
use Busarm\PhpMini\Traits\Singleton;
use App\Helpers\Utils;

class AuthService implements SingletonInterface
{
    use Singleton;
    
    const LOGIN_USER_KEY = 'login_user';
    
    /**
     * Start Login session
     *
     * @param string $user User Id or Token
     * @param string $duration Session duration in seconds. default = 1hr
     * @return void
     */
    public function startLoginSession($user, $duration = 3600)
    {
        if (!$user) return;
        return Utils::setCookie(self::LOGIN_USER_KEY, $user, $duration, app()->request->ip());
    }

    /**
     * Clear Login session
     * 
     * @return void
     */
    public function clearLoginSession()
    {
        Utils::deleteCookie(self::LOGIN_USER_KEY);
    }

    /**
     * Get Login User
     *
     * @return string|bool
     */
    public function getLoginUser()
    {
        return Utils::getCookie(self::LOGIN_USER_KEY, app()->request->ip());
    }
}
