<?php

declare(strict_types=1);

namespace Copy2Cloud\Base\Utilities;

/**
 * @property array $createFields
 * @property array $readFields
 * @property array $updateFields
 * @property array $searchFields
 * @property array $allowedArguments
 */
class PropertyAccessor
{
    private mixed $_defaultValue;

    /**
     * @param mixed|null $defaultValue
     */
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

    /**
     * Method to getting allowed arguments
     *
     * @return array
     */
    public function getAllowedArguments(): array
    {
        return $this->allowedArguments ?? [];
    }

    /**
     * Method to bind an argument to allowed arguments
     *
     * @param string $arg
     * @return $this
     */
    public function withAllowedArgument(string $arg): self
    {
        return $this->_with('allowedArguments', $arg);
    }

    /**
     * Method to add some arguments to allowed arguments
     *
     * @param array $args
     * @return $this
     */
    public function withAllowedArguments(array $args): self
    {
        foreach ($args as $arg) {
            $this->withAllowedArgument((string)$arg);
        }
        return $this;
    }

    /**
     * Method to exclude some arguments from allowed arguments
     *
     * @param array $args
     * @return $this
     */
    public function withoutAllowedArguments(array $args): self
    {
        foreach ($args as $arg) {
            $this->withoutAllowedArgument((string)$arg);
        }
        return $this;
    }

    /**
     * Method to exclude given argument from allowed arguments
     *
     * @param string $arg
     * @return $this
     */
    public function withoutAllowedArgument(string $arg): self
    {
        return $this->_without('allowedArguments', $arg);
    }

    /**
     * Method to getting read fields
     *
     * @return array
     */
    public function getReadFields(): array
    {
        return $this->readFields;
    }

    /**
     * Method to bind some fields to read fields
     *
     * @param array $fields
     * @return $this
     */
    public function withReadFields(array $fields): self
    {
        foreach ($fields as $field) {
            $this->withReadField((string)$field);
        }
        return $this;
    }

    /**
     * Method to bind items to read fields
     *
     * @param string $field
     * @return $this
     */
    public function withReadField(string $field): self
    {
        return $this->_with('readFields', $field);
    }

    /**
     * Method to exclude some fields from read fields
     *
     * @param array $fields
     * @return $this
     */
    public function withoutReadFields(array $fields): self
    {
        foreach ($fields as $field) {
            $this->withoutReadField((string)$field);
        }
        return $this;
    }

    /**
     * Method to exclude given field from read fields
     *
     * @param string $field
     * @return $this
     */
    public function withoutReadField(string $field): self
    {
        return $this->_without('readFields', $field);
    }

    /**
     * Method to getting update fields
     *
     * @return array
     */
    public function getUpdateFields(): array
    {
        return $this->updateFields;
    }

    /**
     * Method to bind some fields to update fields
     *
     * @param array $fields
     * @return $this
     */
    public function withUpdateFields(array $fields): self
    {
        foreach ($fields as $field) {
            $this->withUpdateField((string)$field);
        }
        return $this;
    }

    /**
     * Method to bind items to update fields
     *
     * @param string $field
     * @return $this
     */
    public function withUpdateField(string $field): self
    {
        return $this->_with('updateFields', $field);
    }

    /**
     * Method to exclude some fields from update fields
     *
     * @param array $fields
     * @return $this
     */
    public function withoutUpdateFields(array $fields): self
    {
        foreach ($fields as $field) {
            $this->withoutUpdateField((string)$field);
        }
        return $this;
    }

    /**
     * Method to exclude given field from update fields
     *
     * @param string $field
     * @return $this
     */
    public function withoutUpdateField(string $field): self
    {
        return $this->_without('updateFields', $field);
    }

    /**
     * Method to getting search fields
     *
     * @return array
     */
    public function getSearchFields(): array
    {
        return $this->searchFields;
    }

    /**
     * Method to bind some fields to search fields
     *
     * @param array $fields
     * @return $this
     */
    public function withSearchFields(array $fields): self
    {
        foreach ($fields as $field) {
            $this->withSearchField((string)$field);
        }
        return $this;
    }

    /**
     * Method to bind search field to search fields
     *
     * @param string $field
     * @return $this
     */
    public function withSearchField(string $field): self
    {
        return $this->_with('searchFields', $field);
    }

    /**
     * Method to exclude some fields from search fields
     *
     * @param array $fields
     * @return $this
     */
    public function withoutSearchFields(array $fields): self
    {
        foreach ($fields as $field) {
            $this->withoutSearchField($field);
        }
        return $this;
    }

    /**
     * Method to exclude given field from search fields
     *
     * @param string $field
     * @return $this
     */
    public function withoutSearchField(string $field): self
    {
        return $this->_without('searchFields', $field);
    }

    /**
     * Method to bind a field/arg to given type's fields/args
     *
     * @param string $variable
     * @param string $field
     * @return $this
     */
    private function _with(string $variable, string $field): self
    {
        $this->{$variable} = array_values(array_unique(array_merge($this->{$variable} ?? [], [$field])));
        return $this;
    }

    /**
     * Method to exclude a field/arg from given type's fields/args
     *
     * @param string $variable
     * @param string $field
     * @return $this
     */
    private function _without(string $variable, string $field): self
    {
        foreach ($this->{$variable} ?? [] as $key => $value) {
            if ($value === $field) {
                unset($this->{$variable}[$key]);
                break;
            }
        }
        $this->{$variable} = array_values($this->{$variable} ?? []);
        return $this;
    }
}
