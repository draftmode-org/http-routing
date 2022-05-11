<?php
namespace Terrazza\Component\HttpRouting\OpenApiRouting;

use Psr\Log\LoggerInterface;
use Terrazza\Component\Validator\ObjectValueValidator;
use Terrazza\Component\Validator\ObjectValueSchema;

class OpenApiYamlValidator implements OpenApiYamlValidatorInterface {
    private ObjectValueValidator $validator;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->validator                            = new ObjectValueValidator();
        $this->logger                               = $logger;
    }

    /**
     * @param $content
     * @param array $properties
     */
    public function validateSchemas($content, array $properties) : void {
        if ($contentSchema = $this->buildValueSchemas($properties)) {
            $this->validator->validate($content, $contentSchema);
        }
    }

    /**
     * @param $content
     * @param array $properties
     */
    public function validateSchema($content, array $properties) : void {
        if ($contentSchema = $this->createValidatorSchema("", $properties)) {
            $this->validator->validate($content, $contentSchema);
        }
    }

    /**
     * @param array $properties
     * @return ObjectValueSchema|null
     */
    private function buildValueSchemas(array $properties) :?ObjectValueSchema {
        if (count($properties)) {
            $schema                                 = [];
            foreach ($properties as $parameterName => $parameterProperties) {
                $schema[]                           = $this->createValidatorSchema($parameterName, $parameterProperties);
            }
            return (new ObjectValueSchema("", "object"))->setChildSchemas(...$schema);
        } else {
            return null;
        }
    }

    /**
     * @param string $parameterName
     * @param array $properties
     * @return ObjectValueSchema
     */
    private function createValidatorSchema(string $parameterName, array $properties) : ObjectValueSchema {
        $schema                                     = (new ObjectValueSchema($parameterName, $properties["type"]));
        $schema
            ->setRequired($properties["required"] ?? false)
            ->setNullable($properties["nullable"] ?? false)

            ->setPatterns($properties["patterns"] ?? null)
            ->setFormat($properties["format"] ?? null)
            ->setMinLength($properties["minLength"] ?? null)
            ->setMaxLength($properties["maxLength"] ?? null)
            ->setMinItems($properties["minItems"] ?? null)
            ->setMaxItems($properties["maxItems"] ?? null)
            ->setMinRange($properties["minimum"] ?? null)
            ->setMaxRange($properties["maximum"] ?? null)
            ->setMultipleOf($properties["multipleOf"] ?? null)
            ->setEnum($properties["enum"] ?? null)
        ;
        if (array_key_exists("properties", $properties)) {
            $childSchema                            = [];
            foreach ($properties["properties"] as $childName => $childProperties) {
                $childSchema[]                      = $this->createValidatorSchema($childName, $childProperties);
            }
            $schema->setChildSchemas(...$childSchema);
        } elseif (array_key_exists("oneOf", $properties)) {
            $childSchema                            = [];
            foreach ($properties["oneOf"] as $childName => $childProperties) {
                $childSchema[]                      = $this->createValidatorSchema($childName, $childProperties);
            }
            $schema->setChildSchemas(...$childSchema);
        }
        return $schema;
    }
}