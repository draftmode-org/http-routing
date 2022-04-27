<?php
namespace Terrazza\Component\HttpRouting\ClassRouting;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;

interface HttpClassRouterInterface {
    /**
     * @param string $routingFileName
     * @param HttpServerRequestInterface $request
     * @return HttpRoute|null
     */
    public function getRoute(string $routingFileName, HttpServerRequestInterface $request): ?HttpRoute;
}