<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\Routing\IRouteMatcher;
use Terrazza\Component\Routing\Route;
use Terrazza\Component\Routing\RouteMatcher;
use Terrazza\Component\Routing\RouteSearch;

class OpenApiRouter implements OpenApiRouterInterface {
    private string $yamlFileName;
    private IRouteMatcher $routeMatcher;
    private LoggerInterface $logger;

    public function __construct(string $yamlFileName, LoggerInterface $logger) {
        $this->yamlFileName                         = $yamlFileName;
        $this->logger                               = $logger;
        $this->routeMatcher                         = new RouteMatcher($logger);
    }

    /**
     * @return array
     */
    private function getPaths() : array {
        if (!file_exists($this->yamlFileName)) {
            throw new RuntimeException("OpenApiRouter file ".$this->yamlFileName." does not exist");
        }
        $yaml                                       = yaml_parse_file($this->yamlFileName);
        return $yaml["paths"] ?? [];
    }

    /**
     * @param HttpServerRequestInterface $request
     * @return OpenApiRoute|null
     */
    public function getRoute(HttpServerRequestInterface $request) :?OpenApiRoute {
        $routeSearch                                = new RouteSearch(
            $request->getUri()->getPath(),
            $request->getMethod(),
        );
        foreach ($this->getPaths() as $uri => $methods) {
            $pathParameters                         = [];
            foreach ($methods as $method => $properties) {
                $lMethod                            = strtolower($method);
                if ($lMethod === "parameters") {
                    $pathParameters                 = $properties;
                    continue;
                }
                $route                              = new Route(
                    $uri,
                    $properties["operationId"],
                    [$method]
                );
                if ($this->routeMatcher->routeMatch($routeSearch, $route, false)) {
                    $apiParameters                  = array_filter(array_merge($properties["parameters"] ?? [], $pathParameters));
                    if ($this->parameterMatches($apiParameters, $route->getUri(), $request)) {
                        return new OpenApiRoute(
                            $route->getUri(),
                            $properties["operationId"],
                            $apiParameters,
                            $properties["requestBody"] ?? null,
                            $properties["responses"] ?? null,
                        );
                    }
                }
            }
        }
        return null;
    }

    private function parameterMatches(array $properties, string $path, HttpServerRequestInterface $request) : bool {
        foreach ($properties as $property) {
            if ($property["required"] ?? false) {
                $propertyIn                         = $property["in"] ?? "-";
                $propertyName                       = $property["name"] ?? "-";
                $propertyValue                      = null;
                switch ($propertyIn) {
                    case "path":
                        $propertyValue              = $request->getPathParam($path, $propertyName);
                        break;
                    case "query":
                        $propertyValue              = $request->getQueryParam($propertyName);
                        break;
                }
                if (!$propertyValue) {
                    return false;
                }
            }
        }
        return true;
    }
}
