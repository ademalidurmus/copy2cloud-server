<?php

namespace Copy2Cloud\Base\Exceptions;

use Exception;
use Copy2Cloud\Base\Constants\HttpStatusCodes;

class MaintenanceModeException extends DefaultException
{
    public function __construct($message, $identifier = 0, $code = HttpStatusCodes::SERVICE_UNAVAILABLE, Exception $previous = null)
    {
        parent::__construct($message, $identifier, $code, $previous);
    }
}
