<?php

namespace Copy2Cloud\Tests\Base;

use Copy2Cloud\Base\Config;
use Copy2Cloud\Base\Container;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Ramsey\Uuid\Uuid;

final class ContainerTest extends TestCase
{
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
        $this->assertInstanceOf('Copy2Cloud\Base\Config', $config);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testGetLog()
    {
        $config = Container::getLog();
        $this->assertInstanceOf('Copy2Cloud\Base\Log', $config);
    }
}
