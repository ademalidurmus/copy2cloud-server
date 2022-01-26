<?php

declare(strict_types=1);

namespace Copy2Cloud\Base\Exceptions;

use Copy2Cloud\Base\Constants\HttpStatusCodes;
use Exception;

class DefaultException extends Exception
{
    protected $message;
    protected int $identifier;
    protected $code;

    /**
     * @param $message
     * @param int $identifier
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message, int $identifier = 0, int $code = HttpStatusCodes::INTERNAL_SERVER_ERROR, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->message = $message;
        $this->identifier = $identifier;
        $this->code = $code;
    }

    /**
     * Method to prepare identifier
     *
     * @return int
     */
    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    /**
     *
     * @return string
     */
    public function __toString(): string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}";
    }
}
