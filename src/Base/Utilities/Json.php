<?php

namespace Copy2Cloud\Base\Utilities;

class Json
{
    /**
     * @param mixed $data
     * @return false|string
     */
    public static function encode(mixed $data): bool|string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param mixed $data
     * @param bool $array
     * @return mixed
     */
    public static function decode(mixed $data, bool $array = true): mixed
    {
        return json_decode($data, $array);
    }
}
