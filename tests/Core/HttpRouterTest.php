<?php
namespace Terrazza\Component\HttpRouting\Tests\Core;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Request\HttpServerRequest;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\HttpRouter;
use Terrazza\Component\HttpRouting\Tests\_Mocks\HttpRouting;
use Terrazza\Component\HttpRouting\Tests\_Mocks\HttpRoutingValidator;

class HttpRouterTest extends TestCase {

    function testRouteWithoutValidator() {
        $router             = new HttpRouter(new HttpRouting());
        $request            = new HttpServerRequest("GET", "https://www.terrazza.io");
        $this->assertNull($router->getRoute($request));
    }

    function testRouteWithValidatorSuccessful() {
        $router             = new HttpRouter(
            new HttpRouting(
                new HttpRoute("path", "get", "requestHandlerClass")
            ), new HttpRoutingValidator()
        );
        $request            = new HttpServerRequest("GET", "https://www.terrazza.io");
        $this->assertInstanceOf(HttpRoute::class, $router->getRoute($request));
    }

    function testRouteWithValidatorFailure() {
        $router             = new HttpRouter(
            new HttpRouting(
                new HttpRoute("path", "get", "requestHandlerClass")
            ), new HttpRoutingValidator(new InvalidArgumentException("failure"))
        );
        $request            = new HttpServerRequest("GET", "https://www.terrazza.io");
        $this->expectException(InvalidArgumentException::class);
        $router->getRoute($request);
    }
}