<?php

declare(strict_types=1);

namespace Copy2Cloud\Base\Exceptions;

use Copy2Cloud\Base\Constants\HttpStatusCodes;
use Exception;

class ConfigurationException extends DefaultException
{
    public function __construct($message, $identifier = 0, $code = HttpStatusCodes::INTERNAL_SERVER_ERROR, Exception $previous = null)
    {
        parent::__construct($message, $identifier, $code, $previous);
    }
}
