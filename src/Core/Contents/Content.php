<?php

declare(strict_types=1);

namespace Copy2Cloud\Core\Contents;

use Copy2Cloud\Base\Constants\ErrorCodes;
use Copy2Cloud\Base\Constants\Limitations;
use Copy2Cloud\Base\Enums\StrCharacters;
use Copy2Cloud\Base\Enums\StrTypes;
use Copy2Cloud\Base\Exceptions\DuplicateEntryException;
use Copy2Cloud\Base\Exceptions\InvalidArgumentException;
use Copy2Cloud\Base\Exceptions\NotFoundException;
use Copy2Cloud\Base\Exceptions\UnexpectedValueException;
use Copy2Cloud\Base\Utilities\PropertyAccessor;
use Copy2Cloud\Base\Utilities\Str;
use Copy2Cloud\Core\Contents\Store\Redis;
use Copy2Cloud\Core\Interfaces\StoreRedisInterface;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Respect\Validation\Validator as v;

/**
 * @property string|null $key
 * @property string|null $secret
 * @property string $content
 * @property array $acl
 * @property array $attributes
 * @property int $destroy_count
 * @property int $ttl
 * @property int $insert_time
 * @property int $expire_time
 */
class Content extends PropertyAccessor
{
    protected array $allowedArguments = [];
    protected array $allFields = [
        'key',
        'content',
        'acl',
        'attributes',
        'destroy_count',
        'ttl',
        'insert_time',
        'expire_time',
        'secret',
    ];
    protected array $readFields = [];
    protected array $updateFields = [];

    /**
     * @param string|null $key
     * @param string|null $secret
     * @param StoreRedisInterface $store
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function __construct(
        ?string                     $key = null,
        ?string                     $secret = null,
        private StoreRedisInterface $store = new Redis()
    )
    {
        parent::__construct();

        $this->withReadFields($this->allFields)->withAllowedArguments($this->allFields);

        $this->key = $key;
        $this->secret = $secret;

        if ($this->key) {
            $this->read();
        }
    }

    /**
     * @param array $data
     * @return $this
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     * @throws EnvironmentIsBrokenException
     * @throws DuplicateEntryException
     */
    public function create(array $data): Content
    {
        foreach ($this->allFields as $field) {
            $this->{$field} = $this->checkValue($field, $data[$field] ?? null);
        }

        $this->key = $this->key ?? $this->generateKey();

        if ($this->isExists($this->key)) {
            throw new DuplicateEntryException('Key already exists', ErrorCodes::UNKNOWN);
        }

        $this->destroy_count = $this->destroy_count ?? -1;
        $this->insert_time = time();
        $this->expire_time = $this->expire_time ?? $this->insert_time + Limitations::DEFAULT_TTL;
        $this->ttl = $this->expire_time - $this->insert_time;
        $this->attributes['size'] = strlen($this->content);

        $this->store->create($this);
        return $this;
    }

    /**
     * @return $this
     * @throws EnvironmentIsBrokenException
     * @throws UnexpectedValueException
     * @throws NotFoundException
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

            $key = Str::generateRandomStr($length, StrTypes::mixed, StrCharacters::lowercase);
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
     * @throws UnexpectedValueException
     */
    public function checkValue(string $key, mixed $value): mixed
    {
        $allowedArguments = $this->getAllowedArguments();
        if (!v::in($allowedArguments, true)->validate($key)) {
            throw new InvalidArgumentException("'{$key}' not allowed", ErrorCodes::INVALID_ARGUMENT);
        }

        switch ($key) {
            case 'key':
                if (v::nullType()->validate($value)) {
                    break;
                }
                if (
                    !v::allOf(
                        v::alnum('-_'),
                        v::noWhitespace(),
                        v::length(Limitations::KEY_MIN_LENGTH, Limitations::KEY_MAX_LENGTH)
                    )->validate($value)
                ) {
                    throw new UnexpectedValueException(
                        sprintf(
                            'Key length must be %s to %s alphanumeric characters',
                            Limitations::KEY_MIN_LENGTH,
                            Limitations::KEY_MAX_LENGTH
                        ),
                        ErrorCodes::UNKNOWN
                    );
                }
                break;

            case 'content':
                if (!v::stringType()->validate($value)) {
                    throw new UnexpectedValueException('Content must be a string', ErrorCodes::UNKNOWN);
                }
                $value = trim($value);
                if (!v::length(0, Limitations::CONTENT_MAX_LENGTH)->validate($value)) {
                    throw new UnexpectedValueException('Content length too long', ErrorCodes::UNKNOWN);
                }
                break;

            case 'expire_time':
                if (v::nullType()->validate($value)) {
                    break;
                }
                if ($value < time() || $value > time() + Limitations::MAX_TTL) {
                    throw new UnexpectedValueException('Unexpected expire time', ErrorCodes::UNKNOWN);
                }
                break;

            case 'ttl':
                if ($value < 0 || $value > Limitations::MAX_TTL) {
                    throw new UnexpectedValueException('Unexpected ttl', ErrorCodes::UNKNOWN);
                }
                break;

            case 'destroy_count':
                if ($value < -1 || $value === 0) {
                    throw new UnexpectedValueException('Invalid destroy count', ErrorCodes::UNKNOWN);
                }
                $value = intval($value);
                break;
        }

        return $value;
    }
}