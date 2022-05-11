<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;

class OpenApiRouteValidator implements OpenApiRouteValidatorInterface {
    private LoggerInterface $logger;
    private OpenApiYamlValidatorInterface $validator;
    private OpenApiYamlReader $reader;

    public function __construct(LoggerInterface $logger) {
        $this->logger                               = $logger;
        $this->reader                               = new OpenApiYamlReader($logger);
        $this->validator                            = new OpenApiYamlValidator($logger);
    }

    /**
     * @param string $yamlFileName
     * @param string $uri
     * @param string $method
     * @param HttpServerRequestInterface $request
     */
    public function validateRoute(string $yamlFileName, string $uri, string $method, HttpServerRequestInterface $request) {
        //
        $yaml                                       = $this->reader->load($yamlFileName);
        //
        // validate uri parameter
        //
        $queryParams                                = $yaml->getParameterProperties($uri, $method, "path");
        $this->validator->validateSchemas($request->getPathParams($uri), $queryParams);
        //
        // validate query parameter
        //
        if ($params = $yaml->getParameterProperties($uri, $method, "query")) {
            $this->validator->validateSchemas($request->getQueryParams(), $params);
        }
        //
        // validate cookie parameter
        //
        if ($params = $yaml->getParameterProperties($uri, $method, "cookies")) {
            $this->validator->validateSchemas($request->getCookieParams(), $params);
        }
        //
        // validate requestBody
        //
        $requestContentType                         = $request->getHeaderLine("Content-Type");
        $requestBody                                = $this->getRequestBodyEncoded($requestContentType, $request->getBody()->getContents());
        $requestParams                              = $yaml->getRequestBodyProperties($uri, $method, $requestContentType);
        if ($requestParams) {
            $this->validator->validateSchema($requestBody, $requestParams);
        } else {
            if ($requestBody) {
                throw new RuntimeException("schema $uri:$method/requestBody/content/$requestContentType not found");
            }
        }
    }

    /**
     * @param string $contentType
     * @param $content
     * @return mixed|null
     */
    private function getRequestBodyEncoded(string $contentType, $content) {
        if (strlen($content)) {
            if (preg_match("#(application/json)|(application/vnd.+\+json)#", $contentType, $matches)) {
                $contentEncoded                     = json_decode($content);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $contentEncoded;
                }
                throw new InvalidArgumentException("requestBody content could not be encoded as $contentType");
            }
        }
        return null;
    }
}