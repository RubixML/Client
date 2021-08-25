<?php

namespace Rubix\Client\Exceptions;

use InvalidArgumentException as SPLInvalidArgumentException;

class InvalidArgumentException extends SPLInvalidArgumentException implements RubixServerException
{
    //
}
