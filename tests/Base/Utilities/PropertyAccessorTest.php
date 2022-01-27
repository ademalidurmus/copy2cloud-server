<?php

declare(strict_types=1);

namespace Copy2Cloud\Tests\Base\Utilities;

use Copy2Cloud\Base\Exceptions\UnexpectedValueException;
use Copy2Cloud\Base\Utilities\PropertyAccessor;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Validator as v;

class MockObject extends PropertyAccessor
{
    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     * @throws UnexpectedValueException
     */
    public function checkValue(string $key, mixed $value): mixed
    {
        switch ($key) {
            case 'testKey':
                if (!v::numericVal()->validate($value)) {
                    throw new UnexpectedValueException("{$key} value must be numeric!");
                }
                break;

            case 'testAnotherKey':
                if (!v::arrayType()->validate($value)) {
                    throw new UnexpectedValueException("{$key} value must be array!");
                }
                break;
        }

        return $value;
    }
}

final class PropertyAccessorTest extends TestCase
{
    public function testGetterWithDefaultValue()
    {
        $propertyAccessor = new PropertyAccessor('my_default_value');

        $this->assertObjectNotHasAttribute('test', $propertyAccessor);
        $this->assertEquals('my_default_value', $propertyAccessor->test);
    }

    public function testGetterIsNull()
    {
        $propertyAccessor = new PropertyAccessor();

        $this->assertObjectNotHasAttribute('test', $propertyAccessor);
        $this->assertNull($propertyAccessor->test);
    }

    public function testSetterGetter()
    {
        $propertyAccessor = new PropertyAccessor();
        $propertyAccessor->testKey = 'test value';

        $this->assertObjectHasAttribute('testKey', $propertyAccessor);
        $this->assertEquals('test value', $propertyAccessor->testKey);

        $propertyAccessor->testKey = null;
        $this->assertObjectHasAttribute('testKey', $propertyAccessor);

        unset($propertyAccessor->testKey);
        $this->assertObjectNotHasAttribute('testKey', $propertyAccessor);
    }

    public function testCrudCheckValue()
    {
        $mockObject = new MockObject();

        $this->expectException('Copy2Cloud\Base\Exceptions\UnexpectedValueException');
        $this->expectExceptionMessage('testKey value must be numeric!');
        $mockObject->testKey = 'test string value';

        unset($mockObject->testKey);
        $mockObject->testKey = 1;
        $this->assertEquals(1, $mockObject->testKey);

        $this->expectException('Copy2Cloud\Base\Exceptions\UnexpectedValueException');
        $this->expectExceptionMessage('testAnotherKey value must be array!');
        $mockObject->testAnotherKey = 'test string value';

        unset($mockObject->testAnotherKey);
        $mockObject->testAnotherKey = [
            'test_key' => 'test_val',
        ];
        $this->assertArrayHasKey('test_key', $mockObject->testAnotherKey);
    }

    public function testModifyAllowedArguments()
    {
        $mockObject = new MockObject([]);
        $getAllowedArguments = $mockObject->getAllowedArguments();
        $this->assertEquals([], $getAllowedArguments);

        $getAllowedArguments = $mockObject
            ->withAllowedArguments(['test_key_1', 'test_key_2', 'test_key_3'])
            ->getAllowedArguments();
        $this->assertEquals(['test_key_1', 'test_key_2', 'test_key_3'], $getAllowedArguments);

        $getAllowedArguments = $mockObject
            ->withoutAllowedArguments(['test_key_1', 'test_key_2'])
            ->getAllowedArguments();
        $this->assertEquals(['test_key_3'], $getAllowedArguments);
    }

    public function testModifyReadFields()
    {
        $mockObject = new MockObject([]);
        $getReadFields = $mockObject->getReadFields();
        $this->assertEquals([], $getReadFields);

        $getReadFields = $mockObject
            ->withReadFields(['test_key_1', 'test_key_2', 'test_key_3'])
            ->getReadFields();
        $this->assertEquals(['test_key_1', 'test_key_2', 'test_key_3'], $getReadFields);

        $getReadFields = $mockObject
            ->withoutReadFields(['test_key_1', 'test_key_2'])
            ->getReadFields();
        $this->assertEquals(['test_key_3'], $getReadFields);
    }

    public function testModifyUpdateFields()
    {
        $mockObject = new MockObject([]);
        $getUpdateFields = $mockObject->getUpdateFields();
        $this->assertEquals([], $getUpdateFields);

        $getUpdateFields = $mockObject
            ->withUpdateFields(['test_key_1', 'test_key_2', 'test_key_3'])
            ->getUpdateFields();
        $this->assertEquals(['test_key_1', 'test_key_2', 'test_key_3'], $getUpdateFields);

        $getUpdateFields = $mockObject
            ->withoutUpdateFields(['test_key_1', 'test_key_2'])
            ->getUpdateFields();
        $this->assertEquals(['test_key_3'], $getUpdateFields);
    }

    public function testModifySearchFields()
    {
        $mockObject = new MockObject([]);
        $getSearchFields = $mockObject->getSearchFields();
        $this->assertEquals([], $getSearchFields);

        $getSearchFields = $mockObject
            ->withSearchFields(['test_key_1', 'test_key_2', 'test_key_3'])
            ->getSearchFields();
        $this->assertEquals(['test_key_1', 'test_key_2', 'test_key_3'], $getSearchFields);

        $getSearchFields = $mockObject
            ->withoutSearchFields(['test_key_1', 'test_key_2'])
            ->getSearchFields();
        $this->assertEquals(['test_key_3'], $getSearchFields);
    }
}
