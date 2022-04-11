<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;

class OpenApiRoute {
    private string $path;
    private string $operationId;
    private array $parameters;
    private ?array $requestBody;
    private ?array $responses;

    /**
     * @param string $path
     * @param string $operationId
     * @param array $parameters
     * @param array|null $requestBody
     * @param array|null $responses
     */
    public function __construct(string $path, string $operationId, array $parameters, ?array $requestBody, ?array $responses)
    {
        $this->path = $path;
        $this->operationId = $operationId;
        $this->parameters = $parameters;
        $this->requestBody = $requestBody;
        $this->responses = $responses;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getOperationId(): string
    {
        return $this->operationId;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array|null
     */
    public function getRequestBody(): ?array
    {
        return $this->requestBody;
    }

    /**
     * @return array|null
     */
    public function getResponses(): ?array
    {
        return $this->responses;
    }
}