<?php

namespace Terrazza\Component\HttpRouting;

use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\Exception\HttpInvalidArgumentException;

class HttpRouter implements HttpRouterInterface {
    private HttpRoutingInterface $httpRouting;
    private ?HttpRoutingValidatorInterface $httpRoutingValidator=null;

    /**
     * @param HttpRoutingInterface $httpRouting
     * @param HttpRoutingValidatorInterface|null $httpRoutingValidator
     */
    public function __construct(HttpRoutingInterface $httpRouting, ?HttpRoutingValidatorInterface $httpRoutingValidator=null)
    {
        $this->httpRouting                          = $httpRouting;
        $this->httpRoutingValidator                 = $httpRoutingValidator;
    }

    /**
     * @param HttpServerRequestInterface $request
     * @return HttpRoute|null
     * @throws HttpInvalidArgumentException
     */
    public function getRoute(HttpServerRequestInterface $request) : ?HttpRoute {
        if ($httpRoute = $this->httpRouting->getRoute($request)) {
            if ($this->httpRoutingValidator) {
                $this->httpRoutingValidator->validate($httpRoute, $request);
            }
            return $httpRoute;
        } else {
            return null;
        }
    }
}