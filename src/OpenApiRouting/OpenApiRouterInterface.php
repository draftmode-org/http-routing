<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;

interface OpenApiRouterInterface {
    /**
     * @param string $yamlFileName
     * @param HttpServerRequestInterface $request
     * @return HttpRoute|null
     */
    public function getRoute(string $yamlFileName, HttpServerRequestInterface $request) :?HttpRoute;
}