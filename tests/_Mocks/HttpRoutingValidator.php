<?php
namespace Terrazza\Component\HttpRouting\Tests\_Mocks;
use InvalidArgumentException;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\HttpRoutingReaderInterface;
use Terrazza\Component\HttpRouting\HttpRoutingValidatorInterface;

class HttpRoutingValidator implements HttpRoutingValidatorInterface {
    private ?InvalidArgumentException $exception;
    public function __construct(InvalidArgumentException $exception=null) {
        $this->exception = $exception;
    }

    public function getReader(): HttpRoutingReaderInterface {
        return new class implements HttpRoutingReaderInterface {

            public function load(string $yamlFileName): HttpRoutingReaderInterface {
                return $this;
            }
            public function getRoutes(): array {
                return [];
            }

            public function getParameterParams(string $routePath, string $routeMethod, string $parametersType): ?array {
                return null;
            }

            public function getRequestBodyContents(string $routePath, string $routeMethod): ?array {
                return null;
            }

            public function getRequestBodyParams(array $content, string $contentType): array {
                return [];
            }
        };
    }

    public function validate(HttpRoute $route, HttpServerRequestInterface $request) : void {
        if ($this->exception) {
            throw $this->exception;
        }
    }

    public function setReader(HttpRoutingReaderInterface $reader): HttpRoutingValidatorInterface {
        return $this;
    }
}