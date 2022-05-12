<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

class OpenApiYamlReader implements OpenApiYamlReaderInterface {
    private LoggerInterface $logger;
    private ?array $content=null;
    private ?array $paths=null;
    public function __construct(LoggerInterface $logger) {
        $this->logger                               = $logger;
    }

    /**
     * @param string $yamlFileName
     * @return OpenApiYamlReaderInterface
     */
    public function load(string $yamlFileName) : OpenApiYamlReaderInterface {
        if (is_null($this->content)) {
            if (!file_exists($yamlFileName)) {
                throw new RuntimeException("yaml.file $yamlFileName does not exist");
            }
            $this->content                          = yaml_parse_file($yamlFileName);
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
        $node                                   = [];
        foreach ($refs as $refKey) {
            $node[]                             = $refKey;
            if (array_key_exists($refKey, $content)) {
                $content                            = $content[$refKey];
            } else {
                throw new RuntimeException("node ".join("/", $node). " does not exist");
            }
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
        $this->logger->debug("get properties $routePath:$routeMethod/$parametersType");
        if ($properties = $this->getPath($routePath, $routeMethod)) {
            if (array_key_exists($parametersType, $properties["parameters"])) {
                return $properties["parameters"][$parametersType];
            }
        }
        return null;
    }

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @param string $validContentType
     * @return array|null
     */
    public function getRequestBodyContents(string $routePath, string $routeMethod) :?array {
        $this->logger->debug("get properties $routePath:$routeMethod");
        $properties                                 = $this->getPath($routePath, $routeMethod);
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
            return null;
        }
        return $requestBodyProperties[$contentNode];
    }

    /**
     * @param array $content
     * @param string $contentType
     * @return array
     */
    public function getRequestBodyContentByContentType(array $content, string $contentType) : array {
        if (!array_key_exists($contentType, $content)) {
            throw new InvalidArgumentException("requestBody content-type not accepted, given ".$contentType);
        }
        $content                                    = $content[$contentType];
        $schemaNode                                 = "schema";
        if (!array_key_exists($schemaNode, $content)) {
            throw new RuntimeException("node $schemaNode for $contentType does not exist");
        }
        $properties                                 = $this->buildContentProperties("requestBody", $content[$schemaNode]);
        return $properties["properties"];
    }

    /**
     * @param string $propertyName
     * @param array $properties
     * @param string|null $parentName
     * @return array
     */
    private function buildContentProperties(string $propertyName, array $properties, ?string $parentName=null) : array {
        $propertyFullName                           = $parentName ? $parentName . "." . $propertyName : $propertyName;
        $propertyRequired                           = $properties["required"] ?? null;
        if (array_key_exists("\$ref", $properties)) {
            $properties                             = $this->getContentByRef($properties["\$ref"]);
        }
        if (!array_key_exists("type", $properties)) {
            throw new RuntimeException("node type for $propertyFullName does not exist");
        }
        if (array_key_exists("properties", $properties)) {
            $childSchemas                           = [];
            foreach ($properties["properties"] as $childName => $childProperties) {
                $childSchema                        = $this->buildContentProperties(
                    $childName, $childProperties, $propertyFullName);
                if (is_array($propertyRequired) &&
                    in_array($childName, $propertyRequired)) {
                    $childSchema["required"]        = true;
                }
                $childSchemas[$childName]           = $childSchema;
            }
            $properties["properties"]               = $childSchemas;
        }
        return $properties;
    }
/*

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
 */

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
            $response[$type][$name]                 = $this->buildParameterProperties($name, $parameterSchema);
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