<?php
namespace Terrazza\Component\HttpRouting\ClassRouting;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\Routing\IRouteMatcher;
use Terrazza\Component\Routing\RouteMatcher;
use Terrazza\Component\Routing\RouteSearch;
use Terrazza\Concept\Interfaces\Controller\Http\Payment\HttpPaymentPostRequestBody;

class ClassRouter implements ClassRouterInterface {
    private string $routingFile;
    private LoggerInterface $logger;
    private IRouteMatcher $routeMatcher;

    public function __construct(string $routingFile, LoggerInterface $logger) {
        $this->routingFile                          = $routingFile;
        $this->logger                               = $logger;
        $this->routeMatcher                         = new RouteMatcher($logger);
    }

    /**
     * @return array
     */
    private function getRoutes() : array {
        if (!file_exists($this->routingFile)) {
            throw new RuntimeException("ClassRouter file ".$this->routingFile." does not exist");
        }
        return require_once($this->routingFile);
    }

    public function getRoute(HttpServerRequestInterface $request): ?ClassRoute {
        $routeSearch                                = new RouteSearch(
            $request->getUri()->getPath(),
            $request->getMethod(),
        );
        //
        if ($route = $this->routeMatcher->getRoute($routeSearch, $this->getRoutes())) {
            return new ClassRoute(
                $route->getUri(),
                HttpPaymentPostRequestBody::class
            );
        }
        return null;
    }
}