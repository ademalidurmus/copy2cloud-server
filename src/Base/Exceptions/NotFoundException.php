<?php

namespace Copy2Cloud\Base\Exceptions;

use Exception;
use Copy2Cloud\Base\Constants\HttpStatusCodes;

class NotFoundException extends DefaultException
{
    public function __construct($message, $identifier = 0, $code = HttpStatusCodes::NOT_FOUND, Exception $previous = null)
    {
        parent::__construct($message, $identifier, $code, $previous);
    }
}
