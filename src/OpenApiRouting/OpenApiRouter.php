<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;
use Psr\Log\LoggerInterface;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\HttpRoutingInterface;
use Terrazza\Component\Routing\IRouteMatcher;
use Terrazza\Component\Routing\Route;
use Terrazza\Component\Routing\RouteMatcher;
use Terrazza\Component\Routing\RouteSearch;

class OpenApiRouter implements HttpRoutingInterface {
    private LoggerInterface $logger;
    private string $routingFileName;
    private OpenApiReaderInterface $reader;
    private IRouteMatcher $routeMatcher;

    public function __construct(string $routingFileName, LoggerInterface $logger) {
        $this->logger                               = $logger;
        $this->routingFileName                      = $routingFileName;
        $this->reader                               = new OpenApiReader($logger);
        $this->routeMatcher                         = new RouteMatcher($logger);
    }

    /**
     * @param HttpServerRequestInterface $request
     * @return HttpRoute|null
     */
    public function getRoute(HttpServerRequestInterface $request) :?HttpRoute {
        //
        $yaml                                       = $this->reader->load($this->routingFileName);
        //
        $routeSearch                                = new RouteSearch(
            $request->getUri()->getPath(),
            $request->getMethod(),
        );
        foreach ($yaml->getPaths() as $uri => $methods) {
            foreach ($methods as $method => $properties) {
                $route                              = new Route(
                    $uri,
                    $method
                );
                if ($this->routeMatcher->routeMatch($routeSearch, $route)) {
                    return new HttpRoute(
                        $uri,
                        $method,
                        $properties["operationId"]
                    );
                }
            }
        }
        return null;
    }
}
