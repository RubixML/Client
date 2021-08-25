<?php

namespace Rubix\Client\Exceptions;

use RuntimeException as SPLRuntimeException;

class RuntimeException extends SPLRuntimeException implements RubixServerException
{
    //
}
