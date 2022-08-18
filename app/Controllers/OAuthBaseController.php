<?php

namespace App\Controllers;

use App\Exceptions\AuthorizationException;
use App\Services\OAuthService;
use App\Services\AuthService;
use System\Dto\BaseDto;
use System\Dto\ResponseDto;
use App\Dto\Response\OAuthErrorDto;

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
    protected function __construct(public $isCLI = false)
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
     *
     * @param BaseDto|string|array|object $data
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
     *
     * @param string $message
     * @param string $type
     * @return OAuthErrorDto
     */
    public function error(string $message, $type = 'unexpected_error'): OAuthErrorDto
    {
        $dto = new OAuthErrorDto();
        $dto->success = false;
        $dto->error = $type;
        $dto->error_description = $message;
        return $dto;
    }
}
