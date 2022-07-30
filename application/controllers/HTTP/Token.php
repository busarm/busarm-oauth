<?php

namespace Application\Controllers\HTTP;

use Application\Controllers\OAuthBaseController;
use Application\Services\OAuthScopeService;

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
        parent::__construct();
    }

    /**
     * Obtain access token if authorized
     */
    public function request()
    {
        $result = $this->oauth->server->grantAccessToken($this->oauth->request, $this->oauth->response);
        if ($result) {
            app()->sendHttpResponse(200, $result);
        }
        app()->sendHttpResponse(401, $this->oauth->response->getParameters());
    }

    /**
     * Verify Token
     */
    public function verify()
    {
        if ($this->oauth->validateAccessToken()) {
            app()->sendHttpResponse(200, $this->success('Api access granted'));
        }
        app()->sendHttpResponse(401, $this->oauth->response->getParameters());
    }

    /**
     * Verify token and get info
     */
    public function info()
    {
        if ($this->oauth->validateAccessToken()) {
            app()->sendHttpResponse(200, $this->success($this->oauth->getAuthToken()));
        }
        app()->sendHttpResponse(401, $this->oauth->response->getParameters());
    }

    /**
     * Verify & Obtain user info for token
     */
    public function user()
    {
        if ($this->oauth->validateAccessToken()) {
            $user =  $this->oauth->storage->getCustomUserWIthClaims(
                $this->oauth->getAuthToken('user_id'),
                array_keys(OAuthScopeService::findOpenIdScope($this->oauth->getAuthToken('scope')) ?: []),
                true
            );
            if (!empty($user)) {
                $this->oauth->response->setError(200, $this->success($user));
            } else {
                $this->oauth->response->setError(404, $this->error('Users does not exist', 'invalid_user'));
            }
        }
        app()->sendHttpResponse(401, $this->oauth->response->getParameters());
    }

    /**
     * Delete Access token and refresh token
     */
    public function invalidate()
    {
        $done = false;
        $access_token = $this->oauth->validateAccessToken() ? ($this->oauth->getAuthToken('id') ?? $this->oauth->getAuthToken('jti')) : null;
        $refresh_token = $this->oauth->request->request('refresh_token');

        if (!empty($access_token)) {
            $done = $this->oauth->storage->unsetAccessToken($access_token);
        }
        if (!empty($refresh_token)) {
            $done = $this->oauth->storage->unsetRefreshToken($refresh_token);
        }

        if ($done) {
            app()->sendHttpResponse(200, $this->success('Successfully cleared access'));
        } else {
            app()->sendHttpResponse(400, $this->error('Failed to invalidate access'));
        }
    }
}
