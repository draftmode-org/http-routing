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

    function testFailureGetContentByRef() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFileName);
        $this->expectException(RuntimeException::class);
        $reader->getContentByRef("unknown");
    }

    function testFailureGetContentByRefPartial() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFileName);
        $this->expectException(RuntimeException::class);
        $reader->getContentByRef("#/components/requestBodies/unknown");
    }

    function testFailureGetContentByRefEmptyNode() {
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
            false
        ],[
            !is_null($reader->getRequestBodyContents("/payments", "post")),
            !is_null($reader->getRequestBodyContents("/payments", "get"))
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

    function testGetRequestBodyPropertiesOneOf() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFileName);
        $contents           = $reader->getRequestBodyContents("/animals", "post");
        $this->assertIsArray($reader->getRequestBodyProperties($contents, "application/json"));
    }

    function testFailureGetRequestBodyPropertiesContentType() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFailureFileName);
        $this->expectException(InvalidArgumentException::class);
        $reader->getRequestBodyProperties([], "application/json");
    }

    function testFailureGetRequestBodyPropertiesNodeSchemaMissing() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyProperties(["application/json" => []], "application/json");
    }

    function testFailureGetRequestBodyPropertiesNodeTypeMissing() {
        $logger             = (new Logger("OpenApiRouter"))->createLogger(false);
        $reader             = new OpenApiReader($logger);
        $reader             = $reader->load(self::yamlFailureFileName);
        $this->expectException(RuntimeException::class);
        $reader->getRequestBodyProperties(["application/json" => ["schema" => []]], "application/json");
    }
}