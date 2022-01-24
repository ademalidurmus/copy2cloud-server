<?php

namespace Copy2Cloud\Tests\Base\Utilities;

use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Utilities\Config;
use Copy2Cloud\Base\Utilities\Container;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Ramsey\Uuid\Uuid;

final class ContainerTest extends TestCase
{
    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testInit()
    {
        $config = new Config();
        $config->general = [
            'test' => true,
        ];

        Container::init($config);

        $config = Container::getConfig();
        $this->assertObjectHasAttribute('general', $config);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testSet()
    {
        $data = Container::set('test', 'test value');
        $this->assertEquals('test value', $data);

        $data = Container::get('test');
        $this->assertEquals('test value', $data);
    }

    /**
     * @return void
     */
    public function testSetError()
    {
        $data = Container::set('', 'test value');
        $this->assertFalse($data);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testClean()
    {
        $data = Container::clean('test');
        $this->assertTrue($data);

        $data = Container::get('test');
        $this->assertNull($data);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testGetTransactionId()
    {
        $transactionId = Container::getTransactionId();
        $this->assertTrue(Uuid::isValid($transactionId));
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testGetRedis()
    {
        Container::set(
            Container::RESOURCE_REDIS,
            $this->createMock(Client::class)
        );

        $redis = Container::getRedis();
        $this->assertInstanceOf('Predis\Client', $redis);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testGetConfig()
    {
        Container::set(
            Container::RESOURCE_CONFIG,
            $this->createMock(Config::class)
        );

        $config = Container::getConfig();
        $this->assertInstanceOf('Copy2Cloud\Base\Utilities\Config', $config);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testGetLog()
    {
        $config = Container::getLog();
        $this->assertInstanceOf('Copy2Cloud\Base\Utilities\Log', $config);
    }
}
