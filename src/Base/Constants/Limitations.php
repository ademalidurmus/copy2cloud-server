<?php

declare(strict_types=1);

namespace Copy2Cloud\Base\Constants;

class Limitations
{
    const MEGABYTE_SIZE = 1024 * 1024;
    const PAYLOAD_MAX_LENGTH = 2 * self::MEGABYTE_SIZE;
    const RANDOM_KEY_LENGTH = 15;
    const DAY_SECONDS = 24 * 60 * 60;
    const MAX_TTL_MULTIPLIER_AS_DAY = 31;
    const MAX_TTL = self::MAX_TTL_MULTIPLIER_AS_DAY * self::DAY_SECONDS;
    const DEFAULT_TTL = self::DAY_SECONDS;
}