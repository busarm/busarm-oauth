<?php

namespace App\Controllers;

use App\Exceptions\AuthorizationException;
use App\Services\OAuthService;
use App\Services\AuthService;
use Busarm\PhpMini\Dto\ResponseDto;
use App\Dto\Response\OAuthErrorDto;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Request;
use Busarm\PhpMini\Response;

use function Busarm\PhpMini\Helpers\app;

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
     *
     * @param RequestInterface|null $request Set to NULL if CLI
     */
    protected function __construct(private RequestInterface|null $request = null)
    {
        // Check cli
        if (!$request && !app()->isCli) {
            throw new AuthorizationException('Unauthorized request');
        }

        $request = $request ?? Request::fromGlobal();

        // Create OAuth Service
        $this->oauth = OAuthService::make($request);

        // Create Auth Service
        $this->auth = AuthService::make($request);
    }

    /**
     * Get server status
     */
    public function ping()
    {
        $response = new ResponseDto();
        $response->success = true;
        $response->message = 'Server Online';
        $response->env = app()->env;
        $response->version = app()->config->version;
        $response->duration = (floor(microtime(true) * 1000) - app()->startTimeMs);
        return $response;
    }

    /**
     * Get success response
     *
     * @param \Busarm\PhpMini\Dto\BaseDto|string|array|object $data
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
