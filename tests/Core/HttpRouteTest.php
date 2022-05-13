<?php

namespace Terrazza\Component\HttpRouting\Tests\Core;

use PHPUnit\Framework\TestCase;
use Terrazza\Component\HttpRouting\HttpRoute;

class HttpRouteTest extends TestCase {

    function testCommon() {
        $route = new HttpRoute($path = "path", $method="get", $class="class");
        $this->assertEquals([
            $path,
            $method,
            $class
        ],[
            $route->getRoutePath(),
            $route->getRouteMethod(),
            $route->getRouteHandlerClass()
        ]);
    }
}