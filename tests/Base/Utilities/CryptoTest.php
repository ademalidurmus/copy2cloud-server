<?php

namespace Copy2Cloud\Tests\Base\Utilities;

use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Utilities\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use PHPUnit\Framework\TestCase;

class CryptoTest extends TestCase
{
    /**
     * @return void
     * @throws EnvironmentIsBrokenException
     */
    public function testEncryptContext()
    {
        $context = [
            'content' => 'my special content',
            'attributes' => [
                'content_type' => 'text/plain',
                'size' => 18
            ],
            'acl' => [
                'allow' => [],
                'deny' => []
            ],
            'secret' => true,
            'insert_time' => time(),
        ];

        $fields = [
            'content',
            'attributes',
            'acl',
        ];
        $encryptedContext = Crypto::encryptContext($fields, $context, 'secret@password');

        $this->assertArrayHasKey('content', $encryptedContext);
        $this->assertIsString($encryptedContext['content']);
    }

    /**
     * @return void
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function testDecryptContext()
    {
        $context = [
            'content' => 'def502006125660673afd715ed3a16f5c6324ff6bbfc936d7edcc958c16fc62c459dc180705fdbc5ed39a72aa7ed97b4d8b96590a3d3e849ef9eaefeb3d53682d8fa79e9cbc463fd75be3c3626e154e34217eee944c957b459b37fa7d3c56f281dd405f6160d3e764c592c9bec41',
            'attributes' => 'def50200dcdecbd21b09906fc1a0198bf522f02096b80164e8a716302c61c7a75cb4ecc86232a881e25c2fba2e8aff0103d4f79ca33e07e6d893758605e2d2ed31c8007648b0b01071cea1e1cdc85103908ac5f03b259537096945344c5937f77539acfd82c43a916164d2a0435c10e42a350a0f08bd707b5b9c009dc5adfabf839531eb60d4fc72187eddf4889a7b2a',
            'acl' => 'def50200658e65ec4073439bcf54bb9e899eb423a90b2d95fb57da609f50ce9644d028a48c01623df72cbdcd53385218573561f7ecd7f718ad6f54170c03370aa157edbada6aa5e568e8d57653905a00586f8d3dbc7bd4f48df4c67a03a0ce61ea6ddec5055b33ee66c2e81ee8200c19b650eeba24501533716981c839',
            'secret' => true,
            'insert_time' => 1642794401,
        ];

        $fields = [
            'content',
            'attributes',
            'acl',
        ];

        $decryptedContext = Crypto::decryptContext($fields, $context, 'secret@password');

        $this->assertArrayHasKey('content', $decryptedContext);
        $this->assertEquals('my special content', $decryptedContext['content']);
    }
}