<?php

declare(strict_types=1);

namespace Copy2Cloud\Tests\Core\Contents\Store;

use Copy2Cloud\Base\Constants\Limitations;
use Copy2Cloud\Base\Exceptions\AccessDeniedException;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Exceptions\NotFoundException;
use Copy2Cloud\Base\Exceptions\UnexpectedValueException;
use Copy2Cloud\Core\Contents\Content;
use Copy2Cloud\Core\Contents\Store\Redis;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use function PHPUnit\Framework\at;

class RedisTest extends TestCase
{
    /**
     * @return void
     * @throws EnvironmentIsBrokenException
     * @throws MaintenanceModeException
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCreate()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['hmset', 'expire'])
            ->getMock();

        $mockRedis->expects(at(0))
            ->method('hmset')
            ->willReturn('OK');

        $mockRedis->expects(at(1))
            ->method('expire')
            ->willReturn(1);

        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->key = 'test_key';
        $content->content = 'test content';
        $content->attributes = [
            'size' => 12,
        ];
        $content->acl = [
            'owner' => '127.0.0.1',
        ];
        $content->insert_time = time();
        $content->expire_time = time() + Limitations::DEFAULT_TTL;
        $content->ttl = Limitations::DEFAULT_TTL;
        $content->destroy_count = -1;

        $store = new Redis($mockRedis);
        $create = $store->create($content);

        $this->assertEquals(16, strlen($create->secret));
        $this->assertInstanceOf(Content::class, $create);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws MaintenanceModeException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testRead()
    {
        $time = time();

        $mockRedis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['hgetall', 'ttl'])
            ->getMock();

        $mockRedis->expects(at(0))
            ->method('hgetall')
            ->willReturn(
                [
                    'content' => 'def5020003a8e27e5ea479d4b2f719cfcac1bf5ec450fd937bcffdec1ed7092da1c3e22a759ebc9e556dfaf76b6dac354d1f1980acc8fdabc0e933e7475e2eb8eed74463a7f447fdc1fb5836b00d224defb638cea967b377de2c0ae2e9a3ab8f7526d9016d6a25f8',
                    'acl' => 'def50200db216bd35792900b66e80d8a643dea88ab52d8bac4e69ee8a4e55210e8dda0f26fb57e0ded413c04b31d9c0ce5e6b0fb6c65bb5a6b56d5b57f1259517b167ead26940f31eb387d7cdd29a83f5a87092d48e1aaac5288e71202c0ea9302fee24c56b48338bd1a3013f0123a563097bf513eec',
                    'attributes' => 'def502000bb9906f7735354dc9657ce144c5156fea2b8d6e26241b53432d320581f7b96412380e58e0596d2235e58e1fd28e95ce2a6c859503ba9857b94eabbd2c9af8f1401f28eea97c0618639fb8c18a1900e9467d8d86b060a128cd412188cbd40670acb54b260809',
                    'destroy_count' => 10,
                    'expire_time' => $time + Limitations::DEFAULT_TTL,
                    'update_time' => $time,
                    'insert_time' => $time,
                ]
            );

        $mockRedis->expects(at(1))
            ->method('ttl')
            ->willReturn(86400);

        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->key = 'test_key';
        $content->secret = 'test_secret';

        $store = new Redis($mockRedis);
        $read = $store->read($content);

        $this->assertEquals('test_key', $read->key);
        $this->assertEquals('test content', $read->content);
        $this->assertEquals(['size' => 12], $read->attributes);
        $this->assertEquals(['owner' => '127.0.0.1'], $read->acl);
        $this->assertEquals(10, $read->destroy_count);
        $this->assertEquals($time + Limitations::DEFAULT_TTL, $read->expire_time);
        $this->assertEquals($time, $read->update_time);
        $this->assertEquals($time, $read->insert_time);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws MaintenanceModeException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testReadNotFound()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['hgetall'])
            ->getMock();

        $mockRedis->expects(at(0))
            ->method('hgetall')
            ->willReturn([]);

        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->key = 'test_key';

        $this->expectException(NotFoundException::class);
        $store = new Redis($mockRedis);
        $store->read($content);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws MaintenanceModeException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testReadInvalidSecret()
    {
        $time = time();

        $mockRedis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['hgetall', 'ttl'])
            ->getMock();

        $mockRedis->expects(at(0))
            ->method('hgetall')
            ->willReturn(
                [
                    'content' => 'def5020003a8e27e5ea479d4b2f719cfcac1bf5ec450fd937bcffdec1ed7092da1c3e22a759ebc9e556dfaf76b6dac354d1f1980acc8fdabc0e933e7475e2eb8eed74463a7f447fdc1fb5836b00d224defb638cea967b377de2c0ae2e9a3ab8f7526d9016d6a25f8',
                    'acl' => 'def50200db216bd35792900b66e80d8a643dea88ab52d8bac4e69ee8a4e55210e8dda0f26fb57e0ded413c04b31d9c0ce5e6b0fb6c65bb5a6b56d5b57f1259517b167ead26940f31eb387d7cdd29a83f5a87092d48e1aaac5288e71202c0ea9302fee24c56b48338bd1a3013f0123a563097bf513eec',
                    'attributes' => 'def502000bb9906f7735354dc9657ce144c5156fea2b8d6e26241b53432d320581f7b96412380e58e0596d2235e58e1fd28e95ce2a6c859503ba9857b94eabbd2c9af8f1401f28eea97c0618639fb8c18a1900e9467d8d86b060a128cd412188cbd40670acb54b260809',
                    'destroy_count' => 10,
                    'expire_time' => $time + Limitations::DEFAULT_TTL,
                    'update_time' => $time,
                    'insert_time' => $time,
                ]
            );

        $mockRedis->expects(at(1))
            ->method('ttl')
            ->willReturn(86400);

        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->key = 'test_key';
        $content->secret = 'test_wrong_secret';

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid secret');
        $store = new Redis($mockRedis);
        $store->read($content);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws MaintenanceModeException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testUpdate()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['exists', 'hmset', 'expire'])
            ->getMock();

        $mockRedis->expects(at(0))
            ->method('exists')
            ->willReturn(1);

        $mockRedis->expects(at(1))
            ->method('hmset')
            ->willReturn('OK');

        $mockRedis->expects(at(2))
            ->method('expire')
            ->willReturn(1);

        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->key = 'test_key';
        $content->secret = 'test_secret';
        $content->content = 'test content';
        $content->attributes = [
            'size' => 12,
        ];
        $content->acl = [
            'owner' => '127.0.0.1',
        ];
        $content->ttl = 86400;
        $content->expire_time = time() + Limitations::DEFAULT_TTL;
        $content->update_time = time();
        $content->insert_time = time();
        $content->destroy_count = 10;

        $store = new Redis($mockRedis);
        $update = $store->update($content);
        $this->assertInstanceOf(Content::class, $update);
        $this->assertEquals($update->key, $content->key);
        $this->assertEquals($update->secret, $content->secret);
        $this->assertEquals($update->content, $content->content);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws MaintenanceModeException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testUpdateNotFound()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['exists'])
            ->getMock();

        $mockRedis->expects(at(0))
            ->method('exists')
            ->willReturn(0);

        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->key = 'test_key';
        $content->secret = 'test_secret';
        $content->content = 'test content';
        $content->attributes = [
            'size' => 12,
        ];
        $content->acl = [
            'owner' => '127.0.0.1',
        ];
        $content->ttl = 86400;
        $content->expire_time = time() + Limitations::DEFAULT_TTL;
        $content->update_time = time();
        $content->insert_time = time();
        $content->destroy_count = 10;

        $this->expectException(NotFoundException::class);
        $store = new Redis($mockRedis);
        $store->update($content);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws MaintenanceModeException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testDelete()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['del'])
            ->getMock();

        $mockRedis->expects(at(0))
            ->method('del')
            ->willReturn(1);

        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->key = 'test_key';

        $store = new Redis($mockRedis);
        $delete = $store->delete($content);
        $this->assertTrue($delete);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws MaintenanceModeException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testDeleteError()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['del'])
            ->getMock();

        $mockRedis->expects(at(0))
            ->method('del')
            ->willReturn(0);

        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->key = 'test_key';

        $store = new Redis($mockRedis);
        $delete = $store->delete($content);
        $this->assertFalse($delete);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws MaintenanceModeException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testDecreaseDestroyCount()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['hincrby'])
            ->getMock();

        $mockRedis->expects(at(0))
            ->method('hincrby')
            ->willReturn(9);

        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->key = 'test_key';
        $content->destroy_count = 10;

        $store = new Redis($mockRedis);
        $response = $store->decreaseDestroyCount($content);
        $this->assertTrue($response);
        $this->assertEquals(9, $content->destroy_count);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws MaintenanceModeException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testDecreaseDestroyCountNoObjectUpdate()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['hincrby'])
            ->getMock();

        $mockRedis->expects(at(0))
            ->method('hincrby')
            ->willReturn(9);

        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->key = 'test_key';
        $content->destroy_count = 10;

        $store = new Redis($mockRedis);
        $response = $store->decreaseDestroyCount($content, false);
        $this->assertTrue($response);
        $this->assertEquals(10, $content->destroy_count);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws MaintenanceModeException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testDecreaseDestroyCountForRemainingCountIsZero()
    {
        $mockRedis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['hincrby', 'del'])
            ->getMock();

        $mockRedis->expects(at(0))
            ->method('hincrby')
            ->willReturn(-1);

        $mockRedis->expects(at(1))
            ->method('del')
            ->willReturn(1);

        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->key = 'test_key';
        $content->destroy_count = 1;

        $this->expectException(NotFoundException::class);
        $store = new Redis($mockRedis);
        $store->decreaseDestroyCount($content);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws MaintenanceModeException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testDecreaseDestroyCountNoNeedDecrement()
    {
        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->key = 'test_key';
        $content->destroy_count = -1;

        $store = new Redis($this->getMockBuilder(Client::class)->getMock());
        $response = $store->decreaseDestroyCount($content);
        $this->assertFalse($response);
    }
}