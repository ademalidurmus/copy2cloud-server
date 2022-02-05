<?php

declare(strict_types=1);

namespace Copy2Cloud\Core\Contents\Store;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Constants\ErrorCodes;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Exceptions\NotFoundException;
use Copy2Cloud\Base\Exceptions\UnexpectedValueException;
use Copy2Cloud\Base\Utilities\Crypto;
use Copy2Cloud\Base\Utilities\Str;
use Copy2Cloud\Core\Abstracts\StoreRedisAbstract;
use Copy2Cloud\Core\Contents\Content;
use Copy2Cloud\Core\Interfaces\StoreRedisInterface;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Predis\Client;
use Respect\Validation\Validator as v;

class Redis extends StoreRedisAbstract implements StoreRedisInterface
{
    const ENCRYPTED_FIELDS = [
        'content',
        'attributes',
        'acl',
    ];

    /**
     * @param Client|null $connection
     * @throws MaintenanceModeException
     */
    public function __construct(?Client $connection = null)
    {
        parent::__construct($connection);

        $this->setNamespace(CommonConstants::NAMESPACE_CONTENTS);
    }

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function create(Content $content): Content
    {
        $data = [];
        foreach ($content->getReadFields() as $field) {
            if (v::in(['key', 'secret', 'ttl'], true)->validate($field)) {
                continue;
            }
            $data[$field] = $content->{$field};
        }

        $content->secret = $content->secret ?? Str::generateRandomStr(16);

        $data = Crypto::encryptContext(self::ENCRYPTED_FIELDS, $data, $content->secret);
        $hash = $this->getHash($content->key);
        $this->connection->hmset($hash, $data);
        $this->connection->expire($hash, $content->ttl);

        return $content;
    }

    /**
     * @param Content $content
     * @return Content
     * @throws EnvironmentIsBrokenException
     * @throws UnexpectedValueException
     * @throws NotFoundException
     */
    public function read(Content $content): Content
    {
        $hash = $this->getHash($content->key);
        $data = $this->connection->hgetall($hash);

        if (!$data) {
            throw new NotFoundException('Content not found', ErrorCodes::UNKNOWN);
        }

        $data['ttl'] = $this->connection->ttl($hash);

        try {
            $data = Crypto::decryptContext(self::ENCRYPTED_FIELDS, $data, $content->secret);
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            throw new UnexpectedValueException('Invalid secret', ErrorCodes::UNKNOWN);
        }

        foreach ($data as $field => $value) {
            $content->{$field} = $value;
        }

        return $content;
    }

    public function update(Content $content): Content
    {
    }

    public function delete(Content $content): bool
    {
    }

    /**
     * @param Content $content
     * @param bool $updateObject
     * @return bool
     * @throws NotFoundException
     */
    public function decreaseDestroyCount(Content $content, bool $updateObject = true): bool
    {
        $hash = $this->getHash($content->key);
        if ($content->destroy_count > -1) {
            $destroyCount = $this->connection->hincrby($hash, 'destroy_count', -1);
            if ($destroyCount < 0) {
                $this->connection->del($hash);
                throw new NotFoundException('Content not found', ErrorCodes::UNKNOWN);
            }
            if ($updateObject) {
                $content->destroy_count = $destroyCount;
            }
            return true;
        }
        return false;
    }
}