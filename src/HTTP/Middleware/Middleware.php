<?php

namespace Rubix\Client\HTTP\Middleware;

interface Middleware
{
    /**
     * Return the higher-order function that returns a handler.
     *
     * @return callable
     */
    public function __invoke() : callable;
}
