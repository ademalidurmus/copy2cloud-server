<?php

namespace Copy2Cloud\Tests\Base\Utilities;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Utilities\Config;
use Copy2Cloud\Base\Utilities\Container;
use Copy2Cloud\Base\Utilities\Log;
use PHPUnit\Framework\TestCase;
use stdClass;

class LogTest extends TestCase
{
    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testLog()
    {
        $this->_setConfig();

        $log = new Log();
        $this->expectOutputRegex('/\[(?P<date>.*)\]\s(?<channel>.*)\.(?<severity>.*):\s(?<message>.*)\s(\[|\{)(?<context>.*)(\]|\})\s\[(?<extra>.*)\]/');
        $log->info('test', ['data' => ['type' => 'log_test']]);

        $this->assertTrue($log->isDebugEnabled());

        Container::getConfig()->log['level'] = 'info';
        $this->assertFalse($log->isDebugEnabled());
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testMaskJson()
    {
        $json = '{"password":"123456","pass":123456,"test_another_key":"test value"}';
        $maskedJson = Log::mask($json);
        $this->assertEquals('{"password":"****","pass":"****","test_another_key":"test value"}', $maskedJson);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testMaskArray()
    {
        $data = [
            'Authorization' => ['secret_token', 'another secret'],
            'password' => 123456,
            'access_token' => 'test token',
            'test_key' => 'test value',
        ];

        $maskedData = Log::mask($data);

        $this->assertArrayHasKey('password', $maskedData);
        $this->assertEquals('****', $maskedData['password']);
        $this->assertEquals('****', $maskedData['access_token']);
        $this->assertEquals('****', $maskedData['Authorization'][0]);
        $this->assertEquals('test value', $maskedData['test_key']);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testMaskError()
    {
        $this->_setConfig();

        $stdClass = new stdClass();
        $stdClass->test = 'test value';
        $stdClass->password = 123456;

        $this->expectOutputRegex('/\[(?P<date>.*)\]\s(?<channel>.*)\.(ERROR):\s(Data could not mask!)\s(\[|\{)(?<context>.*)(\]|\})\s\[(?<extra>.*)\]/');
        Log::mask($stdClass);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testRequestResponseLog()
    {
        $this->_setConfig();

        $data = Log::requestResponseLog([
            CommonConstants::REQUEST => [
                'test_key' => 'test_val'
            ],
        ]);

        $this->assertArrayHasKey(CommonConstants::REQUEST, $data);

        $this->expectOutputRegex('/\[(?P<date>.*)\]\s(?<channel>.*)\.(INFO):\s(?<message>.*)\s(\[|\{)(?<context>.*)(\]|\})\s\[(?<extra>.*)\]/');
        $data = Log::requestResponseLog([
            CommonConstants::RESPONSE => [
                'test_key' => 'test_val'
            ],
        ], true);
        $this->assertArrayHasKey(CommonConstants::REQUEST, $data);
        $this->assertArrayHasKey(CommonConstants::RESPONSE, $data);
    }

    /**
     * @return void
     */
    private function _setConfig()
    {
        $config = new Config();
        $config->log = [
            'filename' => 'php://output',
            'level' => 'debug',
        ];
        Container::set(
            Container::RESOURCE_CONFIG,
            $config
        );

        $log = new Log();
        Container::set(Container::RESOURCE_LOG, $log);
    }
}