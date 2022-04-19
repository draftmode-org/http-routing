<?php
namespace Terrazza\Component\HttpRouting\ClassRouting;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;

interface ClassRouterInterface {
    public function getRoute(HttpServerRequestInterface $request): ?ClassRoute;
}