<?php

namespace Copy2Cloud\Base;

use Copy2Cloud\Base\Constants\ErrorCodes;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Exceptions\StoreRedisException;
use Predis\Client;
use Ramsey\Uuid\Uuid;
use Respect\Validation\Validator as v;
use Throwable;

class Container
{
    const RESOURCE_CONFIG = 'config';
    const RESOURCE_REDIS = 'redis';
    const RESOURCE_LOG = 'log';
    const RESOURCE_TRANSACTION_ID = 'transaction_id';

    protected static array $resource = [];

    /**
     * Method to initialize container to dependency injection
     *
     * @param Config $config
     */
    public static function init(Config $config)
    {
        self::set(Container::RESOURCE_CONFIG, $config);
    }

    /**
     * Method to add resource
     *
     * @param string|int $name
     * @param mixed $value
     * @return false|mixed
     */
    public static function set(string|int $name, mixed $value): mixed
    {
        if (v::notEmpty()->validate($name) && !v::nullType()->validate($value)) {
            return self::$resource[$name] = $value;
        }

        return false;
    }

    /**
     * Method to clean resource
     *
     * @param string|int $name
     * @return bool
     */
    public static function clean(string|int $name): bool
    {
        unset(self::$resource[$name]);

        return true;
    }

    /**
     * @param string $name
     * @return null|string|mixed|Client|Log
     * @throws MaintenanceModeException
     */
    public static function get(string $name): mixed
    {
        if (v::key($name)->validate(self::$resource)) {
            return self::$resource[$name];
        }

        switch ($name) {
            case self::RESOURCE_REDIS:
                return self::set(self::RESOURCE_REDIS, self::_initRedis());
            case self::RESOURCE_LOG:
                return self::set(self::RESOURCE_LOG, self::_initLog());
            case self::RESOURCE_TRANSACTION_ID:
                $transactionId = Uuid::uuid1()->toString();
                return self::set(self::RESOURCE_TRANSACTION_ID, $transactionId);
        }

        return null;
    }

    /**
     * Method to getting redis client
     *
     * @return Client
     * @throws MaintenanceModeException
     */
    private static function _initRedis(): Client
    {
        try {
            $redisConfig = self::getConfig()->redis;
            if ($redisConfig) {
                $parameters = [
                    'scheme' => $redisConfig['scheme'] ?? 'tcp',
                    'host' => $redisConfig['host'] ?? '127.0.0.1',
                    'port' => $redisConfig['port'] ?? 6379,
                ];

                if (v::key('uri', v::notEmpty())->validate($redisConfig)) {
                    $parameters = $redisConfig['uri'];
                }

                $client = new Client($parameters);

                if (v::key('auth', v::notEmpty())->validate($redisConfig)) {
                    $client->auth($redisConfig['auth']);
                }

                return $client;
            }
            throw new StoreRedisException('Missing redis configurations!');
        } catch (Throwable $th) {
            Container::getLog()->alert('Redis connection failed!', ['body' => ['message' => $th->getMessage()]]);
            throw new MaintenanceModeException(
                'This server is in maintenance mode. Refresh this page in some minutes.',
                ErrorCodes::MAINTENANCE_MODE
            );
        }
    }

    /**
     * Method to getting config
     *
     * @return Config
     * @throws MaintenanceModeException
     */
    public static function getConfig(): Config
    {
        return self::get(self::RESOURCE_CONFIG);
    }

    /**
     * Method to getting log
     *
     * @return Log
     * @throws MaintenanceModeException
     */
    public static function getLog(): Log
    {
        return self::get(self::RESOURCE_LOG);
    }

    /**
     * Method to initialize log
     *
     * @return Log
     */
    private static function _initLog(): Log
    {
        return new Log();
    }

    /**
     * Method to getting redis client
     *
     * @return Client
     * @throws MaintenanceModeException
     */
    public static function getRedis(): Client
    {
        return self::get(self::RESOURCE_REDIS);
    }

    /**
     * Method to getting transaction id
     *
     * @throws MaintenanceModeException
     */
    public static function getTransactionId(): string
    {
        return self::get(self::RESOURCE_TRANSACTION_ID);
    }
}
