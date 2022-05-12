<?php
namespace Terrazza\Component\HttpRouting;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;

interface HttpRoutingInterface {
    public function getRoute(HttpServerRequestInterface $request) : ?HttpRoute;
}