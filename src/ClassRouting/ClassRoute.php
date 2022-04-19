<?php

namespace Terrazza\Component\HttpRouting\ClassRouting;

class ClassRoute {
    private string $path;
    private ?string $requestBody;

    /**
     * @param string $path
     * @param string|null $requestBody
     */
    public function __construct(string $path, ?string $requestBody)
    {
        $this->path = $path;
        $this->requestBody = $requestBody;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string|null
     */
    public function getRequestBody(): ?string
    {
        return $this->requestBody;
    }
}