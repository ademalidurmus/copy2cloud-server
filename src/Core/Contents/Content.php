<?php

declare(strict_types=1);

namespace Copy2Cloud\Core\Contents;

use Copy2Cloud\Base\Utilities\PropertyAccessor;
use Copy2Cloud\Core\Contents\Store\Redis;
use Copy2Cloud\Core\Interfaces\StoreRedisInterface;

/**
 * @property string|null $key
 */
class Content extends PropertyAccessor
{
    private StoreRedisInterface $store;

    /**
     * @param string|null $key
     * @param StoreRedisInterface|null $store
     */
    public function __construct(?string $key = null, ?StoreRedisInterface $store = null)
    {
        parent::__construct();

        $this->key = $key;

        $this->store = $store ?? new Redis();

        if ($this->key) {
            $this->read();
        }
    }

    public function read(): array
    {
        $this->store->read($this);
        return [];
    }
}