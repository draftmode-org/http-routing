<?php
namespace Terrazza\Component\HttpRouting\Tests\OpenApiRouting;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\HttpRouting\OpenApiRouting\OpenApiReader;
use Terrazza\Dev\Logger\Logger;

class OpenApiReaderTest extends TestCase {
    CONST yamlFileName          = "tests/_Examples/api.yaml";
    CONST yamlFailureFileName   = "tests/_Examples/apiFailure.yaml";

    function testFailureFileExists() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $this->expectException(RuntimeException::class);
        $reader->load("unknown.file");
    }

    function testFailureFileYamlFailure() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $this->expectException(RuntimeException::class);
        $reader->load(__FILE__);
    }

    function testFailureGetRequestBodyContentsEmptyNode() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyContents("/payments", "patch");
    }

    function testGetRequestBodyContents() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFileName);
        $this->assertEquals([
            true,
            false, // method has no requestBody not found
            false, // uri not found
        ],[
            !is_null($reader->getRequestBodyContents("/payments", "post")),
            !is_null($reader->getRequestBodyContents("/payments", "get")),
            !is_null($reader->getRequestBodyContents("/unknown", "delete")),
        ]);
    }

    function testFailureGetRequestBodyContentsRouteNotFound() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyContents("/payments", "change");
    }

    function testFailureGetRequestBodyContentsContentMissing() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyContents("/payments", "put");
    }

    function testFailureGetParameterParamsNodeSchemaMissing() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getParameterParams("/animals", "get", "query");
    }

    function testGetRequestBodyParamsOneOf() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFileName);
        $contents           = $reader->getRequestBodyContents("/animals", "post");
        $this->assertIsArray($reader->getRequestBodyParams($contents, "application/json"));
    }

    function testFailureGetRequestBodyParamsContentType() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFailureFileName);
        $this->expectException(InvalidArgumentException::class);
        $reader->getRequestBodyParams([], "application/json");
    }

    function testFailureGetRequestBodyParamsNodeSchemaMissing() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyParams(["application/json" => []], "application/json");
    }

    function testFailureGetRequestBodyParamsNodeTypeMissing() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyParams(["application/json" => ["schema" => []]], "application/json");
    }

    function testFailureGetContentByRefNodeNotFound() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(true);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getParameterParams("/animals", "patch", "query");
    }
}