<?php
namespace Terrazza\Component\HttpRouting;

use InvalidArgumentException;

trait HttpValidatorTrait {
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