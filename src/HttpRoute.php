<?php

namespace Terrazza\Component\HttpRouting;

class HttpRoute {
    private string $routePath;
    private string $routeMethod;
    private string $routeHandlerClass;

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @param string $routeHandlerClass
     */
    public function __construct(string $routePath, string $routeMethod, string $routeHandlerClass)
    {
        $this->routePath                        = $routePath;
        $this->routeMethod                      = $routeMethod;
        $this->routeHandlerClass                = $routeHandlerClass;
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
    public function getRouteHandlerClass(): string
    {
        return $this->routeHandlerClass;
    }
}