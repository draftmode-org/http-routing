<?php

namespace Terrazza\Component\HttpRouting\ClassRouting;

class HttpClassRoute {
    private string $path;
    /** @var HttpClassRouteMethod[]  */
    private array $methods;

    /**
     * @param string $path
     * @param HttpClassRouteMethod[] $methods
     */
    public function __construct(string $path, HttpClassRouteMethod ...$methods) {
        $this->path                                 = $path;
        $this->methods                              = $methods;
    }

    /**
     * @return string
     */
    public function getPath(): string {
        return $this->path;
    }

    /**
     * @return HttpClassRouteMethod[]
     */
    public function getMethods(): array {
        return $this->methods;
    }
}