<?php

declare(strict_types=1);

namespace Copy2Cloud\Tests\Core\Contents;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Constants\Limitations;
use Copy2Cloud\Base\Exceptions\AccessDeniedException;
use Copy2Cloud\Base\Exceptions\DuplicateEntryException;
use Copy2Cloud\Base\Exceptions\InvalidArgumentException;
use Copy2Cloud\Base\Exceptions\NotFoundException;
use Copy2Cloud\Base\Exceptions\UnexpectedValueException;
use Copy2Cloud\Base\Utilities\Container;
use Copy2Cloud\Base\Utilities\Str;
use Copy2Cloud\Core\Contents\Content;
use Copy2Cloud\Core\Contents\Store\Redis;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\at;

/**
 * @todo improve ip range examples
 */
class ContentTest extends TestCase
{
    public function testGetClientScopeAllowList()
    {
        $content = new Content();
        $content->acl = [];
        $content->acl['allow'] = [
            '0.0.0.0/24',
            '127.0.0.1-127.0.0.25',
            '1.1.1.1'
        ];
        Container::setClientIp('0.0.0.0');

        $scope = $content->getClientScope();
        $this->assertIsArray($scope);
        $this->assertContains(CommonConstants::READ, $scope);
        $this->assertNotContains(CommonConstants::UPDATE, $scope);

        $scope = $content->getClientScope('127.0.0.2');
        $this->assertIsArray($scope);
        $this->assertContains(CommonConstants::READ, $scope);
        $this->assertNotContains(CommonConstants::UPDATE, $scope);
    }

    public function testGetClientScopeDenyList()
    {
        $content = new Content();
        $content->acl = [];
        $content->acl['deny'] = [
            '0.0.0.0/24',
            '1.1.1.1'
        ];
        Container::setClientIp('0.0.0.0');
        $scope = $content->getClientScope();
        $this->assertIsArray($scope);
        $this->assertNotContains(CommonConstants::READ, $scope);
        $this->assertNotContains(CommonConstants::UPDATE, $scope);
    }

    public function testGetClientScopeOwner()
    {
        $content = new Content();
        $content->acl = [];
        $content->acl['allow'] = [
            '0.0.0.0/24',
            '1.1.1.1',
        ];
        $content->acl['owner'] = '1.1.1.1';

        $scope = $content->getClientScope('1.1.1.1');
        $this->assertIsArray($scope);
        $this->assertContains(CommonConstants::READ, $scope);
        $this->assertContains(CommonConstants::UPDATE, $scope);
    }

    public function testGetClientScopeRead()
    {
        $content = new Content();
        $content->acl = [];
        $content->acl['allow'] = [
            '1.1.1.2',
        ];
        $content->acl['owner'] = '1.1.1.1';

        $scope = $content->getClientScope('1.1.1.3');
        $this->assertIsArray($scope);
        $this->assertEmpty($scope);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws DuplicateEntryException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     * @throws EnvironmentIsBrokenException
     */
    public function testCreateGenerateRandomKey()
    {
        $mockStore = $this->getMockBuilder(Redis::class)
            ->onlyMethods(['create', 'isExists'])
            ->getMock();

        $mockStore->expects(at(0))
            ->method('isExists')
            ->willReturn(false);

        $mockStore->expects(at(1))
            ->method('create')
            ->willReturn(new Content);

        $content = new Content(null, null, $mockStore);
        $create = $content->create([
            'content' => 'test content',
            'acl' => [
                'owner' => '127.0.0.1',
            ],
        ]);
        $this->assertInstanceOf(Content::class, $create);
        $this->assertEquals(Limitations::RANDOM_KEY_LENGTH, strlen($create->key));
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws DuplicateEntryException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCreate()
    {
        $mockStore = $this->getMockBuilder(Redis::class)
            ->onlyMethods(['create', 'isExists'])
            ->getMock();

        $mockStore->expects(at(0))
            ->method('isExists')
            ->willReturn(false);

        $mockStore->expects(at(1))
            ->method('create')
            ->willReturn(new Content);

        $content = new Content(null, null, $mockStore);
        $create = $content->create([
            'content' => 'test content',
            'key' => 'test',
            'secret' => 'test_secret',
            'acl' => [
                'owner' => '127.0.0.1',
                'test_key' => 'test value'
            ],
        ]);
        $this->assertInstanceOf(Content::class, $create);
        $this->assertIsInt($create->ttl);
        $this->assertEquals(12, $create->attributes['size']);
        $this->assertEquals(-1, $create->destroy_count);
        $this->assertNull($create->acl['test_key'] ?? null);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws DuplicateEntryException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCreateIsExists()
    {
        $mockStore = $this->getMockBuilder(Redis::class)
            ->onlyMethods(['isExists'])
            ->getMock();

        $mockStore->expects(at(0))
            ->method('isExists')
            ->willReturn(true);

        $content = new Content(null, null, $mockStore);

        $this->expectException(DuplicateEntryException::class);
        $content->create([
            'content' => 'test content',
            'key' => 'test',
            'acl' => [
                'owner' => '127.0.0.1',
            ],
        ]);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testRead()
    {
        Container::setClientIp('127.0.0.5');

        $readContent = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $readContent->key = 'test_key';
        $readContent->secret = 'test_secret';

        $mockStore = $this->getMockBuilder(Redis::class)
            ->onlyMethods(['read', 'decreaseDestroyCount'])
            ->getMock();

        $mockStore->expects(at(0))
            ->method('read')
            ->willReturnCallback(function ($content) {
                $content->content = 'test content';
                $content->attributes = [
                    'size' => 12,
                ];
                $content->acl = [
                    'owner' => '127.0.0.1',
                ];
                $content->ttl = Limitations::DEFAULT_TTL;
                $content->expire_time = time() + Limitations::DEFAULT_TTL;
                $content->update_time = time();
                $content->insert_time = time();
                $content->destroy_count = -1;

                return $content;
            });

        $mockStore->expects(at(1))
            ->method('decreaseDestroyCount')
            ->willReturn(false);

        $content = new Content('test_key', 'test_secret', $mockStore);
        $this->assertEquals(-1, $content->destroy_count);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testReadForRestrictedClient()
    {
        Container::setClientIp('127.0.0.5');

        $readContent = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $readContent->key = 'test_key';
        $readContent->secret = 'test_secret';

        $mockStore = $this->getMockBuilder(Redis::class)
            ->onlyMethods(['read'])
            ->getMock();

        $mockStore->expects(at(0))
            ->method('read')
            ->willReturnCallback(function ($content) {
                $content->content = 'test content';
                $content->attributes = [
                    'size' => 12,
                ];
                $content->acl = [
                    'deny' => [
                        '127.0.0.3',
                    ],
                    'allow' => [
                        '127.0.0.2',
                    ],
                    'owner' => '127.0.0.1',
                ];
                $content->ttl = Limitations::DEFAULT_TTL;
                $content->expire_time = time() + Limitations::DEFAULT_TTL;
                $content->update_time = time();
                $content->insert_time = time();
                $content->destroy_count = -1;

                return $content;
            });

        $this->expectException(AccessDeniedException::class);
        new Content('test_key', 'test_secret', $mockStore);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testUpdate()
    {
        Container::setClientIp('127.0.0.1');

        $mockStore = $this->getMockBuilder(Redis::class)
            ->onlyMethods(['read', 'update'])
            ->getMock();

        $mockStore->expects(at(0))
            ->method('read')
            ->willReturnCallback(function ($content) {
                $content->content = 'test content';
                $content->attributes = [
                    'size' => 12,
                ];
                $content->acl = [
                    'deny' => [
                        '127.0.0.3',
                    ],
                    'allow' => [
                        '127.0.0.2',
                    ],
                    'owner' => '127.0.0.1',
                ];
                $content->ttl = Limitations::DEFAULT_TTL;
                $content->expire_time = time() + Limitations::DEFAULT_TTL;
                $content->update_time = time();
                $content->insert_time = time();
                $content->destroy_count = -1;

                return $content;
            });

        $mockStore->expects(at(1))
            ->method('update')
            ->willReturn(new Content);

        $content = new Content('test', 'test_secret', $mockStore);
        $update = $content->update([
            'content' => 'test content updated',
            'key' => 'test',
            'secret' => 'test_secret',
            'acl' => [
                'owner' => '127.0.0.1',
            ],
        ]);
        $this->assertInstanceOf(Content::class, $update);
        $this->assertIsInt($update->ttl);
        $this->assertEquals(20, $update->attributes['size']);
        $this->assertEquals(-1, $update->destroy_count);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testUpdateForRestrictedClient()
    {
        Container::setClientIp('127.0.0.2');

        $mockStore = $this->getMockBuilder(Redis::class)
            ->onlyMethods(['read'])
            ->getMock();

        $mockStore->expects(at(0))
            ->method('read')
            ->willReturnCallback(function ($content) {
                $content->content = 'test content';
                $content->attributes = [
                    'size' => 12,
                ];
                $content->acl = [
                    'deny' => [
                        '127.0.0.3',
                    ],
                    'allow' => [
                        '127.0.0.2',
                    ],
                    'owner' => '127.0.0.1',
                ];
                $content->ttl = Limitations::DEFAULT_TTL;
                $content->expire_time = time() + Limitations::DEFAULT_TTL;
                $content->update_time = time();
                $content->insert_time = time();
                $content->destroy_count = -1;

                return $content;
            });

        $this->expectException(AccessDeniedException::class);
        $content = new Content('test', 'test_secret', $mockStore);
        $content->update([
            'content' => 'test content updated',
        ]);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     *
     * @todo fix given object __destruct problem
     */
    public function testDelete()
    {
        Container::setClientIp('127.0.0.1');

        $mockStore = $this->getMockBuilder(Redis::class)
            ->onlyMethods(['read', 'delete'])
            ->getMock();

        $mockStore->expects(at(0))
            ->method('read')
            ->willReturnCallback(function ($content) {
                $content->content = 'test content';
                $content->attributes = [
                    'size' => 12,
                ];
                $content->acl = [
                    'owner' => '127.0.0.1',
                ];
                $content->ttl = Limitations::DEFAULT_TTL;
                $content->expire_time = time() + Limitations::DEFAULT_TTL;
                $content->update_time = time();
                $content->insert_time = time();
                $content->destroy_count = -1;

                return $content;
            });

        $mockStore->expects(at(1))
            ->method('delete')
            ->willReturnCallback(function ($content) {
                $fields = [
                    'key',
                    'content',
                    'acl',
                    'attributes',
                    'destroy_count',
                    'ttl',
                    'insert_time',
                    'update_time',
                    'expire_time',
                    'secret',
                ];
                foreach ($fields as $field) {
                    unset($content->{$field});
                }
                return $content;
            })
            ->willReturn(true);

        $content = new Content('test', 'test_secret', $mockStore);
        $delete = $content->delete();

        $this->assertInstanceOf(Content::class, $delete);
        // $this->assertNull($delete->key);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testDeleteForRestrictedClient()
    {
        Container::setClientIp('127.0.0.2');

        $mockStore = $this->getMockBuilder(Redis::class)
            ->onlyMethods(['read'])
            ->getMock();

        $mockStore->expects(at(0))
            ->method('read')
            ->willReturnCallback(function ($content) {
                $content->content = 'test content';
                $content->attributes = [
                    'size' => 12,
                ];
                $content->acl = [
                    'allow' => [
                        '127.0.0.2',
                    ],
                    'owner' => '127.0.0.1',
                ];
                $content->ttl = Limitations::DEFAULT_TTL;
                $content->expire_time = time() + Limitations::DEFAULT_TTL;
                $content->update_time = time();
                $content->insert_time = time();
                $content->destroy_count = -1;

                return $content;
            });

        $this->expectException(AccessDeniedException::class);
        $content = new Content('test', 'test_secret', $mockStore);
        $content->delete();
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCheckValueInvalidArgumentInvalidArgumentError()
    {
        $this->expectException(InvalidArgumentException::class);
        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->testInvalidKey = '';
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws DuplicateEntryException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCheckValueKeyAlphanumericError()
    {
        $this->expectException(UnexpectedValueException::class);
        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->create(
            [
                'key' => 'test key with space',
            ]
        );
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws DuplicateEntryException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCheckValueKeyLengthError()
    {
        $this->expectException(UnexpectedValueException::class);
        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->create(
            [
                'key' => Str::generateRandomStr(Limitations::KEY_MAX_LENGTH + 1),
            ]
        );
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCheckValueContentVariableTypeError()
    {
        $this->expectException(UnexpectedValueException::class);
        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->content = [];
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     *
     * @todo mock content generate operation need performance improvement
     */
    public function testCheckValueContentLengthError()
    {
        $this->expectException(UnexpectedValueException::class);
        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->content = Str::generateRandomStr(Limitations::CONTENT_MAX_LENGTH + 1);
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCheckValueExpireTimeError()
    {
        $this->expectException(UnexpectedValueException::class);
        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->expire_time = time() - 100;
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCheckValueTtlError()
    {
        $this->expectException(UnexpectedValueException::class);
        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->ttl = Limitations::MAX_TTL + 1;
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCheckValueDestroyCountError()
    {
        $this->expectException(UnexpectedValueException::class);
        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->destroy_count = 0;
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCheckValueAclAllowError()
    {
        $this->expectException(UnexpectedValueException::class);
        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->acl = [
            'allow' => ['invalid.ip.address'],
        ];
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCheckValueAclDenyError()
    {
        $this->expectException(UnexpectedValueException::class);
        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->acl = [
            'deny' => ['invalid.ip.address'],
        ];
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testCheckValueAclOwnerError()
    {
        $this->expectException(UnexpectedValueException::class);
        $content = new Content(null, null, $this->getMockBuilder(Redis::class)->getMock());
        $content->acl = [
            'deny' => ['127.0.0.1'],
            'owner' => '127.0.0.1',
        ];
    }
}
