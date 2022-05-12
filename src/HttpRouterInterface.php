<?php
namespace Terrazza\Component\HttpRouting;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\Exception\HttpInvalidArgumentException;

interface HttpRouterInterface {
    /**
     * @param HttpServerRequestInterface $request
     * @return HttpRoute|null
     * @throws HttpInvalidArgumentException
     */
    public function getRoute(HttpServerRequestInterface $request) : ?HttpRoute;
}