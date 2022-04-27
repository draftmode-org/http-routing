<?php
namespace Terrazza\Component\HttpRouting\ClassRouting;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\Routing\IRouteMatcher;
use Terrazza\Component\Routing\Route;
use Terrazza\Component\Routing\RouteMatcher;
use Terrazza\Component\Routing\RouteSearch;
use Terrazza\Concept\Interfaces\Controller\Http\Payment\HttpPaymentPostRequestBody;

class HttpClassRouter implements HttpClassRouterInterface {
    private LoggerInterface $logger;
    private HttpClassRoutingReaderInterface $reader;
    private IRouteMatcher $routeMatcher;

    public function __construct(LoggerInterface $logger) {
        $this->logger                               = $logger;
        $this->reader                               = new HttpClassRoutingReader($logger);
        $this->routeMatcher                         = new RouteMatcher($logger);
    }

    /**
     * @param string $routingFileName
     * @param HttpServerRequestInterface $request
     * @return HttpRoute|null
     */
    public function getRoute(string $routingFileName, HttpServerRequestInterface $request): ?HttpRoute {
        //
        $this->reader->load($routingFileName);
        //
        $routeSearch                                = new RouteSearch(
            $request->getUri()->getPath(),
            $request->getMethod(),
        );
        foreach ($this->reader->getPaths() as $httpRoute) {
            $uri                                    = $httpRoute->getPath();
            foreach ($httpRoute->getMethods() as $httpMethod) {
                $method                             = $httpMethod->getMethod();
                $route                              = new Route(
                    $uri,
                    $httpRoute->getPath(),
                    [$method]
                );
                if ($this->routeMatcher->routeMatch($routeSearch, $route, false)) {
                    return new HttpRoute(
                        $uri,
                        $method,
                    );
                }
            }
        }
        return null;
    }
}