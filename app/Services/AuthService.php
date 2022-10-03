<?php

namespace App\Services;

use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\SingletonStatelessInterface;
use Busarm\PhpMini\Traits\SingletonStateless;
use DateTime;
use DateTimeZone;

use function Busarm\PhpMini\Helpers\app;

class AuthService implements SingletonStatelessInterface
{
    use SingletonStateless;

    const LOGIN_USER_PARAM = 'login_user';
    const CSRF_KEY_PARAM = 'csrf_key';

    public function __construct(private RequestInterface $request)
    {
    }
    
    /**
     * Start Login session
     *
     * @param string $user User Id or Token
     * @param string $duration Session duration in seconds. default = 1hr
     * @return bool
     */
    public function startLoginSession($user, $duration = 3600)
    {
        if (!$user) return false;
        return $this->request->cookie()->set(self::LOGIN_USER_PARAM, $user, $duration);
    }

    /**
     * Clear Login session
     * 
     * @return void
     */
    public function clearLoginSession()
    {
        $this->request->cookie()->remove(self::LOGIN_USER_PARAM);
    }

    /**
     * Get Login User
     *
     * @return string|bool
     */
    public function getLoginUser()
    {
        return $this->request->cookie()->get(self::LOGIN_USER_PARAM);
    }

    /**
     * Generate CSRF Token
     * 
     * @return string $key
     * @return bool $force Force generate new key even if it exists
     * @return string
     */
    public function generateCsrfToken($key = null, $force = false)
    {
        if ($force || (empty($key) && empty($key = $this->request->cookie()->get(self::CSRF_KEY_PARAM)))) {
            $key = md5(uniqid($this->request->ip()));
            $this->request->cookie()->set(self::CSRF_KEY_PARAM, $key);
        }
        $date = new DateTime("now", new DateTimeZone("GMT"));
        return sha1(sprintf("%s:%s:%s", $key, $this->request->ip(), $date->format('Y-m-d H')));
    }


    /**
     * Get CSRF Token
     * @return string
     */
    public function getCsrfToken()
    {
        if (!empty($key = $this->request->cookie()->get(self::CSRF_KEY_PARAM))) {
            return $this->generateCsrfToken($key);
        }
        return null;
    }

    /**
     * CSRF Validation
     * @param string $csrfToken
     * @return boolean
     */
    public function validateCsrf($csrfToken)
    {
        if ($csrfToken) {
            return $csrfToken == $this->getCsrfToken();
        }
        return false;
    }
}
