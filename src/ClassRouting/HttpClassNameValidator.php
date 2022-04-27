<?php

namespace Terrazza\Component\HttpRouting\ClassRouting;

use Psr\Log\LoggerInterface;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\HttpValidatorTrait;
use Terrazza\Component\Validator\ClassValidator;
use Terrazza\Component\Validator\ClassValidatorInterface;
use Terrazza\Component\Validator\ValueValidatorSchema;

class HttpClassNameValidator {
    use HttpValidatorTrait;
    private HttpClassRoutingReader $reader;
    private LoggerInterface $logger;
    private ClassValidatorInterface $validator;
    CONST default_request_content_type              = "application/json";

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->reader                               = new HttpClassRoutingReader($logger);
        $this->validator                            = new ClassValidator($logger);
        $this->logger                               = $logger;
    }

    /**
     * @param string $classRoutingFile
     * @param HttpRoute $route
     * @param HttpServerRequestInterface $request
     */
    public function validate(string $classRoutingFile, HttpRoute $route, HttpServerRequestInterface $request) : void {
        $routePath                                  = $route->getRoutePath();
        $routeMethod                                = $route->getRouteMethod();
        //
        $this->reader->load($classRoutingFile);
        //
        $requestContentType                         = $request->getHeaderLine("content-type");
        if ($requestBodySchema = $this->getRequestBodySchema($routePath, $routeMethod, $requestContentType)) {
            $this->logger->debug("requestBodySchema for $routePath:$routeMethod found");
            $requestBody                            = $this->getRequestBodyContentEncoded($requestContentType, $request->getBody()->getContents());
            $this->validator->validateSchema($requestBody, $requestBodySchema);
            $this->logger->debug("requestBodySchema for $routePath:$routeMethod isValid");
        } else {
            $this->logger->debug("no requestBodySchema for $routePath:$routeMethod found");
        }
    }

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @param string|null $validContentType
     * @return ValueValidatorSchema|null
     */
    private function getRequestBodySchema(string $routePath, string $routeMethod, string $validContentType=null) :?ValueValidatorSchema {
        $validContentType                           = $validContentType ?? self::default_request_content_type;
        if ($requestBodyClass = $this->reader->getRequestBodyClass($routePath, $routeMethod, $validContentType)) {
            $this->logger->debug("requestBodyClass for $routePath:$routeMethod found");
            $requestBodySchemas                     = $this->validator->getClassSchema($requestBodyClass);
            $schema                                 = (new ValueValidatorSchema("requestBody"))
                ->setType("object")
                ->setOptional(false);
            $schema->setChildSchemas($requestBodySchemas);
            return $schema;
        } else {
            $this->logger->debug("no requestBodyClass for $routePath:$routeMethod found");
        }
        return null;
    }
}