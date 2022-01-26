<?php

declare(strict_types=1);

namespace Copy2Cloud\Tests\Core\Abstracts\Store;

use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Core\Abstracts\StoreRedisAbstract;
use PHPUnit\Framework\TestCase;
use Predis\Client;

class MockAbstractStoreRedisStoreAbstract extends StoreRedisAbstract
{
}

class RedisTest extends TestCase
{
    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testPrefix()
    {
        $mockRedis = $this->createMock(Client::class);
        $redisStore = new MockAbstractStoreRedisStoreAbstract($mockRedis);

        $this->assertEquals('C2C', $redisStore->getPrefix());
        $redisStore->setPrefix('C2C_TEST');
        $this->assertEquals('C2C_TEST', $redisStore->getPrefix());
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testNamespace()
    {
        $mockRedis = $this->createMock(Client::class);
        $redisStore = new MockAbstractStoreRedisStoreAbstract($mockRedis);

        $this->assertEquals('', $redisStore->getNamespace());
        $redisStore->setNamespace('test_namespace');
        $this->assertEquals('test_namespace', $redisStore->getNamespace());
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testGetHash()
    {
        $mockRedis = $this->createMock(Client::class);
        $redisStore = new MockAbstractStoreRedisStoreAbstract($mockRedis);

        $hash = $redisStore->getHash('test');
        $this->assertEquals('C2C:test', $hash);

        $hash = $redisStore->setNamespace('test_namespace')->setPrefix('C2C_TEST')->getHash('test');
        $this->assertEquals('C2C_TEST:test_namespace:test', $hash);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testIsExistsForExistingKey()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->addMethods(['exists'])
            ->getMock();

        $mockRedis->expects($this->once())
            ->method('exists')
            ->willReturn(1);

        $redisStore = new MockAbstractStoreRedisStoreAbstract($mockRedis);
        $response = $redisStore->isExists('test');
        $this->assertTrue($response);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testIsExistsForNonExistingKey()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->addMethods(['exists'])
            ->getMock();

        $mockRedis->expects($this->once())
            ->method('exists')
            ->willReturn(0);

        $redisStore = new MockAbstractStoreRedisStoreAbstract($mockRedis);
        $response = $redisStore->isExists('test');
        $this->assertFalse($response);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testIsHashExistsForExistingKey()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->addMethods(['hExists'])
            ->getMock();

        $mockRedis->expects($this->once())
            ->method('hExists')
            ->willReturn(1);

        $redisStore = new MockAbstractStoreRedisStoreAbstract($mockRedis);
        $response = $redisStore->isHashExists('hash', 'field');
        $this->assertTrue($response);
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testIsHashExistsForNonExistingKey()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->addMethods(['hExists'])
            ->getMock();

        $mockRedis->expects($this->once())
            ->method('hExists')
            ->willReturn(0);

        $redisStore = new MockAbstractStoreRedisStoreAbstract($mockRedis);
        $response = $redisStore->isHashExists('hash', 'field');
        $this->assertFalse($response);
    }
}
