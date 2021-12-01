<?php
defined('OAUTH_BASE_PATH') or exit('No direct script access allowed');

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
        parent::__construct(false, true, true);
    }

    /**
     * Obtain access token if authorized
     * @api token/get
     * @method POST
     * */
    public function get()
    {
        $result = $this->get_oauth_server()->grantAccessToken($this->request, $this->response);
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
        if ($this->get_oauth_server()->verifyResourceRequest($this->request, $this->response)) {
            $this->response->setParameters(array('success' => true, 'message' => 'Api access granted'));
        }
        $this->response->send();
        die;
    }


    /**
     * Delete Access token and refresh token
     * @api token/invalidate
     * @method POST
     * @param access_token String Required
     * @param refresh_token String Optional
     * */
    public function invalidate()
    {
        $done = false;
        $refresh_token = $this->request->request('refresh_token');
        if (!($access_token = $this->request->request('access_token'))) {
            if ($token = $this->get_oauth_server()->getAccessTokenData($this->request)) {
                $access_token = ($token['access_token'] ?? $token['id'] ?? $token['jti'] ?? null);
            }
        }

        if (!empty($access_token)) {
            $done = $this->get_oauth_storage()->unsetAccessToken($access_token);
        }
        if (!empty($refresh_token)) {
            $done = $this->get_oauth_storage()->unsetRefreshToken($refresh_token);
        }

        if ($done) {
            $this->response->setParameters(array('success' => true, 'msg' => 'Successfully cleared access'));
        } else {
            $this->response->setParameters(array('success' => $done, 'msg' => 'Failed to invalidate access'));
        }
        $this->response->send();
        die;
    }
}
