<?php

namespace Application\Controllers;

use System\Scopes;
use System\Server;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 12:20 PM
 */

class Token extends Server
{
    public function __construct()
    {
        parent::__construct(false, true);
    }

    /**
     * Obtain access token if authorized
     * @api token/get
     * @method POST
     * */
    public function request()
    {
        $result = $this->getOauthServer()->grantAccessToken($this->request, $this->response);
        if ($result) {
            $this->response->setParameters($result);
        }
        $this->response->send();
        die();
    }


    /**
     * Verify Token
     * @api token/verify
     * @method GET|POST
     * */
    public function verify()
    {
        if ($this->validateAccessToken()) {
            $this->response->setParameters(array('success' => true, 'message' => 'Api access granted'));
        }
        $this->response->send();
        die;
    }

    /**
     * Verify token and get info
     * @api token/info
     * @method GET|POST 
     * */
    public function info()
    {
        if ($this->validateAccessToken()) {
            $this->response->setParameters(array('success' => true, 'data' => $this->getTokenInfo()));
        }
        $this->response->send();
        die;
    }

    /**
     * Verify & Obtain user info for token
     * @api token/user
     * @method GET|POST 
     * */
    public function user()
    {
        if ($this->validateAccessToken()) {
            $user =  $this->getOauthStorage()->getSingleUserInfoForClaims(
                $this->getTokenInfo('user_id'),
                array_keys(Scopes::findOpenIdScope($this->getTokenInfo('scope')) ?: []),
                true
            );
            if (!empty($user)) {
                $this->response->setParameters($this->success($user));
            } else {
                $this->response->setStatusCode(404);
                $this->response->setParameters($this->error('Users does not exist', 'invalid_user'));
            }
        }
        $this->response->send();
        die;
    }

    /**
     * Delete Access token and refresh token
     * @api token/invalidate
     * @method POST
     * @param refresh_token String Optional
     * */
    public function invalidate()
    {
        $done = false;
        $access_token = $this->validateAccessToken() ? ($this->getTokenInfo('id') ?? $this->getTokenInfo('jti')) : null;
        $refresh_token = $this->request->request('refresh_token');

        if (!empty($access_token)) {
            $done = $this->getOauthStorage()->unsetAccessToken($access_token);
        }
        if (!empty($refresh_token)) {
            $done = $this->getOauthStorage()->unsetRefreshToken($refresh_token);
        }

        if ($done) {
            $this->response->setParameters(array('success' => true, 'msg' => 'Successfully cleared access'));
        } else {
            $this->response->setStatusCode(400);
            $this->response->setParameters(array('success' => false, 'msg' => 'Failed to invalidate access'));
        }
        $this->response->send();
        die;
    }
}
