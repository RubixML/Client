<?php

include __DIR__ . '../../../vendor/autoload.php';

use Rubix\Client\RESTClient;
use Rubix\Client\HTTP\Middleware\BasicAuthenticator;
use Rubix\Client\HTTP\Middleware\BackoffAndRetry;
use Rubix\ML\Datasets\Generators\Blob;
use Rubix\ML\Datasets\Generators\Agglomerate;

$client = new RESTClient('127.0.0.1', 8000, false, [
    new BasicAuthenticator('user', 'secret'),
    new BackoffAndRetry(),
]);

$generator = new Agglomerate([
    'red' => new Blob([255, 0, 0], 20.0),
    'green' => new Blob([0, 128, 0], 20.0),
    'blue' => new Blob([0, 0, 255], 20.0),
]);

$dataset = $generator->generate(10);

$predictions = $client->predict($dataset);

print_r($predictions);

for ($i = 0; $i < 100000; ++$i) {
    $client->predict($dataset);
}
