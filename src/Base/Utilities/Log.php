<?php

declare(strict_types=1);

namespace Copy2Cloud\Base\Utilities;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Exceptions\UnexpectedValueException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Respect\Validation\Validator as v;
use Throwable;
use const APP_NAME;

class Log extends Logger
{
    const DEFAULT_FILENAME = '/var/log/copy2cloud.log';
    const DEFAULT_LEVEL = 'ERROR';

    private static array $requestResponse = [];

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @throws MaintenanceModeException
     */
    public function __construct()
    {
        $this->config = Container::getConfig();

        parent::__construct(defined('APP_NAME') ? APP_NAME : 'copy2cloud');

        $streamHandler = new StreamHandler($this->_getStorePath(), $this->_getLevel());

        $this->pushHandler($streamHandler);

        $this->pushProcessor(function ($record) {
            $record['context']['extra']['transaction_id'] = Container::getTransactionId();

            return $record;
        });
    }

    /**
     * Method to prepare log store path
     *
     * @return string
     */
    private function _getStorePath(): string
    {
        return $this->config->log['filename'] ?? self::DEFAULT_FILENAME;
    }

    /**
     * Method to prepare log level
     *
     * @return string
     */
    private function _getLevel(): string
    {
        return $this->config->log['level'] ?? self::DEFAULT_LEVEL;
    }

    /**
     * @return bool
     */
    public function isDebugEnabled(): bool
    {
        return v::key('level', v::equals('debug'))->validate($this->config->log);
    }

    /**
     * @param array $data
     * @param bool $write
     * @return array
     * @throws MaintenanceModeException
     */
    public static function requestResponseLog(array $data, bool $write = false): array
    {
        self::$requestResponse = array_merge(self::$requestResponse, $data);
        if ($write) {
            Container::getLog()->info('', self::mask(self::$requestResponse));
        }

        return self::$requestResponse;
    }

    /**
     * @param mixed $data
     * @return mixed
     * @throws MaintenanceModeException
     */
    public static function mask(mixed $data): mixed
    {
        try {
            $json = $data;
            $returnAsArray = false;
            if (v::arrayType()->validate($data)) {
                $returnAsArray = true;
                $json = Json::encode($data);
            }

            if (!v::stringType()->validate($json)) {
                throw new UnexpectedValueException('Invalid data type to mask!', ['type' => gettype($json)]);
            }

            $maskedFields = implode('|', CommonConstants::MASKED_FIELDS);
            $json = preg_replace('/"(' . $maskedFields . ')":"(.*?)"/i', '"$1":"****"', $json);
            $json = preg_replace('/"(' . $maskedFields . ')":(\d+)/i', '"$1":"****"', $json);
            $json = preg_replace('/"(' . $maskedFields . ')":\["(.*?)"]/i', '"$1":["****"]', $json);

            if (!$returnAsArray) {
                return $json;
            }

            return Json::decode($json);
        } catch (Throwable $th) {
            Container::getLog()->error('Data could not mask!', [
                'exception' => [
                    'message' => $th->getMessage(),
                    'file' => $th->getFile(),
                    'line' => $th->getLine(),
                ],
            ]);
        }
        return $data;
    }
}
