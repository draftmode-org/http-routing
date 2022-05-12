<?php
namespace Terrazza\Component\HttpRouting\Tests\_Mocks;
use InvalidArgumentException;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\HttpRoutingValidatorInterface;

class HttpRoutingValidator implements HttpRoutingValidatorInterface {
    private ?InvalidArgumentException $exception;
    public function __construct(InvalidArgumentException $exception=null) {
        $this->exception = $exception;
    }

    public function validate(HttpRoute $route, HttpServerRequestInterface $request) : void {
        if ($this->exception) {
            throw $this->exception;
        }
    }
}