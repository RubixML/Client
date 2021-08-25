<?php

namespace Rubix\Client\Tests;

use Rubix\Client\RESTClient;
use Rubix\Client\Client;
use Rubix\Client\AsyncClient;
use Rubix\Client\HTTP\Middleware\SharedTokenAuthenticator;
use PHPUnit\Framework\TestCase;

/**
 * @group Clients
 * @covers \Rubix\Client\RESTClient
 */
class RESTClientTest extends TestCase
{
    /**
     * @var \Rubix\Client\RESTClient
     */
    protected $client;

    /**
     * @before
     */
    protected function setUp() : void
    {
        $this->client = new RESTClient('127.0.0.1', 8888, false, [
            new SharedTokenAuthenticator('secret'),
        ], 0.0);
    }

    /**
     * @test
     */
    public function build() : void
    {
        $this->assertInstanceOf(RESTClient::class, $this->client);
        $this->assertInstanceOf(Client::class, $this->client);
        $this->assertInstanceOf(AsyncClient::class, $this->client);
    }
}
