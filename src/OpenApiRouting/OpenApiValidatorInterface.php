<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;

interface OpenApiValidatorInterface {
    /**
     * @param string $yamlFileName
     * @param HttpRoute $route
     * @param HttpServerRequestInterface $request
     */
    public function validate(string $yamlFileName, HttpRoute $route, HttpServerRequestInterface $request) : void;
}