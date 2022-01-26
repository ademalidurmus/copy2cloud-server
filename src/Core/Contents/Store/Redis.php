<?php

declare(strict_types=1);

namespace Copy2Cloud\Core\Contents\Store;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Core\Abstracts\StoreRedisAbstract;
use Copy2Cloud\Core\Contents\Content;
use Copy2Cloud\Core\Interfaces\StoreRedisInterface;
use Predis\Client;

class Redis extends StoreRedisAbstract implements StoreRedisInterface
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

    public function create(Content $content): Content
    {
    }

    public function read(Content $content): Content
    {
    }

    public function update(Content $content): Content
    {
    }

    public function delete(Content $content): bool
    {
    }
}