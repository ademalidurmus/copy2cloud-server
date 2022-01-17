<?php

namespace Copy2Cloud\Base\Exceptions;

use Exception;
use Copy2Cloud\Base\Constants\HttpStatusCodes;

class InvalidArgumentException extends DefaultException
{
    public function __construct($message, $identifier = 0, $code = HttpStatusCodes::NOT_ACCEPTABLE, Exception $previous = null)
    {
        parent::__construct($message, $identifier, $code, $previous);
    }
}
