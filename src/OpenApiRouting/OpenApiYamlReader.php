<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;

use Psr\Log\LoggerInterface;
use RuntimeException;

class OpenApiYamlReader implements OpenApiYamlReaderInterface {
    private LoggerInterface $logger;
    private array $content=[];
    public function __construct(LoggerInterface $logger) {
        $this->logger                               = $logger;
    }

    /**
     * @param string $yamlFileName
     * @return OpenApiYamlReaderInterface
     */
    public function load(string $yamlFileName) : OpenApiYamlReaderInterface {
        if (!file_exists($yamlFileName)) {
            throw new RuntimeException("OpenApiReader file $yamlFileName does not exist");
        }
        $this->content                          = yaml_parse_file($yamlFileName);
        return $this;
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

    /**
     * @return array
     */
    public function getPaths() : array {
        $yaml                                       = $this->content;
        $paths                                      = [];
        foreach ($yaml["paths"] ?? [] as $uri => $methods) {
            $uriParameter                           = [];
            foreach ($methods as $method => $parameters) {
                if ($method === "parameters") {
                    $uriParameter                   = $parameters;
                }
            }
            foreach ($methods as $method => $properties) {
                if ($method === "parameters") {
                    continue;
                }
                $properties["parameters"]            = array_filter(
                    array_merge($properties["parameters"] ?? [],
                        $uriParameter));
                if (!array_key_exists($uri, $paths)) {
                    $paths[$uri]                    = [];
                }
                $paths[$uri][$method]               = $properties;
            }
        }
        return $paths;
    }

    /**
     * @param string $ref
     * @return array
     */
    public function getContentByRef(string $ref) : array {
        $content                                = $this->content;
        $refs                                   = explode("/", $ref);
        array_shift($refs);
        $node                                   = [];
        foreach ($refs as $refKey) {
            $node[]                             = $refKey;
            if (array_key_exists($refKey, $content)) {
                $content                            = $content[$refKey];
            } else {
                throw new RuntimeException("node ".join("/", $node). " missing in yaml");
            }
        }
        return $content;
    }

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @param string $parametersType
     * @return array
     */
    public function getParameterProperties(string $routePath, string $routeMethod, string $parametersType) : array {
        $this->logger->debug("get properties $routePath:$routeMethod/$parametersType");
        $properties                                 = $this->getPath($routePath, $routeMethod);
        $schema                                     = [];
        foreach ($properties["parameters"] ?? [] as $parameter) {
            $parameterType                          = $parameter["in"] ?? "-";
            if ($parametersType === $parameterType) {
                $parameterName                      = $parameter["name"] ?? "-";
                $schemaNode                         = "schema";
                if (!array_key_exists($schemaNode, $parameter)) {
                    throw new RuntimeException("node $schemaNode in properties/$parametersType for $parameterName does not exist");
                }
                $parameterSchema                    = $parameter[$schemaNode];
                if (array_key_exists("required", $parameter)) {
                    $parameterSchema["required"]    = $parameter["required"];
                }
                $schema[$parameterName]             = $this->buildParameterProperties($parameterName, $parameterSchema);
            }
        }
        return $schema;
    }

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @param string $validContentType
     * @return array|null
     */
    public function getRequestBodyProperties(string $routePath, string $routeMethod, string $validContentType) :?array {
        $this->logger->debug("get properties $routePath:$routeMethod/$validContentType");
        $properties                                 = $this->getPath($routePath, $routeMethod);
        if (!array_key_exists("requestBody", $properties)) {
            return null;
        }
        $requestBodyProperties                      = $properties["requestBody"];
        $contentRef                                 = $routePath. " for method $routeMethod";

        if (array_key_exists("\$ref", $requestBodyProperties)) {
            $contentRef                             = $requestBodyProperties["\$ref"];
            $requestBodyProperties                  = $this->getContentByRef($contentRef);
        }

        $contentNode                                = "content";
        if (!array_key_exists($contentNode, $requestBodyProperties)) {
            throw new RuntimeException("node $contentNode for $contentRef does not exist");
        }
        $contentRef                                 .= "/$contentNode";
        $requestBodyContent                         = $requestBodyProperties[$contentNode];

        if (!array_key_exists($validContentType, $requestBodyContent)) {
            throw new RuntimeException("node $validContentType for $contentRef does not exist");
        }
        $contentRef                                 .= "/".$validContentType;
        $requestBodyContentType                     = $requestBodyContent[$validContentType];

        $schemaNode                                 = "schema";
        if (!array_key_exists($schemaNode, $requestBodyContentType)) {
            throw new RuntimeException("node $schemaNode for $contentRef does not exist");
        }
        return $this->buildParameterProperties("", $requestBodyContentType[$schemaNode]);
    }

    /**
     * @param string $propertyName
     * @param array $properties
     * @param string|null $parentName
     * @return array
     */
    private function buildParameterProperties(string $propertyName, array $properties, string $parentName=null) : array {
        $propertyFullName                           = $parentName ? $parentName . "." . $propertyName : $propertyName;
        $propertyRequired                           = (array_key_exists("required", $properties) && $properties["required"]);
        if (array_key_exists("\$ref", $properties)) {
            $properties                             = $this->getContentByRef($properties["\$ref"]);
        }
        if (!array_key_exists("type", $properties)) {
            throw new RuntimeException("node type for $propertyFullName does not exist");
        }
        if (!array_key_exists("required", $properties) && $propertyRequired) {
            $properties["required"]                 = $propertyRequired;
        }
        if (array_key_exists("properties", $properties)) {
            $childSchemas                           = [];
            foreach ($properties["properties"] as $childName => $childProperties) {
                $childSchemas[$childName]           = $this->buildParameterProperties(
                    $childName, $childProperties, $propertyFullName);
            }
            $properties["properties"]               = $childSchemas;
        }
        return $properties;
    }
}