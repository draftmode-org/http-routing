<?php

namespace Terrazza\Component\HttpRouting;

class HttpRoute {
    private string $routePath;
    private string $routeMethod;
    private string $requestHandlerClass;

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @param string $requestHandlerClass
     */
    public function __construct(string $routePath, string $routeMethod, string $requestHandlerClass)
    {
        $this->routePath                        = $routePath;
        $this->routeMethod                      = $routeMethod;
        $this->requestHandlerClass              = $requestHandlerClass;
    }

    /**
     * @return string
     */
    public function getRoutePath(): string
    {
        return $this->routePath;
    }

    /**
     * @return string
     */
    public function getRouteMethod(): string
    {
        return $this->routeMethod;
    }

    /**
     * @return string
     */
    public function getRequestHandlerClass(): string
    {
        return $this->requestHandlerClass;
    }
}