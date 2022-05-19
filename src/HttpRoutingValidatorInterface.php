<?php
namespace Terrazza\Component\HttpRouting;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;

interface HttpRoutingValidatorInterface {
    /**
     * @return HttpRoutingReaderInterface
     */
    public function getReader() : HttpRoutingReaderInterface;

    /**
     * @param HttpRoutingReaderInterface $reader
     * @return HttpRoutingValidatorInterface
     */
    public function setReader(HttpRoutingReaderInterface $reader) : HttpRoutingValidatorInterface;

    /**
     * @param HttpRoute $route
     * @param HttpServerRequestInterface $request
     */
    public function validate(HttpRoute $route, HttpServerRequestInterface $request) : void;
}