<?php

declare(strict_types=1);

namespace Copy2Cloud\Tests\Base\Exceptions;

use Copy2Cloud\Base\Constants\HttpStatusCodes;
use Copy2Cloud\Base\Exceptions\AccessDeniedException;
use Copy2Cloud\Base\Exceptions\AuthenticationException;
use Copy2Cloud\Base\Exceptions\ConfigurationException;
use Copy2Cloud\Base\Exceptions\DefaultException;
use Copy2Cloud\Base\Exceptions\DuplicateEntryException;
use Copy2Cloud\Base\Exceptions\GatewayException;
use Copy2Cloud\Base\Exceptions\InvalidArgumentException;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Exceptions\NotFoundException;
use Copy2Cloud\Base\Exceptions\ServiceUnavailableException;
use Copy2Cloud\Base\Exceptions\StoreRedisException;
use Copy2Cloud\Base\Exceptions\UnexpectedValueException;
use PHPUnit\Framework\TestCase;

class MockException
{
    public static function throw($class, ...$parameters)
    {
        throw new $class(...$parameters);
    }
}

final class DefaultExceptionTest extends TestCase
{
    public function testDefaultException()
    {
        try {
            MockException::throw(DefaultException::class, 'Test another exception message', 2);
        } catch (DefaultException $e) {
            $this->assertTrue(method_exists($e, 'getIdentifier'),);
            $this->assertEquals(2, $e->getIdentifier());
            $this->assertEquals(
                'Copy2Cloud\Base\Exceptions\DefaultException: [500]: Test another exception message',
                (string)$e
            );
        }

        $this->expectException(DefaultException::class);
        $this->expectExceptionMessage('Test exception message');
        $this->expectExceptionCode(HttpStatusCodes::INTERNAL_SERVER_ERROR);

        MockException::throw(DefaultException::class, 'Test exception message', 1);
    }

    public function testAccessDeniedException()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionCode(HttpStatusCodes::FORBIDDEN);

        MockException::throw(AccessDeniedException::class, 'Test exception message');
    }

    public function testAuthenticationException()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(HttpStatusCodes::UNAUTHORIZED);

        MockException::throw(AuthenticationException::class, 'Test exception message');
    }

    public function testConfigurationException()
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionCode(HttpStatusCodes::INTERNAL_SERVER_ERROR);

        MockException::throw(ConfigurationException::class, 'Test exception message');
    }

    public function testDuplicateEntryException()
    {
        $this->expectException(DuplicateEntryException::class);
        $this->expectExceptionCode(HttpStatusCodes::CONFLICT);

        MockException::throw(DuplicateEntryException::class, 'Test exception message');
    }

    public function testGatewayException()
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionCode(HttpStatusCodes::BAD_GATEWAY);

        MockException::throw(GatewayException::class, 'Test exception message');
    }

    public function testInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(HttpStatusCodes::NOT_ACCEPTABLE);

        MockException::throw(InvalidArgumentException::class, 'Test exception message');
    }

    public function testMaintenanceModeException()
    {
        $this->expectException(MaintenanceModeException::class);
        $this->expectExceptionCode(HttpStatusCodes::SERVICE_UNAVAILABLE);

        MockException::throw(MaintenanceModeException::class, 'Test exception message');
    }

    public function testNotFoundException()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(HttpStatusCodes::NOT_FOUND);

        MockException::throw(NotFoundException::class, 'Test exception message');
    }

    public function testServiceUnavailableException()
    {
        $this->expectException(ServiceUnavailableException::class);
        $this->expectExceptionCode(HttpStatusCodes::SERVICE_UNAVAILABLE);

        MockException::throw(ServiceUnavailableException::class, 'Test exception message');
    }

    public function testStoreRedisException()
    {
        $this->expectException(StoreRedisException::class);
        $this->expectExceptionCode(HttpStatusCodes::INTERNAL_SERVER_ERROR);

        MockException::throw(StoreRedisException::class, 'Test exception message');
    }

    public function testUnexpectedValueException()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionCode(HttpStatusCodes::NOT_ACCEPTABLE);

        MockException::throw(UnexpectedValueException::class, 'Test exception message');
    }
}
