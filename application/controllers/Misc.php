<?php

use GuzzleHttp\RequestOptions;

defined('OAUTH_BASE_PATH') or exit('No direct script access allowed');

/**
 * Created by VSCode.
 * User: Samuel
 * Date: 1/13/2022
 * Time: 3:34 PM
 */
class Misc
{ 
    /** @var \OAuth2\Request */
    protected $request;

    /** @var \OAuth2\Response */
    protected $response;

    public function __construct()
    {
        //Create request & response objects
        $this->request = \OAuth2\Request::createFromGlobals();
        $this->response = new \OAuth2\Response();
    }

    /**
     * Process secure link
     *
     * @param string $link
     * @return string
     */
    public function link($data = null)
    {
        $data = $this->request->query("data");
        if (!empty($data)) {
            $link = CIPHER::decrypt(Configs::ENCRYPTION_KEY(), $data);
            if($link) {
                return App::getInstance()->redirect($link);
            }
            return App::getInstance()->showMessage(400, false, 'Invalid Request', 'Failed to process link');
        }
        return App::getInstance()->showMessage(400, false, 'Invalid Request', 'Secure link not available');
    }
}
