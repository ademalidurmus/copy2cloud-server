<?php

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
        $store = $this->getMockBuilder(Redis::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create', 'isExists'])
            ->getMock();

        $store->expects(at(0))
            ->method('isExists')
            ->willReturn(false);

        $store->expects(at(1))
            ->method('create')
            ->willReturn(new Content);

        $content = new Content(null, null, $store);
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
        $store = $this->getMockBuilder(Redis::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create', 'isExists'])
            ->getMock();

        $store->expects(at(0))
            ->method('isExists')
            ->willReturn(false);

        $store->expects(at(1))
            ->method('create')
            ->willReturn(new Content);

        $content = new Content(null, null, $store);
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
        $store = $this->getMockBuilder(Redis::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isExists'])
            ->getMock();

        $store->expects(at(0))
            ->method('isExists')
            ->willReturn(true);

        $content = new Content(null, null, $store);

        $this->expectException(DuplicateEntryException::class);
        $content->create([
            'content' => 'test content',
            'key' => 'test',
            'acl' => [
                'owner' => '127.0.0.1',
            ],
        ]);
    }
}
