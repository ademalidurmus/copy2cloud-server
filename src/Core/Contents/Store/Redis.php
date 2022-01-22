<?php

namespace Copy2Cloud\Core\Contents\Store;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Core\Abstracts\Store\Redis as AbstractsRedisStore;
use Predis\Client;

class Redis extends AbstractsRedisStore
{
    /**
     * @param Client|null $connection
     * @throws MaintenanceModeException
     */
    public function __construct(?Client $connection = null)
    {
        parent::__construct($connection);

        $this->setNamespace(CommonConstants::NAMESPACE_CONTENTS);
    }

    public function create(string $key, array $data): bool
    {
        $this->connection->hmset();
    }

    public function read(string $key): array
    {
    }

    public function update(string $key, array $data): bool
    {
    }

    public function delete(string $key): bool
    {
    }
}