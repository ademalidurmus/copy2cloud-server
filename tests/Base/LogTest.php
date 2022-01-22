<?php

namespace Copy2Cloud\Tests\Base;

use Copy2Cloud\Base\Config;
use Copy2Cloud\Base\Container;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Log;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testLog()
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
}