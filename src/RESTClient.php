<?php

namespace Rubix\Client;

use Rubix\ML\Datasets\Dataset;
use Rubix\Client\HTTP\Requests\PredictRequest;
use Rubix\Client\HTTP\Requests\ProbaRequest;
use Rubix\Client\HTTP\Requests\ScoreRequest;
use Rubix\Client\HTTP\Middleware\Middleware;
use Rubix\Client\Exceptions\InvalidArgumentException;
use Rubix\Client\Exceptions\RuntimeException;
use Rubix\Client\Helpers\JSON;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Promise;
use Psr\Http\Message\ResponseInterface;
use Exception;

/**
 * REST Client
 *
 * The REST Client communicates with the HTTP Server through the JSON REST API it exposes.
 *
 * @category    Machine Learning
 * @package     Rubix/Server
 * @author      Andrew DalPino
 */
class RESTClient implements Client, AsyncClient
{
    protected const HEADERS = [
        'User-Agent' => 'Rubix ML REST Client',
        'Accept' => 'application/json',
    ];

    protected const ACCEPTED_CONTENT_TYPES = [
        'application/json',
    ];

    protected const MAX_TCP_PORT = 65535;

    /**
     * The Guzzle HTTP client.
     *
     * @var \GuzzleHttp\Client
     */
    protected \GuzzleHttp\Client $client;

    /**
     * @param string $host
     * @param int $port
     * @param bool $secure
     * @param \Rubix\Client\HTTP\Middleware\Middleware[] $middlewares
     * @param float $timeout
     * @param bool $verifyCertificate
     * @throws \Rubix\Client\Exceptions\InvalidArgumentException
     */
    public function __construct(
        string $host = '127.0.0.1',
        int $port = 8000,
        bool $secure = false,
        array $middlewares = [],
        float $timeout = 0.0,
        bool $verifyCertificate = true
    ) {
        if (empty($host)) {
            throw new InvalidArgumentException('Host address cannot be empty.');
        }

        if ($port < 0 or $port > self::MAX_TCP_PORT) {
            throw new InvalidArgumentException('Port number must be'
                . ' between 0 and ' . self::MAX_TCP_PORT . ", $port given.");
        }

        $stack = HandlerStack::create();

        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof Middleware) {
                throw new InvalidArgumentException('Middleware must'
                    . ' implement the Middleware interface.');
            }

            $stack->push($middleware());
        }

        if ($timeout < 0.0) {
            throw new InvalidArgumentException('Timeout must be'
                . " greater than 0, $timeout given.");
        }

        $baseUri = ($secure ? 'https' : 'http') . "://$host:$port";

        $this->client = new Guzzle([
            'base_uri' => $baseUri,
            'headers' => self::HEADERS,
            'timeout' => $timeout,
            'verify' => $verifyCertificate,
            'handler' => $stack,
        ]);
    }

    /**
     * Make a set of predictions on a dataset.
     *
     * @param \Rubix\ML\Datasets\Dataset $dataset
     * @return (string|int|float)[]
     */
    public function predict(Dataset $dataset) : array
    {
        return $this->predictAsync($dataset)->wait();
    }

    /**
     * Make a set of predictions on a dataset and return a promise.
     *
     * @param \Rubix\ML\Datasets\Dataset $dataset
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function predictAsync(Dataset $dataset) : PromiseInterface
    {
        $request = new PredictRequest($dataset);

        return $this->client->sendAsync($request)
            ->then([$this, 'parseResponseBody'], [$this, 'onError'])
            ->then([$this, 'unpackPayload'])
            ->then(function ($data) {
                return $data['predictions'];
            });
    }

    /**
     * Return the joint probabilities of each sample in a dataset.
     *
     * @param \Rubix\ML\Datasets\Dataset $dataset
     * @return array[]
     */
    public function proba(Dataset $dataset) : array
    {
        return $this->probaAsync($dataset)->wait();
    }

    /**
     * Compute the joint probabilities of the samples in a dataset and return a promise.
     *
     * @param \Rubix\ML\Datasets\Dataset $dataset
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function probaAsync(Dataset $dataset) : PromiseInterface
    {
        $request = new ProbaRequest($dataset);

        return $this->client->sendAsync($request)
            ->then([$this, 'parseResponseBody'], [$this, 'onError'])
            ->then([$this, 'unpackPayload'])
            ->then(function ($data) {
                return $data['probabilities'];
            });
    }

    /**
     * Return the anomaly scores of each sample in a dataset.
     *
     * @param \Rubix\ML\Datasets\Dataset $dataset
     * @return float[]
     */
    public function score(Dataset $dataset) : array
    {
        return $this->scoreAsync($dataset)->wait();
    }

    /**
     * Compute the anomaly scores of the samples in a dataset and return a promise.
     *
     * @param \Rubix\ML\Datasets\Dataset $dataset
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function scoreAsync(Dataset $dataset) : PromiseInterface
    {
        $request = new ScoreRequest($dataset);

        return $this->client->sendAsync($request)
            ->then([$this, 'parseResponseBody'], [$this, 'onError'])
            ->then([$this, 'unpackPayload'])
            ->then(function ($data) {
                return $data['scores'];
            });
    }

    /**
     * Parse the response body and return a promise that resolves to an associative array.
     *
     * @internal
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @throws \Rubix\Client\Exceptions\RuntimeException
     * @return \GuzzleHttp\Promise\Promise
     */
    public function parseResponseBody(ResponseInterface $response) : Promise
    {
        $promise = new Promise(function () use (&$promise, $response) {
            if ($response->hasHeader('Content-Type')) {
                $type = $response->getHeaderLine('Content-Type');

                switch ($type) {
                    case 'application/json':
                        $payload = JSON::decode($response->getBody());

                        break;

                    default:
                        throw new RuntimeException('Unacceptable content'
                            . " type $type in the response body.");
                }

                /** @var \GuzzleHttp\Promise\Promise $promise */
                $promise->resolve($payload);
            }
        });

        return $promise;
    }

    /**
     * Unpack the response body data payload.
     *
     * @param mixed[] $body
     * @return \GuzzleHttp\Promise\Promise
     */
    public function unpackPayload(array $body) : Promise
    {
        $promise = new Promise(function () use (&$promise, $body) {
            if (!isset($body['data'])) {
                throw new RuntimeException('Data payload missing'
                    . ' from the response body.');
            }

            /** @var \GuzzleHttp\Promise\Promise $promise */
            $promise->resolve($body['data']);
        });

        return $promise;
    }

    /**
     * Rethrow a client exception from the server namespace.
     *
     * @internal
     *
     * @param \Exception $exception
     * @throws \Rubix\Client\Exceptions\RuntimeException
     */
    public function onError(Exception $exception) : void
    {
        throw new RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
    }
}
