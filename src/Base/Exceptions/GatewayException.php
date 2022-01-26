<?php

declare(strict_types=1);

namespace Copy2Cloud\Base\Exceptions;

use Copy2Cloud\Base\Constants\HttpStatusCodes;
use Exception;

class GatewayException extends DefaultException
{
    public function __construct($message, $identifier = 0, $code = HttpStatusCodes::BAD_GATEWAY, Exception $previous = null)
    {
        parent::__construct($message, $identifier, $code, $previous);
    }
}
