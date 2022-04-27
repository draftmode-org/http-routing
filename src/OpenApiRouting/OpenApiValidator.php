<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;

use Psr\Log\LoggerInterface;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\HttpRouting\HttpRoute;
use Terrazza\Component\HttpRouting\HttpValidatorTrait;
use Terrazza\Component\Validator\ValueValidator;
use Terrazza\Component\Validator\ValueValidatorSchema;

class OpenApiValidator implements OpenApiValidatorInterface {
    use HttpValidatorTrait;
    private OpenApiYamlReader $yamlReader;
    private ValueValidator $validator;
    private LoggerInterface $logger;
    CONST default_request_content_type              = "application/json";

    public function __construct(LoggerInterface $logger) {
        $this->yamlReader                           = new OpenApiYamlReader($logger);
        $this->validator                            = new ValueValidator();
        $this->logger                               = $logger;
    }

    /**
     * @param string $yamlFileName
     * @param HttpRoute $route
     * @param HttpServerRequestInterface $request
     */
    public function validate(string $yamlFileName, HttpRoute $route, HttpServerRequestInterface $request) : void {
        $routePath                                  = $route->getRoutePath();
        $routeMethod                                = $route->getRouteMethod();
        //
        $this->yamlReader                           = $this->yamlReader->load($yamlFileName);
        //
        if ($querySchema = $this->getParameterSchemas($routePath, $routeMethod, "query")) {
            $this->logger->debug("requestParameters for $routePath:$routeMethod/query found");
            $this->validator->validateSchemas($request->getQueryParams(), $querySchema, "queryParam");
            $this->logger->debug("requestParameters for $routePath:$routeMethod/query isValid");
        } else {
            $this->logger->debug("requestParameters for $routePath:$routeMethod/query found");
        }

        if ($pathSchema = $this->getParameterSchemas($routePath, $routeMethod, "path")) {
            $this->logger->debug("requestParameters for $routePath:$routeMethod/path found");
            $this->validator->validateSchemas($request->getPathParams($routePath), $pathSchema, "pathParam");
            $this->logger->debug("requestParameters for $routePath:$routeMethod/path isValid");
        } else {
            $this->logger->debug("no requestParameters for $routePath:$routeMethod/path found");
        }

        $requestContentType                         = $request->getHeaderLine("content-type");
        if ($requestBodySchema = $this->getRequestBodySchema($routePath, $routeMethod, $requestContentType)) {
            $this->logger->debug("requestBodySchema for $routePath:$routeMethod/$requestContentType found");
            $requestBody                            = $this->getRequestBodyContentEncoded($requestContentType, $request->getBody()->getContents());
            $this->validator->validateSchema($requestBody, $requestBodySchema);
            $this->logger->debug("requestBodySchema for $routePath:$routeMethod/$requestContentType isValid");
        } else {
            $this->logger->debug("no requestBodySchema for $routePath:$routeMethod/$requestContentType found");
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
        if ($requestBodyProperties = $this->yamlReader->getRequestBodyProperties($routePath, $routeMethod, $validContentType)) {
            return $this->createValidatorSchema("requestBody", $requestBodyProperties);
        }
        return null;
    }

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @param string $parametersType
     * @return ValueValidatorSchema[]
     */
    private function getParameterSchemas(string $routePath, string $routeMethod, string $parametersType) : array {
        $parameterProperties                        = $this->yamlReader->getParameterProperties($routePath, $routeMethod, $parametersType);
        $schema                                     = [];
        foreach ($parameterProperties as $parameterName => $properties) {
            $schema[$parameterName]                 = $this->createValidatorSchema($parameterName, $properties);
        }
        return $schema;
    }

    /**
     * @param string $parameterName
     * @param array $properties
     * @return ValueValidatorSchema
     */
    private function createValidatorSchema(string $parameterName, array $properties) : ValueValidatorSchema {
        $schema                                     = (new ValueValidatorSchema($parameterName));
        if (array_key_exists("required", $properties)) {
            $schema->setOptional(!$properties["required"]);
        }
        $schema
            ->setType($properties["type"])
            ->setPatterns($properties["patterns"] ?? null)
            ->setFormat($properties["format"] ?? null)
            ->setMinLength($properties["minLength"] ?? null)
            ->setMaxLength($properties["maxLength"] ?? null)
            ->setMinItems($properties["minItems"] ?? null)
            ->setMaxItems($properties["maxItems"] ?? null);
        if (array_key_exists("properties", $properties)) {
            $childSchema                            = [];
            foreach ($properties["properties"] as $childName => $childProperties) {
                $childSchema[$childName]            = $this->createValidatorSchema($childName, $childProperties);
            }
            $schema->setChildSchemas($childSchema);
        }
        return $schema;
    }
}