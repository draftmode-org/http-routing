<?php

namespace Terrazza\Component\HttpRouting\OpenApiRouting;

interface OpenApiYamlReaderInterface {
    /**
     * @param string $yamlFileName
     * @return OpenApiYamlReaderInterface
     */
    public function load(string $yamlFileName) : OpenApiYamlReaderInterface;

    /**
     * @return array
     */
    public function getPaths() : array;

    /**
     * @param string $ref
     * @return array
     */
    public function getContentByRef(string $ref) : array;

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @param string $parametersType
     * @return array|null
     */
    public function getParameterProperties(string $routePath, string $routeMethod, string $parametersType) :?array;

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
     */
    public function getRequestBodyContentByContentType(array $content, string $contentType) : array;
}