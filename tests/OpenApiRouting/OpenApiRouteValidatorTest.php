<?php
namespace Terrazza\Component\HttpRouting\Tests\OpenApiRouting;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Message\Uri\Uri;
use Terrazza\Component\Http\Request\HttpServerRequest;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\OpenApiRouting\OpenApiRouteValidator;
use Terrazza\Dev\Logger\Logger;

class OpenApiRouteValidatorTest extends TestCase {
    CONST routingFileName   = "tests/_Examples/api.yaml";
    CONST baseUri           = "https://test.terrazza.io";

    function testSuccessful() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments/12345";
        $serverRequest      = new HttpServerRequest("GET", new Uri(self::baseUri.$path));
        $httpRoute          = new HttpRoute("/payments/{paymentId}", "get", "requestHandlerClass");
        $validator->validate($httpRoute, $serverRequest);
        $this->assertTrue(true);
    }

    function testFailurePathParam() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments/1234509";
        $serverRequest      = new HttpServerRequest("GET", new Uri(self::baseUri.$path));
        $httpRoute          = new HttpRoute("/payments/{paymentId}", "get", "requestHandlerClass");
        $this->expectException(InvalidArgumentException::class);
        $validator->validate($httpRoute, $serverRequest);
    }

    function testFailureQueryParam() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments";
        $serverRequest      = new HttpServerRequest("GET", new Uri(self::baseUri.$path));
        // queryParam paymentFrom required, missing
        $httpRoute          = new HttpRoute("/payments", "get", "requestHandlerClass");
        $this->expectException(InvalidArgumentException::class);
        $validator->validate($httpRoute, $serverRequest);
    }

    function testRequestBodySuccessful() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments";
        $serverRequest      = (new HttpServerRequest("POST", new Uri(self::baseUri.$path)))
            ->withBody(json_encode(["date" => "2022-01-01"]));
        $httpRoute          = new HttpRoute("/payments", "post", "requestHandlerClass");
        $validator->validate($httpRoute, $serverRequest);
        $this->assertTrue(true);
    }

    function testRequestBodyFailureNoContent() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments";
        $serverRequest      = (new HttpServerRequest("post", new Uri(self::baseUri.$path)));
        $httpRoute          = new HttpRoute("/payments", "post", "requestHandlerClass");
        $this->expectException(InvalidArgumentException::class);
        $validator->validate($httpRoute, $serverRequest);
    }

    function testRequestBodyFailureContentInvalid() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $validator          = new OpenApiRouteValidator(self::routingFileName, $logger);
        $path               = "/payments";
        $serverRequest      = (new HttpServerRequest("POST", new Uri(self::baseUri.$path)))
            ->withBody(json_encode(["date" => "2022-31-01"]));
        $httpRoute          = new HttpRoute("/payments", "post", "requestHandlerClass");
        $this->expectException(InvalidArgumentException::class);
        $validator->validate($httpRoute, $serverRequest);
    }
}