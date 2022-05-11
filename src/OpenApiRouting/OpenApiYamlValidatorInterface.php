<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;

interface OpenApiYamlValidatorInterface {
    /**
     * @param $content
     * @param array $properties
     */
    public function validateSchemas($content, array $properties) : void;

    /**
     * @param $content
     * @param array $properties
     */
    public function validateSchema($content, array $properties) : void;
}