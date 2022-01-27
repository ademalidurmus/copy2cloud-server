<?php

declare(strict_types=1);

namespace Copy2Cloud\Base\Constants;

class Limitations
{
    const MEGABYTE_SIZE = 1024 * 1024;
    const PAYLOAD_MAX_LENGTH = 2 * self::MEGABYTE_SIZE;
    const RANDOM_KEY_LENGTH = 15;
}