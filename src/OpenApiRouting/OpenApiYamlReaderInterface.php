<?php

namespace Terrazza\Component\HttpRouting\OpenApiRouting;

interface OpenApiYamlReaderInterface {
    /**
     * @param string $yamlFileName
     * @return OpenApiYamlReaderInterface
     */
    public function load(string $yamlFileName) : OpenApiYamlReaderInterface;

    /**
     * @param string $path
     * @param string $method
     * @return array|null
     */
    //public function getPath(string $path, string $method) :?array;

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
     * @return array
     */
    public function getParameterProperties(string $routePath, string $routeMethod, string $parametersType) : array;

    /**
     * @param string $routePath
     * @param string $routeMethod
     * @param string $validContentType
     * @return array|null
     */
    public function getRequestBodyProperties(string $routePath, string $routeMethod, string $validContentType) :?array;
}