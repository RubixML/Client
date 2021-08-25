<?php

namespace Rubix\Client\HTTP\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use Rubix\Client\Exceptions\InvalidArgumentException;

use function in_array;
use function usleep;

/**
 * Backoff and Retry
 *
 * The Backoff and Retry middleware handles Too Many Requests (429) and Service Unavailable (503)
 * responses by retrying the request after waiting for a period of time to avoid overloading the
 * server even further. An acceptable backoff period is gradually achieved by multiplicatively
 * increasing the delay between retries.
 *
 * @category    Machine Learning
 * @package     Rubix/Server
 * @author      Andrew DalPino
 */
class BackoffAndRetry implements Middleware
{
    /**
     * Retry the request if the response code is one of the following.
     *
     * @var list<int>
     */
    protected const RETRY_CODES = [
        429, 503,
    ];

    /**
     * The maximum number of times to retry the request before giving up.
     *
     * @var int
     */
    protected int $maxRetries;

    /**
     * The number of seconds to delay between retries before exponential backoff is applied.
     *
     * @var float
     */
    protected float $initialDelay;

    /**
     * @param int $maxRetries
     * @param float $initialDelay
     * @throws \Rubix\Client\Exceptions\InvalidArgumentException
     */
    public function __construct(int $maxRetries = 3, float $initialDelay = 0.5)
    {
        if ($maxRetries < 0) {
            throw new InvalidArgumentException('Max retries must be'
                . " greater than 0, $maxRetries given.");
        }

        if ($initialDelay < 0.0) {
            throw new InvalidArgumentException('Initial delay must be'
                . " greater than 0, $initialDelay given.");
        }

        $this->maxRetries = $maxRetries;
        $this->initialDelay = $initialDelay;
    }

    /**
     * Try the request.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @param callable $handler
     * @param mixed[] $options
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function tryRequest(RequestInterface $request, callable $handler, array $options) : PromiseInterface
    {
        $retry = function (ResponseInterface $response) use ($request, $handler, $options) : PromiseInterface {
            if (in_array($response->getStatusCode(), self::RETRY_CODES)) {
                if ($options['tries_left']) {
                    usleep((int) ($options['delay'] * 1e6));

                    --$options['tries_left'];
                    $options['delay'] *= 2.0;

                    return $this->tryRequest($request, $handler, $options);
                }
            }

            return new FulfilledPromise($response);
        };

        return $handler($request, $options)->then($retry);
    }

    /**
     * Return the higher-order function.
     *
     * @return callable
     */
    public function __invoke() : callable
    {
        return function (callable $handler) : callable {
            return function (RequestInterface $request, array $options) use ($handler) : PromiseInterface {
                $options['tries_left'] = 1 + $this->maxRetries;
                $options['delay'] = $this->initialDelay;

                return $this->tryRequest($request, $handler, $options);
            };
        };
    }
}
