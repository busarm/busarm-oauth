<?php

namespace System\Interfaces;

use System\Interfaces\MiddlewareInterface;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 28/7/2022
 * Time: 5:22 PM
 */
interface RouterInterface
{
    /**
     * @return string|null
     */
    public function getRequestMethod(): string|null;

    /**
     * @return string|null
     */
    public function getRequestPath(): string|null;

    /**
     * @return RouteInterface|null
     */
    public function getCurrentRoute(): RouteInterface|null;

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array;

    /**
     * Process routing
     * @return MiddlewareInterface[]
     */
    public function process(): array;

    /**
     * @param Route $route 
     * @return self
     */
    public function addRoute(RouteInterface $route): self;

    /**
     * @param Route[] $route 
     * @return self
     */
    public function addRoutes(array $routes): self;
}
