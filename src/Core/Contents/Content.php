<?php

declare(strict_types=1);

namespace Copy2Cloud\Core\Contents;

use Copy2Cloud\Base\Constants\ErrorCodes;
use Copy2Cloud\Base\Constants\Limitations;
use Copy2Cloud\Base\Exceptions\InvalidArgumentException;
use Copy2Cloud\Base\Utilities\PropertyAccessor;
use Copy2Cloud\Base\Utilities\Str;
use Copy2Cloud\Core\Contents\Store\Redis;
use Copy2Cloud\Core\Interfaces\StoreRedisInterface;
use Respect\Validation\Validator as v;

/**
 * @property string|null $key
 */
class Content extends PropertyAccessor
{
    private StoreRedisInterface $store;
    protected array $allowedArguments = [];
    protected array $allFields = [
        'content',
        'acl',
        'attributes',
        'ttl',
        'insert_time',
        'expire_time',
        'secret',
    ];
    protected array $readFields = [];
    protected array $updateFields = [];

    /**
     * @param string|null $key
     * @param StoreRedisInterface|null $store
     */
    public function __construct(?string $key = null, ?StoreRedisInterface $store = null)
    {
        parent::__construct();

//        $this->addRe

        $this->key = $key;

        $this->store = $store ?? new Redis();

        if ($this->key) {
            $this->read();
        }
    }

    public function create(array $data): Content
    {
        foreach ($data as $key => $value) {

        }
        $this->store->create($this);
        return $this;
    }

    /**
     * @return $this
     */
    public function read(): Content
    {
        $this->store->read($this);
        return $this;
    }

    /**
     * @param int|null $length
     * @return string
     */
    public function generateKey(?int $length = null): string
    {
        do {
            if (!$length || $length < 1) {
                $length = Limitations::RANDOM_KEY_LENGTH;
            }

            $key = Str::generateRandomStr($length);
            $isExist = $this->isExists($key);
        } while ($isExist);

        return $key;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function isExists(string $key): bool
    {
        return $this->store->isExists($key);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function checkValue(string $key, mixed $value): mixed
    {
        $allowedArguments = $this->getAllowedArguments();
        if (!v::in($allowedArguments, true)->validate($key)) {
            throw new InvalidArgumentException("'{$key}' not allowed!", ErrorCodes::INVALID_ARGUMENT);
        }

        switch ($key) {
            case 'key':
                break;
        }

        return $value;
    }
}