<?php

namespace Rubix\Client\HTTP\Middleware;

/**
 * Shared Token Authenticator
 *
 * @category    Machine Learning
 * @package     Rubix/Server
 * @author      Andrew DalPino
 */
class SharedTokenAuthenticator extends BasicAuthenticator
{
    /**
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->credentials = "Bearer $token";
    }
}
