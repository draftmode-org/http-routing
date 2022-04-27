<?php

namespace Terrazza\Component\HttpRouting\ClassRouting;

class HttpClassRouteMethod {
    private string $method;
    private ?string $requestBodyClass;

    /**
     * @param string $method
     * @param string|null $requestBodyClass
     */
    public function __construct(string $method, string $requestBodyClass=null)
    {
        $this->method = $method;
        $this->requestBodyClass = $requestBodyClass;
    }

    /**
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function getRequestBodyClass() :?string {
        return $this->requestBodyClass;
    }
}