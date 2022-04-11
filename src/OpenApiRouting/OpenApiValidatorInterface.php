<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;

interface OpenApiValidatorInterface {
    /**
     * @param string $yamlFileName
     * @param OpenApiRoute $route
     * @param HttpServerRequestInterface $request
     */
    public function validate(string $yamlFileName, OpenApiRoute $route, HttpServerRequestInterface $request) : void;
}