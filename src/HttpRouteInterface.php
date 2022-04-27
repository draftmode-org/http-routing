<?php
namespace Terrazza\Component\HttpRouting;

interface HttpRouteInterface {
    /**
     * @return string
     */
    public function getRoutePath(): string;
    /**
     * @return string
     */
    public function getRouteMethod(): string;

}