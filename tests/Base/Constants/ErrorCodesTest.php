<?php

declare(strict_types=1);

namespace Copy2Cloud\Tests\Base\Constants;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ErrorCodesTest extends TestCase
{
    public function testErrorCodesAreUnique()
    {
        $reflectionClass = new ReflectionClass('Copy2Cloud\Base\Constants\ErrorCodes');
        $constantList = $reflectionClass->getConstants();

        $errorCodeValuesCount = count(array_unique(array_values($constantList)));
        $errorCodesCount = count($constantList);
        $this->assertEquals($errorCodeValuesCount, $errorCodesCount, 'Error code values must be unique!');
    }
}