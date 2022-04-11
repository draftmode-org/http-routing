<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;

interface OpenApiRouterInterface {
    public function getRoute(HttpServerRequestInterface $request) :?OpenApiRoute;
}