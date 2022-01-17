<?php

namespace Copy2Cloud\Base;

class Crud
{
    private mixed $_defaultValue;

    public function __construct(mixed $defaultValue = null)
    {
        $this->_defaultValue = $defaultValue;
    }

    /**
     * @param string $name
     * @return null
     */
    public function __get(string $name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }

        return $this->_defaultValue;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function __set(string $name, mixed $value)
    {
        return $this->{$name} = $this->checkValue($name, $value);
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
