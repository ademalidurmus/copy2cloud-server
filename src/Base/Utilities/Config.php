<?php

namespace Copy2Cloud\Base\Utilities;

use Respect\Validation\Validator as v;

/**
 * @property null $general
 * @property null $redis
 * @property null $log
 */
class Config extends PropertyAccessor
{
    /**
     * @var Config
     */
    protected static Config $config;

    /**
     * Method to initialize config
     *
     * @param string $filename
     * @return Config
     */
    public static function init(string $filename): Config
    {
        $config = parse_ini_file($filename, true, INI_SCANNER_TYPED);

        return self::$config = self::parse($config);
    }

    /**
     * Method to parse config ini
     *
     * @param array $config
     * @param bool $first
     * @return Config|array
     */
    public static function parse(array $config, bool $first = true): Config|array
    {
        $obj = new Config();

        foreach ($config as &$value) {
            if (v::arrayType()->validate($value)) {
                $value = self::parse($value, false);
            }
        }

        if ($first) {
            array_walk(
                $config,
                function ($v, $k) use ($obj) {
                    $obj->{$k} = $v;
                }
            );
            return $obj;
        }

        return $config;
    }

    /**
     * Method to getting config or given config section value
     *
     * @param string|null $section
     * @return object
     */
    public static function get(?string $section = null): object
    {
        if (isset($section) && isset(self::$config->{$section})) {
            return (object)self::$config->{$section};
        }
        return self::$config;
    }
}
