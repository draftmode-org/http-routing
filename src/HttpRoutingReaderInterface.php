<?php
namespace Terrazza\Component\HttpRouting;
use InvalidArgumentException;

interface HttpRoutingReaderInterface {
    /**
     * @param string $yamlFileName
     * @return HttpRoutingReaderInterface
     */
    public function load(string $yamlFileName) : HttpRoutingReaderInterface;

    /**
     * @return array
     */
    public function getRoutes() : array;

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @param string $parametersType
     * @return array|null
     */
    public function getParameterParams(string $routePath, string $routeMethod, string $parametersType) :?array;

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @return array|null
     */
    public function getRequestBodyContents(string $routePath, string $routeMethod) :?array;

    /**
     * @param array $content
     * @param string $contentType
     * @return array
     * @throws InvalidArgumentException
     */
    public function getRequestBodyParams(array $content, string $contentType) : array;
}