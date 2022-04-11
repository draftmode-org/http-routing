<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\Validator\ValueValidatorInterface;
use Terrazza\Component\Validator\ValueValidatorSchema;

class OpenApiValidator implements OpenApiValidatorInterface {
    private LoggerInterface $logger;
    private ValueValidatorInterface $valueValidator;
    private array $components;

    public function __construct(ValueValidatorInterface $valueValidator, LoggerInterface $logger) {
        $this->valueValidator                       = $valueValidator;
        $this->logger                               = $logger;
    }

    /**
     * @param string $yamlFileName
     * @return void
     */
    private function initializeComponents(string $yamlFileName) : void {
        $yaml                                       = yaml_parse_file($yamlFileName);
        $this->components                           = $yaml["components"] ?? [];
    }

    public function validate(string $yamlFileName, OpenApiRoute $route, HttpServerRequestInterface $request) : void {
        $this->initializeComponents($yamlFileName);
        $this->validateParameters($route->getParameters(), $route->getPath(), $request);
        if ($requestBody = $route->getRequestBody()) {
            if ($requestBodySchemas = $this->getPropertyByKey($requestBody, "content")) {
                $contentType                        = $request->getHeaderLine("content-type");
                $requestBodySchema                  = $this->getRequestContentTypeSchema($contentType, $requestBodySchemas);
                $requestBodyContent                 = $this->getRequestBodyContentEncoded($contentType,$request->getBody()->getContents());
                $this->validateRequestBody($requestBodyContent, $requestBodySchema);
            }
        }
    }

    private function validateParameters(array $properties, string $path, HttpServerRequestInterface $request) : void {
        foreach ($properties as $property) {
            $propertyIn                             = $property["in"] ?? "-";
            $propertyName                           = $property["name"] ?? "-";
            $propertyValue                          = null;
            switch ($propertyIn) {
                case "path":
                    $propertyValue                  = $request->getPathParam($path, $propertyName);
                    break;
                case "query":
                    $propertyValue                  = $request->getQueryParam($propertyName);
                    break;
            }
            if ($propertyValue) {
                if ($propertySchema = $this->getPropertyWithKey($property["schema"], "type")) {
                    try {
                        $contentSchema              = (new ValueValidatorSchema($propertyName))
                            ->setPatterns($propertySchema["patterns"])
                            ->setFormat($propertySchema["format"])
                            ->setMinLength($propertySchema["minLength"])
                            ->setMaxLength($propertySchema["maxLength"])
                            ->setMinItems($propertySchema["minItems"])
                            ->setMaxItems($propertySchema["maxItems"]);
                        $this->valueValidator->validateContent($propertyValue, $contentSchema);
                    } catch (InvalidArgumentException $exception) {
                        throw new InvalidArgumentException("parameter $propertyName in $propertyIn invalid, ".$exception->getMessage());
                    }
                }
            }
        }
    }

    private function getRequestContentTypeSchema(string $contentType, array $requestSchemas) : array {
        if (array_key_exists($contentType, $requestSchemas)) {
            $requestSchema                          = $requestSchemas[$contentType];
            if (array_key_exists("schema", $requestSchema)) {
                return $requestSchema["schema"];
            } else {
                throw new RuntimeException("property schema in content/$contentType missing");
            }
        }
        $supportedContentTypes                      = array_keys($requestSchemas);
        throw new InvalidArgumentException("expected content types (".join(",", $supportedContentTypes)."), given $contentType");
    }

    private function getRequestBodyContentEncoded(string $contentType, string $content) {
        switch ($contentType) {
            default:
            case "application/json":
                $contentEncoded                     = json_decode($content);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $contentEncoded;
                }
                throw new InvalidArgumentException("requestBody is not valid $contentType");
        }
    }

    private function validateRequestBody($content, array $schema) : void {
        if (!array_key_exists("type", $schema)) {
            throw new RuntimeException("missing property in schema, type");
        }
        if (!array_key_exists("properties", $schema)) {
            throw new RuntimeException("missing property in schema, properties");
        }
        $properties                                 = $schema["properties"];
        /**
         * extend schema-required fields
         */
        if (array_key_exists("required", $schema)) {
            foreach ($schema["required"] as $propertyName) {
                if (array_key_exists($propertyName, $properties)) {
                    $properties[$propertyName]["required"] = true;
                }
            }
        }
        $this->validateContentByProperties($properties, $content);
    }

    private function validateContentByProperties(array $properties, $content, ?string $parentPropertyName=null) {
        foreach ($properties as $propertyName => $property) {
            $fullPropertyName                       = $parentPropertyName ? $parentPropertyName.".".$propertyName : $propertyName;
            $inputRequired                          = $property["required"] ?? false;
            $inputExists                            = property_exists($content, $propertyName);
            if ($inputRequired && !$inputExists) {
                throw new InvalidArgumentException("argument $fullPropertyName required, missing");
            }
            if ($inputExists) {
                $inputValue                         = $content->{$propertyName};
                if ($propertyType = $this->getPropertyWithKey($property, "type")) {
                    $this->valueValidator->validateContentType($inputValue, $propertyType["type"] ?? null);
                    if ($propertyType["type"]==="object" && is_object($inputValue)) {
                        $this->validateContentByProperties($propertyType["properties"], $inputValue, $fullPropertyName);
                    }
                    try {
                        $inputSchema                = (new ValueValidatorSchema($propertyName))
                            ->setPatterns($propertyType["patterns"])
                            ->setFormat($propertyType["format"])
                            ->setMinLength($propertyType["minLength"])
                            ->setMaxLength($propertyType["maxLength"])
                            ->setMinItems($propertyType["minItems"])
                            ->setMaxItems($propertyType["maxItems"]);
                        $this->valueValidator->validateContent($inputValue,$inputSchema);
                    } catch (InvalidArgumentException $exception) {
                        throw new InvalidArgumentException("argument $fullPropertyName ".$exception->getMessage());
                    }
                } else {
                    throw new RuntimeException("no type for propertyName found");
                }
            }
        }
    }

    private function getPropertyByKey(array $property, string $propertyKey) :?array {
        if ($schema = $this->getPropertyWithKey($property, $propertyKey)) {
            return $schema[$propertyKey];
        } else {
            return null;
        }
    }
    private function getPropertyWithKey(array $property, string $propertyType) :?array {
        if (array_key_exists($propertyType, $property)) {
            return $property;
        } elseif (array_key_exists("\$ref", $property)) {
            return $this->getPropertyWithKey($this->getComponentsSchema($property["\$ref"]), $propertyType);
        } else {
            return null;
        }
    }

    /**
     * @param string $ref
     * @return array|null
     */
    private function getComponentsSchema(string $ref) :?array {
        $refs                                   = explode("/", $ref);
        array_shift($refs);
        $componentsType                         = array_shift($refs);
        if ($componentsType !== "components") {
            throw new RuntimeException("property components missing in yaml");
        }
        $componentType                          = array_shift($refs);
        if (!array_key_exists($componentType, $this->components)) {
            throw new RuntimeException("components/$componentType missing in yaml");
        }
        $schemaTypes                            = $this->components[$componentType];
        $type                                   = array_shift($refs);
        if (array_key_exists($type, $schemaTypes)) {
            return $schemaTypes[$type];
        }
        return null;
    }
}