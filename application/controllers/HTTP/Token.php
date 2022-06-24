<?php

namespace Application\Controllers\HTTP;

use Application\Controllers\OAuthBaseController;
use  Application\Services\OAuthScopeService;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 12:20 PM
 */

class Token extends OAuthBaseController
{
    public function __construct()
    {
        parent::__construct(false, true);
    }

    /**
     * Obtain access token if authorized
     */
    public function request()
    {
        $result = $this->server->grantAccessToken($this->request, $this->response);
        if ($result) {
            $this->response->setParameters($result);
        }
        $this->response->send();
        die();
    }


    /**
     * Verify Token
     */
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
     */
    public function info()
    {
        if ($this->validateAccessToken()) {
            $this->response->setParameters(array('success' => true, 'data' => $this->getCurrentToken()));
        }
        $this->response->send();
        die;
    }

    /**
     * Verify & Obtain user info for token
     */
    public function user()
    {
        if ($this->validateAccessToken()) {
            $user =  $this->storage->getCustomUserWIthClaims(
                $this->getCurrentToken('user_id'),
                array_keys(OAuthScopeService::findOpenIdScope($this->getCurrentToken('scope')) ?: []),
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
     */
    public function invalidate()
    {
        $done = false;
        $access_token = $this->validateAccessToken() ? ($this->getCurrentToken('id') ?? $this->getCurrentToken('jti')) : null;
        $refresh_token = $this->request->request('refresh_token');

        if (!empty($access_token)) {
            $done = $this->storage->unsetAccessToken($access_token);
        }
        if (!empty($refresh_token)) {
            $done = $this->storage->unsetRefreshToken($refresh_token);
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
