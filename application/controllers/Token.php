<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 12:20 PM
 */

class Token extends Server
{
    public function __construct(){
        parent::__construct();
    }

    /**Obtain access token if authorized
     * @api resources/getTokenData
     * @method GET*/
    public function get()
    {
        $result = $this->get_oauth_server()->grantAccessToken($this->request,$this->response);
        if ($result) {
            $this->response->setParameters($result);
        }
        $this->response->send();
        die();
    }


    /**Verify Token
     * @api resources/verifyToken
     * @method GET*/
    public function verify()
    {
        if ($this->get_oauth_server()->verifyResourceRequest($this->request, $this->response)) {
            $this->response->setParameters(array('success' => true, 'message' => 'Api access granted'));
        }
        $this->response->send();
        die;
    }
}