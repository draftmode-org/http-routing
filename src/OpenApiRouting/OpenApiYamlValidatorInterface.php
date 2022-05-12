<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;

interface OpenApiYamlValidatorInterface {
    /**
     * @param string $schemaName
     * @param $content
     * @param array $properties
     */
    public function validate(string $schemaName, $content, array $properties) : void;
}