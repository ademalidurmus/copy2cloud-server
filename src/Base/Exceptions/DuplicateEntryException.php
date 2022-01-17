<?php

namespace Copy2Cloud\Base\Exceptions;

use Exception;
use Copy2Cloud\Base\Constants\HttpStatusCodes;

class DuplicateEntryException extends DefaultException
{
    public function __construct($message, $identifier = 0, $code = HttpStatusCodes::CONFLICT, Exception $previous = null)
    {
        parent::__construct($message, $identifier, $code, $previous);
    }
}
