<?php
namespace Terrazza\Component\HttpRouting;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\Exception\HttpInvalidArgumentException;

interface HttpRoutingValidatorInterface {
    /**
     * @param HttpRoute $route
     * @param HttpServerRequestInterface $request
     * @throws HttpInvalidArgumentException
     */
    public function validate(HttpRoute $route, HttpServerRequestInterface $request) : void;
}