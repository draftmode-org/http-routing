<?php

namespace Terrazza\Component\HttpRouting\ClassRouting;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\Validator\ValueValidatorInterface;
use Terrazza\Component\Validator\ValueValidatorSchema;

class ClassAnnotationValidator {
    CONST validatePattern                           = '/@validate\s\(+([^\s]+)\)/';
    private LoggerInterface $logger;
    private ValueValidatorInterface $valueValidator;

    public function __construct(ValueValidatorInterface $valueValidator, LoggerInterface $logger) {
        $this->valueValidator                       = $valueValidator;
        $this->logger                               = $logger;
    }

    /**
     * @param ClassRoute $route
     * @param HttpServerRequestInterface $request
     */
    public function validate(ClassRoute $route, HttpServerRequestInterface $request) : void {
        if ($requestBody = $route->getRequestBody()) {
            if ($requestBodySchema = $this->getClassSchemas($requestBody)) {
                $contentType                        = $request->getHeaderLine("content-type");
                $content                            = $this->getRequestBodyContentEncoded("application/json", $request->getBody()->getContents());
                $this->validateContentByProperties($requestBodySchema, $content);
            }
        }
    }

    /**
     * @param class-string<T> $className
     * @return ValueValidatorSchema[]
     */
    private function getClassSchemas(string $className) : array {
        $arrTypeConvert                             = [
            "int"                                   => "integer"
        ];
        try {
            $properties                             = [];
            $rClass                                 = new ReflectionClass($className);
            foreach ($rClass->getProperties() as $rProperty) {
                $rPropertyName                      = $rProperty->getName();
                $inputSchema                        = (new ValueValidatorSchema($rProperty->getName()));
                if (preg_match(self::validatePattern, $rProperty->getDocComment(), $matches)) {
                    if ($rPropertyType = $rProperty->getType()) {
                        $type                       = strtr($rPropertyType->getName(), $arrTypeConvert);
                        $inputSchema
                            ->setType($type)
                            ->setOptional($rPropertyType->allowsNull())
                        ;
                    }
                    $rInputSchema                   = new ReflectionClass($inputSchema);
                    foreach (explode(",",$matches[1]) as $match) {
                        $matchProperty              = explode("=", $match);
                        list($matchKey,$matchValue) = $matchProperty;
                        $setMethod                  = "set".ucfirst($matchKey);
                        if ($rInputProperty = $rInputSchema->getProperty($matchKey)) {
                            $matchValueType         = gettype($matchValue);
                            if ($rInputPropertyType = $rInputProperty->getType()) {
                                $expectedType       = $rInputPropertyType->getName();
                                if ($matchValueType === "string" && $expectedType === "int") {
                                    if (strval(intval($matchValue)) === $matchValue) {
                                        $matchValue = intval($matchValue);
                                        $matchValueType = gettype($matchValue);
                                    }
                                }
                                $expectedType        = strtr($expectedType, $arrTypeConvert);
                                if ($expectedType === $matchValueType) {
                                    call_user_func([$inputSchema, $setMethod], $matchValue);
                                } else {
                                    throw new RuntimeException("type for $matchKey has to be $expectedType, given ".$matchValueType);
                                }
                            }
                        }
                    }
                    $properties[$rPropertyName]     = $inputSchema;
                } else {
                    if ($rPropertyType = $rProperty->getType()) {
                        $inputSchema
                            ->setOptional($rPropertyType->allowsNull())
                        ;
                        if (!$rPropertyType->isBuiltin()) {
                            $rPropertyTypeName      = $rPropertyType->getName();
                            if (class_exists($rPropertyTypeName)) {
                                $inputSchema
                                    ->setType("object")
                                    ->setChildSchemas($this->getClassSchemas($rPropertyTypeName));
                                $properties[$rPropertyName] = $inputSchema;
                            }
                        }
                    }
                }
            }
            return $properties;
        }
        catch (ReflectionException $exception) {
            throw new RuntimeException("getSchema for class $className failure, ".$exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @param array|ValueValidatorSchema[] $properties
     * @param $content
     * @param string|null $parentPropertyName
     * @return void
     */
    private function validateContentByProperties(array $properties, $content, ?string $parentPropertyName=null) : void {
        foreach ($properties as $propertyName => $inputSchema) {
            $fullPropertyName                       = $parentPropertyName ? $parentPropertyName.".".$propertyName : $propertyName;
            $inputExists                            = property_exists($content, $propertyName);
            if (!$inputSchema->isOptional() && !$inputExists) {
                throw new InvalidArgumentException("argument $fullPropertyName required, missing");
            }
            if ($inputExists) {
                $inputValue                         = $content->{$propertyName};
                $this->valueValidator->validateContentType($inputValue, $inputSchema->getType());
                if ($inputSchema->hasChildSchemas() && is_object($inputValue)) {
                    $this->validateContentByProperties($inputSchema->getChildSchemas(), $inputValue, $fullPropertyName);
                }
                try {
                    $this->valueValidator->validateContent($inputValue,$inputSchema);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException("argument $fullPropertyName ".$exception->getMessage());
                }
                unset($content->{$propertyName});
            }
        }
        $unmappedKeys                               = [];
        foreach ($content as $cKey => $cValue) {
            $unmappedKeys[]                         = $parentPropertyName ? $parentPropertyName.".".$cKey : $cKey;
        }
        if (count($unmappedKeys)) {
            $arguments                              = "argument".(count($unmappedKeys) > 1 ? "s" : "");
            throw new InvalidArgumentException("$arguments (".join(", ", $unmappedKeys).") are given but not defined");
        }
    }

    /**
     * @param string $contentType
     * @param string $content
     * @return mixed
     */
    private function getRequestBodyContentEncoded(string $contentType, string $content) {
        switch ($contentType) {
            default:
            case "application/json":
                $contentEncoded                     = json_decode($content);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $contentEncoded;
                }
                throw new InvalidArgumentException("requestBody is not valid $contentType");
        }
    }
}