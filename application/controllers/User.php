<?php
defined('OAUTH_BASE_PATH') or exit('No direct script access allowed');

/**
 * Created by VSCode.
 * User: Samuel
 * Date: 05/04/2021
 * Time: 1:00 PM
 **/
class Login extends Server
{

    public function __construct()
    {
        parent::__construct(false, true, true);
    }

    /**
     * Get User info based on requested claims
     * @api user/info
     * @method GET*/
    public function info()
    {
        $this->get_oauth_server()->getUserInfoController()->handleUserInfoRequest($this->request, $this->response);
        $this->response->send();
        die;
    }
}
