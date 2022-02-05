<?php

namespace Copy2Cloud\Tests\Core\Contents;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Utilities\Container;
use Copy2Cloud\Core\Contents\Content;
use PHPUnit\Framework\TestCase;

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
}
