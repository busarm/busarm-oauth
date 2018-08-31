<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

require_once OAUTH_BASE_PATH.'Server.php';

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
        parent::__construct();
    }

    /**Obtain access token if authorized*/
    public function get_token()
    {
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();

        $result = $this->get_oauth_server()->grantAccessToken($request,$response);

        if (is_bool($result))
        {
            $response->setParameters(array('success' => $result));
        }
        elseif (!is_null($result))
        {
            $response->setParameters($result);

        }
        $response->send();


        die();
    }

}