<?php
namespace Terrazza\Component\HttpRouting\ClassRouting;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\Routing\IRouteMatcher;
use Terrazza\Component\Routing\Route;
use Terrazza\Component\Routing\RouteMatcher;
use Terrazza\Component\Routing\RouteSearch;

class ClassRouter {
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

    public function getRoute(HttpServerRequestInterface $request): ?Route {
        $routeSearch                                = new RouteSearch(
            $request->getUri()->getPath(),
            $request->getMethod(),
        );
        //
        return $this->routeMatcher->getRoute(
            $routeSearch,
            $this->getRoutes());
    }
}