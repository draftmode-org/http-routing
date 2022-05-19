<?php
namespace Terrazza\Component\HttpRouting;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;

interface HttpRouterInterface {
    /**
     * @param HttpServerRequestInterface $request
     * @return HttpRoute|null
     */
    public function getRoute(HttpServerRequestInterface $request) : ?HttpRoute;
}