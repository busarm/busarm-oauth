<?php

namespace App\Controllers\HTTP;

use App\Controllers\OAuthBaseController;
use App\Services\OAuthScopeService;
use Busarm\PhpMini\App;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 12:20 PM
 */

class Token extends OAuthBaseController
{
    public function __construct(
        private App $app,
        private RequestInterface $request,
        private ResponseInterface $response,
    ) {
        parent::__construct($request);
    }

    /**
     * Obtain access token if authorized
     */
    public function request()
    {
        $result = $this->oauth->server->grantAccessToken($this->oauth->request, $this->oauth->response);
        if ($result) {
            return $this->response->json($result, 200);
        }
        return $this->response->addHttpHeaders($this->oauth->response->getHttpHeaders())->json($this->oauth->response->getParameters(), 401);
    }

    /**
     * Verify Token
     */
    public function verify()
    {
        if ($this->oauth->validateAccessToken()) {
            return $this->success('Api access granted');
        }
        return $this->response->addHttpHeaders($this->oauth->response->getHttpHeaders())->json($this->oauth->response->getParameters(), 401);
    }

    /**
     * Verify token and get info
     */
    public function info()
    {
        if ($this->oauth->validateAccessToken()) {
            return $this->success($this->oauth->getAuthToken());
        }
        return $this->response->addHttpHeaders($this->oauth->response->getHttpHeaders())->json($this->oauth->response->getParameters(), 401);
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
                return $this->success($user);
            } else {
                return $this->response->json($this->error('Users does not exist', 'invalid_user')->toArray(), 404);
            }
        }
        return $this->response->addHttpHeaders($this->oauth->response->getHttpHeaders())->json($this->oauth->response->getParameters(), 401);
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
            return $this->success('Successfully cleared access');
        } else {
            return $this->response->json($this->error('Failed to invalidate access')->toArray(), 400);
        }
    }
}
