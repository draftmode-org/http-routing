<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;
use Psr\Log\LoggerInterface;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\Routing\IRouteMatcher;
use Terrazza\Component\Routing\Route;
use Terrazza\Component\Routing\RouteMatcher;
use Terrazza\Component\Routing\RouteSearch;

class OpenApiRouter implements OpenApiRouterInterface {
    private LoggerInterface $logger;
    private OpenApiYamlReaderInterface $reader;
    private IRouteMatcher $routeMatcher;

    public function __construct(LoggerInterface $logger) {
        $this->logger                               = $logger;
        $this->reader                               = new OpenApiYamlReader($logger);
        $this->routeMatcher                         = new RouteMatcher($logger);
    }

    /**
     * @param string $yamlFileName
     * @param HttpServerRequestInterface $request
     * @return HttpRoute|null
     */
    public function getRoute(string $yamlFileName, HttpServerRequestInterface $request) :?HttpRoute {
        //
        $this->reader->load($yamlFileName);
        //
        $routeSearch                                = new RouteSearch(
            $request->getUri()->getPath(),
            $request->getMethod(),
        );
        foreach ($this->reader->getPaths() as $uri => $methods) {
            foreach ($methods as $method => $properties) {
                $route                              = new Route(
                    $uri,
                    $properties["operationId"],
                    [$method]
                );
                if ($this->routeMatcher->routeMatch($routeSearch, $route, false)) {
                    if ($this->parameterMatches($properties["parameters"] ?? [], $route->getUri(), $request)) {
                        return new HttpRoute(
                            $uri,
                            $method
                        );
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param array $properties
     * @param string $path
     * @param HttpServerRequestInterface $request
     * @return bool
     */
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
