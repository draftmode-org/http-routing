<?php
namespace Terrazza\Component\HttpRouting\Tests\OpenApiRouting;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Message\Uri\Uri;
use Terrazza\Component\Http\Request\HttpServerRequest;
use Terrazza\Component\HttpRouting\OpenApiRouting\OpenApiRouter;
use Terrazza\Component\HttpRouting\OpenApiRouting\OpenApiRouteValidator;
use Terrazza\Dev\Logger\Logger;

class OpenApiRouterTest extends TestCase {

    function testFindRouteWithOutValidation() {
        $logger         = new Logger("OpenApiRouter");
        $router         = new OpenApiRouter($logger->createLogger(false));
        $baseUri        = "https://dev.terrazza.io";
        $serverRequest  = new HttpServerRequest("GET", new Uri("$baseUri/payment/12345"));
        $this->assertEquals([
            false,
            true
        ],[
            is_null($router->getRoute("tests/_Examples/api.yaml", $serverRequest)),
            is_null($router->getRoute("tests/_Examples/api.yaml", $serverRequest->withUri(new Uri("$baseUri/payment"))))
        ]);
    }

    function testFindRouteWithValidation() {
        $logger         = new Logger("OpenApiRouter");
        $validator      = new OpenApiRouteValidator($logger->createLogger(false));
        $router         = new OpenApiRouter($logger->createLogger(true), $validator);
        $baseUri        = "https://dev.terrazza.io";
        $serverRequest  = new HttpServerRequest("GET", new Uri("$baseUri/payment/12345"));
        $this->assertEquals([
            false,
            true
        ],[
            is_null($router->getRoute("tests/_Examples/api.yaml", $serverRequest)),
            is_null($router->getRoute("tests/_Examples/api.yaml", $serverRequest->withUri(new Uri("$baseUri/payment"))))
        ]);
    }
}