<?php
namespace Terrazza\Component\HttpRouting\ClassRouting;
use Psr\Log\LoggerInterface;
use RuntimeException;

class HttpClassRoutingReader implements HttpClassRoutingReaderInterface {
    private LoggerInterface $logger;
    /**
     * @var HttpClassRoute[]
     */
    private array $content=[];
    public function __construct(LoggerInterface $logger) {
        $this->logger                               = $logger;
    }

    /**
     * @param string $classRoutingFileName
     * @return HttpClassRoutingReaderInterface
     */
    public function load(string $classRoutingFileName): HttpClassRoutingReaderInterface {
        if (!file_exists($classRoutingFileName)) {
            throw new RuntimeException("HttpClassRoutingReader file $classRoutingFileName does not exist");
        }
        $this->content                          = require($classRoutingFileName);
        return $this;
    }

    /**
     * @return HttpClassRoute[]
     */
    public function getPaths() : array {
        return $this->content;
    }

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @return HttpClassRouteMethod|null
     */
    private function getPathMethod(string $routePath, string $routeMethod) :?HttpClassRouteMethod {
        $paths                                       = $this->getPaths();
        foreach ($paths as $route) {
            if ($route->getPath() === $routePath) {
                foreach ($route->getMethods() as $method) {
                    if ($method->getMethod() === $routeMethod) {
                        return $method;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param $routePath
     * @param $routeMethod
     * @param $validContentType
     * @return string|null
     */
    public function getRequestBodyClass($routePath, $routeMethod, $validContentType) :?string {
        if ($method = $this->getPathMethod($routePath, $routeMethod)) {
            return $method->getRequestBodyClass();
        }
        return null;
    }
}