<?php

declare(strict_types=1);

namespace Copy2Cloud\Core\Contents;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Constants\ErrorCodes;
use Copy2Cloud\Base\Constants\Limitations;
use Copy2Cloud\Base\Enums\StrCharacters;
use Copy2Cloud\Base\Enums\StrTypes;
use Copy2Cloud\Base\Exceptions\AccessDeniedException;
use Copy2Cloud\Base\Exceptions\DuplicateEntryException;
use Copy2Cloud\Base\Exceptions\InvalidArgumentException;
use Copy2Cloud\Base\Exceptions\NotFoundException;
use Copy2Cloud\Base\Exceptions\UnexpectedValueException;
use Copy2Cloud\Base\Utilities\Container;
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
 * @property int $update_time
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
        'update_time',
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
        $this->update_time = time();
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
     * @throws AccessDeniedException
     */
    public function read(): Content
    {
        $this->store->read($this);

        $scope = $this->getClientScope();
        if (!v::in($scope)->validate(CommonConstants::READ)) {
            throw new AccessDeniedException('Cannot read this content', ErrorCodes::UNKNOWN);
        }

        $this->store->decreaseDestroyCount($this);
        return $this;
    }

    public function update(array $data): Content
    {
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
     * @param string|null $clientIp
     * @return array
     */
    public function getClientScope(?string $clientIp = null): array
    {
        if (!$clientIp) {
            $clientIp = Container::getClientIp();
        }

        $ipIsInRange = function (string $range, string $clientIp) {
            if (
                !v::contains('/')->validate($range)
                && !v::contains('-')->validate($range)
            ) {
                $range = "{$range}-{$range}";
            }
            return v::ip(
                $range,
                FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6
            )->validate($clientIp);
        };

        $ipListInvolveGivenIp = function (array $ipList, $clientIp) use ($ipIsInRange) {
            foreach ($ipList as $ip) {
                if ($ipIsInRange($ip, $clientIp)) {
                    return true;
                }
            }
            return false;
        };

        $scope = [];
        $isOwner = v::key('owner')->validate($this->acl) && $ipIsInRange($this->acl['owner'], $clientIp);

        switch (true) {
            case $ipListInvolveGivenIp($this->acl['allow'] ?? [], $clientIp):
            case $isOwner:
                $scope[] = CommonConstants::READ;
                if ($isOwner) {
                    $scope[] = CommonConstants::UPDATE;
                }
                break;

            case $ipListInvolveGivenIp($this->acl['deny'] ?? [], $clientIp):
                $scope = []; // clean scope for restricted client
                break;
        }

        return array_values(array_unique($scope));
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

        $fieldRemover = function (array &$array, array $fields) {
            foreach ($array as $key => $value) {
                if (!v::in($fields, true)->validate($key)) {
                    unset($array[$key]);
                }
            }
        };

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
                if (v::nullType()->validate($value)) {
                    break;
                }
                if ($value < -1 || $value === 0) {
                    throw new UnexpectedValueException('Invalid destroy count', ErrorCodes::UNKNOWN);
                }
                $value = intval($value);
                break;

            case 'acl':
                $validateIp = fn(string $key, mixed $value) => v::key(
                    $key,
                    v::allOf(
                        v::arrayType(),
                        v::unique(),
                        v::each(
                            v::anyOf(
                                v::ip(),
                                v::ip('*', FILTER_FLAG_IPV6)
                            )
                        )
                    ),
                    false
                )->validate($value);

                if (!$validateIp('allow', $value)) {
                    throw new UnexpectedValueException('Invalid acl-allow values', ErrorCodes::UNKNOWN);
                }
                if (!$validateIp('deny', $value)) {
                    throw new UnexpectedValueException('Invalid acl-deny values', ErrorCodes::UNKNOWN);
                }

                if (
                    v::key('deny')->validate($value)
                    && v::in($value['deny'])->validate($value['owner'] ?? '')
                ) {
                    throw new UnexpectedValueException('Cannot add own ip address to deny list', ErrorCodes::UNKNOWN);
                }

                $fieldRemover($value, ['allow', 'deny', 'owner']);
                break;

            case 'attributes':
                $fieldRemover($value, ['size']);
                break;
        }

        return $value;
    }
}