<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

class OpenApiReader implements OpenApiReaderInterface {
    private LoggerInterface $logger;
    private ?array $content=null;
    private ?array $paths=null;
    CONST multipleTypes = ["oneOf"];

    public function __construct(LoggerInterface $logger) {
        $this->logger                               = $logger;
    }

    /**
     * @param string $yamlFileName
     * @return OpenApiReaderInterface
     */
    public function load(string $yamlFileName) : OpenApiReaderInterface {
        if (is_null($this->content)) {
            if (!file_exists($yamlFileName)) {
                throw new RuntimeException("yaml.file $yamlFileName does not exist");
            }
            $content                                = yaml_parse_file($yamlFileName);
            if (is_array($content)) {
                $this->content                      = $content;
            } else {
                throw new RuntimeException("yaml.file $yamlFileName could no be parsed");
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getPaths() : array {
        if (is_null($this->paths)) {
            $skipMethods                            = ["parameters"];
            $yaml                                   = $this->content ?? [];
            $paths                                  = [];
            foreach ($yaml["paths"] ?? [] as $uri => $methods) {
                $uriParameters                      = [];
                foreach ($methods as $method => $parameters) {
                    if ($method === "parameters") {
                        $uriParameters              = $parameters;
                    }
                }
                foreach ($methods as $method => $properties) {
                    if (in_array($method, $skipMethods)) {
                        continue;
                    }
                    if (!array_key_exists($uri, $paths)) {
                        $paths[$uri]                = [];
                    }
/* TODO no need to merge it that deep */
                    $properties["parameters"]       = $this->mergeParameters($uriParameters, $properties["parameters"] ?? []);
                    $paths[$uri][$method]           = $properties;
                }
            }
            $this->paths                            = $paths;
        }
        return $this->paths;
    }


    /**
     * @param string $ref
     * @return array
     */
    public function getContentByRef(string $ref) : array {
        $content                                = $this->content ?? [];
        $refs                                   = explode("/", $ref);
        array_shift($refs);
        $nodes                                  = [];
        foreach ($refs as $refKey) {
            $nodes[]                            = $refKey;
            if (array_key_exists($refKey, $content)) {
                $content                        = $content[$refKey];
            } else {
                throw new RuntimeException("node ".join("/", $nodes). " does not exist");
            }
        }
        if (count($nodes) === 0) {
            throw new RuntimeException("node $ref does not exist");
        }
        if (is_null($content)) {
            throw new RuntimeException("node ".join("/", $nodes). " exists, but does not have any content");
        }
        return $content;
    }

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @param string $parametersType
     * @return array|null
     */
    public function getParameterProperties(string $routePath, string $routeMethod, string $parametersType) :?array {
        $this->logger->debug("get properties for $routePath:$routeMethod");
        if ($properties = $this->getPath($routePath, $routeMethod)) {
            $this->logger->debug("...properties for $parametersType found");
            if (array_key_exists($parametersType, $properties["parameters"])) {
                return ["type" => "object", "properties" => $properties["parameters"][$parametersType]];
            } else {
                $this->logger->debug("...attribute parameters in properties not found");
            }
        } else {
            $this->logger->debug("...properties for $parametersType found");
        }
        return null;
    }

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @return array|null
     */
    public function getRequestBodyContents(string $routePath, string $routeMethod) :?array {
        $this->logger->debug("get requestBody content for $routePath:$routeMethod");
        $properties                                 = $this->getPath($routePath, $routeMethod);
        if (!is_array($properties)) {
            throw new RuntimeException("path for $routePath:$routeMethod not found");
        }
        if (!array_key_exists("requestBody", $properties)) {
            return null;
        }
        $requestBodyProperties                      = $properties["requestBody"];

        if (array_key_exists("\$ref", $requestBodyProperties)) {
            $contentRef                             = $requestBodyProperties["\$ref"];
            $requestBodyProperties                  = $this->getContentByRef($contentRef);
        }
        $contentNode                                = "content";
        if (!array_key_exists($contentNode, $requestBodyProperties)) {
            throw new RuntimeException("node content for requestBody $routePath:$routeMethod does not exist");
        }
        $this->logger->debug("requestBody:content found");
        return $requestBodyProperties[$contentNode];
    }

    /**
     * @param array $content
     * @param string $contentType
     * @return array
     */
    public function getRequestBodyProperties(array $content, string $contentType) : array {
        if (!array_key_exists($contentType, $content)) {
            throw new InvalidArgumentException("requestBody Content-Type not accepted, given ".$contentType);
        }
        $content                                    = $content[$contentType];
        $schemaNode                                 = "schema";
        if (!is_array($content) ||
            !array_key_exists($schemaNode, $content)) {
            throw new RuntimeException("node $schemaNode for $contentType does not exist or is not an array");
        }
        return $this->buildContentProperties("requestBody", $content[$schemaNode]);
    }

    /**
     * @param string $propertyName
     * @param array $properties
     * @param string|null $parentName
     * @return array
     */
    private function buildContentProperties(string $propertyName, array $properties, ?string $parentName=null) : array {
        $this->logger->debug("buildContentProperties for $propertyName", $properties);
        $propertyRequired                           = $properties["required"] ?? null;
        while (array_key_exists("\$ref", $properties)) {
            $propertyRef                            = $properties["\$ref"];
            $this->logger->debug("...load property ref $propertyRef");
            $properties                             = $this->getContentByRef($properties["\$ref"]);
        }
        $propertyFullName                           = $parentName ? $parentName . "." . $propertyName : $propertyName;
        foreach (self::multipleTypes as $multipleType) {
            if (array_key_exists($multipleType, $properties)) {
                $properties["type"]                 = $multipleType;
                $properties["properties"]           = $properties[$multipleType];
            }
        }
        if (array_key_exists("type", $properties)) {
             if (array_key_exists("properties", $properties)) {
                $childSchemas = [];
                foreach ($properties["properties"] as $childName => $childProperties) {
                    $childSchema = $this->buildContentProperties(
                        $childName, $childProperties, $propertyFullName);
                    if (is_array($propertyRequired) &&
                        in_array($childName, $propertyRequired)) {
                        $childSchema["required"] = true;
                    }
                    $childSchemas[$childName] = $childSchema;
                }
                $properties["properties"] = $childSchemas;
                unset($properties["required"]);
            } else {
                if ($propertyRequired === true) {
                    $properties["required"] = true;
                }
            }
            foreach (self::multipleTypes as $multipleType) {
                unset($properties[$multipleType]);
            }
            return $properties;
        } else {
            throw new RuntimeException("node type for $propertyFullName does not exist");
        }
    }

    /**
     * @param array $uriParameters
     * @param array $methodParameters
     * @return array
     */
    private function mergeParameters(array $uriParameters, array $methodParameters) : array {
        $parameters                                 = array_filter(array_merge($uriParameters, $methodParameters));
        $response                                   = [];
        foreach ($parameters as $parameter) {
            $type                                   = $parameter["in"] ?? "-";
            if (!array_key_exists($type, $response)) {
                $response[$type]                    = [];
            }
            $name                                   = $parameter["name"] ?? "-";
            $schemaNode                             = "schema";
            if (!array_key_exists($schemaNode, $parameter)) {
                throw new RuntimeException("node $schemaNode in parameters/$type for $name does not exist");
            }
            $parameterSchema                        = $parameter[$schemaNode];
            if (array_key_exists("required", $parameter)) {
                $parameterSchema["required"]        = $parameter["required"];
            }
            $response[$type][$name]                 = $this->buildContentProperties($name, $parameterSchema);
        }
        return $response;
    }

    /**
     * @param string $path
     * @param string $method
     * @return array|null
     */
    private function getPath(string $path, string $method) :?array {
        $paths                                       = $this->getPaths();
        if (array_key_exists($path, $paths)) {
            if (array_key_exists($method, $paths[$path])) {
                return $paths[$path][$method];
            }
        }
        return null;
    }
}