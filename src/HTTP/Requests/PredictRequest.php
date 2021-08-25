<?php

namespace Rubix\Client\HTTP\Requests;

use Rubix\ML\Datasets\Dataset;

class PredictRequest extends JSONRequest
{
    /**
     * @param \Rubix\ML\Datasets\Dataset $dataset
     */
    public function __construct(Dataset $dataset)
    {
        parent::__construct('POST', '/model/predictions', [
            'samples' => $dataset->samples(),
        ]);
    }
}
