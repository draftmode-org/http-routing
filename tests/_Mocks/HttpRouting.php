<?php
namespace Terrazza\Component\HttpRouting\Tests\_Mocks;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\HttpRoutingInterface;

class HttpRouting implements HttpRoutingInterface {
    private ?HttpRoute $response;
    public function __construct(?HttpRoute $response=null) {
        $this->response = $response;
    }

    public function getRoute(HttpServerRequestInterface $request): ?HttpRoute {
        return $this->response;
    }
}