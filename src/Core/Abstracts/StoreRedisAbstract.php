<?php

declare(strict_types=1);

namespace Copy2Cloud\Core\Abstracts;

use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Utilities\Container;
use Predis\Client;

abstract class StoreRedisAbstract
{
    protected ?Client $connection;
    protected string $prefix = '';
    protected string $namespace = '';

    /**
     * @throws MaintenanceModeException
     */
    public function __construct(?Client $connection = null)
    {
        $this->connection = $connection ?? Container::getRedis();
        $this->setPrefix('C2C');
    }

    /**
     * @param string $prefix
     * @return $this
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param string $namespace
     * @return $this
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHash(string $key): string
    {
        $hash = sprintf(
            '%s:%s',
            $this->prefix,
            $this->namespace
        );

        $hash = trim($hash, ':');

        $hash = sprintf(
            '%s:%s',
            $hash,
            $key
        );

        return trim($hash, ':');
    }

    /**
     * @param string $key
     * @return bool
     */
    public function isExists(string $key): bool
    {
        return $this->connection->exists($this->getHash($key)) > 0;
    }

    /**
     * @param string $key
     * @param string $field
     * @return bool
     */
    public function isHashExists(string $key, string $field): bool
    {
        return $this->connection->hexists($this->getHash($key), $field) > 0;
    }
}