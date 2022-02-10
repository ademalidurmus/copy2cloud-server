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
            ],
        ]);
        $this->assertInstanceOf(Content::class, $create);
        $this->assertIsInt($create->ttl);
        $this->assertEquals(12, $create->attributes['size']);
        $this->assertEquals(-1, $create->destroy_count);
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
        Container::setClientIp('127.0.0.1');

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
                $content->ttl = 86400;
                $content->expire_time = 1644603179;
                $content->update_time = 1644516779;
                $content->insert_time = 1644516779;
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
        Container::setClientIp('127.0.0.3');

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
                $content->ttl = 86400;
                $content->expire_time = 1644603179;
                $content->update_time = 1644516779;
                $content->insert_time = 1644516779;
                $content->destroy_count = -1;

                return $content;
            });

        $this->expectException(AccessDeniedException::class);
        new Content('test_key', 'test_secret', $mockStore);
    }
}
