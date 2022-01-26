<?php

declare(strict_types=1);

namespace Copy2Cloud\Base\Utilities;

class PropertyAccessor
{
    private mixed $_defaultValue;

    public function __construct(mixed $defaultValue = null)
    {
        $this->_defaultValue = $defaultValue;
    }

    /**
     * @param string $name
     * @return null|mixed
     */
    public function __get(string $name): mixed
    {
        return $this->{$name} ?? $this->_defaultValue;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->{$name} = $this->checkValue($name, $value);
    }

    /**
     * Method to check value
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function checkValue(string $key, mixed $value): mixed
    {
        return $value;
    }
}
