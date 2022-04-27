<?php

namespace Terrazza\Component\HttpRouting;

class HttpRoute {
    private string $routePath;
    private string $routeMethod;

    /**
     * @param string $routePath
     * @param string $routeMethod
     */
    public function __construct(string $routePath, string $routeMethod)
    {
        $this->routePath                        = $routePath;
        $this->routeMethod                      = $routeMethod;
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
}