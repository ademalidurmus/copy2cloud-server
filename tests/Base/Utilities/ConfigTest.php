<?php

namespace Copy2Cloud\Tests\Base\Utilities;

use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Utilities\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    /**
     * @return void
     */
    public function testInit()
    {
        $config = Config::init(__DIR__ . '/../../.mock/mock_config.ini');
        $this->assertObjectHasAttribute('general', $config);
    }

    /**
     * @depends testInit
     * @return void
     */
    public function testGetGivenSection()
    {
        $configSection = Config::get('unittest');
        $this->assertObjectHasAttribute('test_key', $configSection);
        $this->assertTrue($configSection->test_key);
        $this->assertIsNotBool($configSection->test_another_key);
        $this->assertEquals('true', $configSection->test_another_key);
        $this->assertIsArray($configSection->test_array);
    }

    /**
     * @depends testInit
     * @return void
     */
    public function testGetUnknownSection()
    {
        $configSection = Config::get('unknown_section');
        $this->assertObjectHasAttribute('general', $configSection);
        $this->assertObjectHasAttribute('unittest', $configSection);
    }
}
