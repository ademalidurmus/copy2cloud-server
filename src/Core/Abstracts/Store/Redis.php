<?php

namespace Copy2Cloud\Core\Abstracts\Store;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Container;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Predis\Client;
use Respect\Validation\Validator as v;

abstract class Redis
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
     * @param string $namespace
     * @return $this
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHash(string $key): string
    {
        $hash = sprintf(
            '%s:%s:%s',
            $this->prefix,
            $this->namespace,
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

    /**
     * @param array $data
     * @param string $secret
     * @return array
     * @throws EnvironmentIsBrokenException
     */
    public function encryptContext(array $data, string $secret): array
    {
        if (!v::key('secret', v::trueVal())->validate($data)) {
            return $data;
        }

        switch ($this->namespace) {
            case CommonConstants::NAMESPACE_CONTENTS:
                foreach (['content', 'attributes', 'acl'] as $field) {
                    if (v::key($field)->validate($data)) {
                        $data[$field] = Crypto::encryptWithPassword(serialize($data[$field]), $secret);
                    }
                }
                break;

            default:
                return $data;
        }

        return $data;
    }

    /**
     * @param array $data
     * @param string $secret
     * @return array
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function decryptContext(array $data, string $secret): array
    {
        if (!v::key('secret', v::trueVal())->validate($data)) {
            return $data;
        }

        switch ($this->namespace) {
            case CommonConstants::NAMESPACE_CONTENTS:
                foreach (['content', 'attributes', 'acl'] as $field) {
                    if (v::key($field)->validate($data)) {
                        $data[$field] = unserialize(Crypto::decryptWithPassword($data[$field], $secret));
                    }
                }
                break;

            default:
                return $data;
        }

        return $data;
    }
}