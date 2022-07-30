<?php

namespace Application\Controllers;

use Application\Dto\OAuthErrorDto;
use Application\Exceptions\AuthorizationException;
use Application\Services\OAuthService;
use Application\Services\AuthService;
use System\Dto\ResponseDto;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/12/2018
 * Time: 1:17 AM
 */
class OAuthBaseController
{
    
    /** @var OAuthService */
    protected $oauth;

    /** @var AuthService */
    protected $auth;

    /**
     * Server constructor.
     * @param boolean $isCLI Is CLI Application
     */
    protected function __construct($isCLI = false)
    {
        // Check cli
        if ($isCLI && !is_cli()) {
            throw new AuthorizationException('Unauthorized request');
        }

        // Create OAuth Service
        $this->oauth = OAuthService::getInstance();

        // Create Auth Service
        $this->auth = AuthService::getInstance();
    }

    /**
     * Get success response
     * @return ResponseDto
     */
    public function success($data): ResponseDto
    {
        $dto = new ResponseDto();
        if (is_string($data)) {
            $dto->success = true;
            $dto->message = $data;
            return $dto;
        } else {
            $dto = new ResponseDto();
            $dto->success = true;
            $dto->data = $data;
        }
        return $dto;
    }

    /**
     * Get success response
     * @return OAuthErrorDto
     */
    public function error($message, $type = 'unexpected_error'): OAuthErrorDto
    {
        $dto = new OAuthErrorDto();
        $dto->success = false;
        $dto->error = $type;
        $dto->error_description = $message;
        return $dto;
    }
}
