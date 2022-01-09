# Rubix Client
The PHP client SDK for Rubix ML Server.

## Installation
Install Rubix Client SDK using [Composer](https://getcomposer.org/):

```sh
$ composer require rubix/client
```

## Requirements
- [PHP](https://php.net/manual/en/install.php) 7.4 or above

## Documentation
The latest documentation can be found below.

### Table of Contents
- [Clients](#clients)
	- [REST Client](#rest-client)
- [Client Middleware](#client-middleware)
	- [Backoff and Retry](#backoff-and-retry)
	- [Basic Authenticator](#basic-authenticator-client-side)
	- [Compress Request Body](#compress-request-body)
	- [Shared Token Authenticator](#shared-token-authenticator-client-side)

---
### Clients
Clients allow you to communicate directly with a model server using a friendly object-oriented interface inside your PHP applications. Under the hood, clients handle all the networking communication and content negotiation for you so you can write programs *as if* the model was directly accessible in your applications.

Return the predictions from the model:
```php
public predict(Dataset $dataset) : array
```

```php
use Rubix\Client\RESTClient;

$client = new RESTClient('127.0.0.1', 8080);

// Import a dataset

$predictions = $client->predict($dataset);
```

Calculate the joint probabilities of each sample in a dataset:
```php
public proba(Dataset $dataset) : array
```

Calculate the anomaly scores of each sample in a dataset:
```php
public score(Dataset $dataset) : array
```

### Async Clients
Clients that implement the Async Client interface have asynchronous versions of all the standard client methods. All asynchronous methods return a [Promises/A+](https://promisesaplus.com/) object that resolves to the return value of the response. Promises allow you to perform other work while the request is processing or to execute multiple requests in parallel. Calling the `wait()` method on the promise will block until the promise is resolved and return the value.

```php
public predictAsync(Dataset $dataset) : Promise
```

```php
$promise = $client->predictAsync($dataset);

// Do something else

$predictions = $promise->wait();
```

Return a promise for the probabilities predicted by the model:
```php
public probaAsync(Dataset $dataset) : Promise
```

Return a promise for the anomaly scores predicted by the model:
```php
public scoreAsync(Dataset $dataset) : Promise
```

### REST Client
The REST Client communicates with the [HTTP Server](#http-server) through the JSON REST API it exposes.

Interfaces: [Client](#clients), [AsyncClient](#async-clients)

#### Parameters
| # | Param | Default | Type | Description |
|---|---|---|---|---|
| 1 | host | '127.0.0.1' | string | The IP address or hostname of the server. |
| 2 | port | 8000 | int | The network port that the HTTP server is running on. |
| 3 | secure | false | bool | Should we use an encrypted HTTP channel (HTTPS)? |
| 4 | middlewares | | array | The stack of client middleware to run on each request/response.  |
| 5 | timeout | | float | The number of seconds to wait before giving up on the request. |
| 6 | verify certificate | true | bool | Should we try to verify the server's TLS certificate? |

**Example**

```php
use Rubix\Client\RESTClient;
use Rubix\Client\HTTP\Middleware\BasicAuthenticator;
use Rubix\Client\HTTP\Middleware\CompressRequestBody;
use Rubix\Client\HTTP\Middleware\BackoffAndRetry;
use Rubix\Client\HTTP\Encoders\Gzip;

$client = new RESTClient('127.0.0.1', 443, true, [
	new BasicAuthenticator('user', 'password'),
	new CompressRequestBody(new Gzip(1)),
	new BackoffAndRetry(),
], 0.0, true);
```

### Client Middleware
Similarly to Server middleware, client middlewares are functions that hook into the request/response cycle but from the client end. Some of the server middlewares have accompanying client middleware such as [Basic Authenticator](#basic-authenticator) and [Shared Token Authenticator](#shared-token-authenticator).

### Backoff and Retry
The Backoff and Retry middleware handles Too Many Requests (429) and Service Unavailable (503) responses by retrying the request after waiting for a period of time to avoid overloading the server even further. An acceptable backoff period is gradually achieved by multiplicatively increasing the delay between retries.

#### Parameters
| # | Param | Default | Type | Description |
|---|---|---|---|---|
| 1 | max retries | 3 | int | The maximum number of times to retry the request before giving up. |
| 2 | initial delay | 0.5 | float | The number of seconds to delay between retries before exponential backoff is applied. |

**Example**

```php
use Rubix\Client\HTTP\Middleware\BackoffAndRetry;

$middleware = new BackoffAndRetry(5, 0.5);
```

### Basic Authenticator (Client Side)
Adds the necessary authorization headers to the request using the Basic scheme.

#### Parameters
| # | Param | Default | Type | Description |
|---|---|---|---|---|
| 1 | username | | string | The user's name. |
| 2 | password | | string | The user's password. |

**Example**

```php
use Rubix\Client\HTTP\Middleware\BasicAuthenticator;

$middleware = new BasicAuthenticator('morgan', 'secret');
```

### Compress Request Body
Apply the Gzip compression algorithm to the request body.

#### Parameters
| # | Param | Default | Type | Description |
|---|---|---|---|---|
| 1 | level | 5 | int | The compression level between 0 and 9 with 0 meaning no compression. |
| 2 | threshold | 65535 | int | The minimum size of the request body in bytes in order to be compressed. |

**Example**

```php
use Rubix\Client\HTTP\Middleware\CompressRequestBody;

$middleware = new CompressRequestBody(5, 65535);
```

### Shared Token Authenticator (Client Side)
Adds the necessary authorization headers to the request using the Bearer scheme.

#### Parameters
| # | Param | Default | Type | Description |
|---|---|---|---|---|
| 1 | token | | string | The shared token to authenticate the request. |

**Example**

```php
use Rubix\Client\HTTP\Middleware\SharedtokenAuthenticator;

$middleware = new SharedTokenAuthenticator('secret');
```

## License
The code is licensed [MIT](LICENSE) and the documentation is licensed [CC BY-NC 4.0](https://creativecommons.org/licenses/by-nc/4.0/).
