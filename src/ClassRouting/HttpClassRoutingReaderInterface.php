<?php
namespace Terrazza\Component\HttpRouting\ClassRouting;

interface HttpClassRoutingReaderInterface {
    /**
     * @param string $classRoutingFileName
     * @return HttpClassRoutingReaderInterface
     */
    public function load(string $classRoutingFileName) : HttpClassRoutingReaderInterface;
    /**
     * @return HttpClassRoute[]
     */
    public function getPaths() : array;
}