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
    private ?OpenApiRouteValidatorInterface $validator;
    private IRouteMatcher $routeMatcher;

    public function __construct(LoggerInterface $logger, OpenApiRouteValidatorInterface $validator=null) {
        $this->logger                               = $logger;
        $this->validator                            = $validator;
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
        $yaml                                       = $this->reader->load($yamlFileName);
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
                    //
                    // optional validation
                    //
                    if ($this->validator) {
                        $this->validator->validateRoute($yamlFileName, $uri, $method, $request);
                    }
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
