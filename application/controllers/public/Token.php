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
    private $request;
    private $response;

    public function __construct(){
        parent::__construct();
        
        $this->request = OAuth2\Request::createFromGlobals();
        $this->response = new OAuth2\Response();
    }

    /**Obtain access token if authorized*/
    public function get_token()
    {
        $result = $this->get_oauth_server()->grantAccessToken($this->request,$this->response);

        if (is_bool($result))
        {
            $this->response->setParameters(array('success' => $result));
        }
        elseif (!is_null($result))
        {
            $this->response->setParameters($result);

        }
        $this->response->send();

        die();
    }

}