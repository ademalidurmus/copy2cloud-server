<?php

namespace Copy2Cloud\Tests\Base;

use Copy2Cloud\Base\Crud;
use Copy2Cloud\Base\Exceptions\UnexpectedValueException;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Validator as v;

class MockObject extends Crud
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

final class CrudTest extends TestCase
{
    public function testGetterWithDefaultValue()
    {
        $crud = new Crud('my_default_value');

        $this->assertObjectNotHasAttribute('test', $crud);
        $this->assertEquals('my_default_value', $crud->test);
    }

    public function testGetterIsNull()
    {
        $crud = new Crud();

        $this->assertObjectNotHasAttribute('test', $crud);
        $this->assertNull($crud->test);
    }

    public function testSetterGetter()
    {
        $crud = new Crud();
        $crud->testKey = 'test value';

        $this->assertObjectHasAttribute('testKey', $crud);
        $this->assertEquals('test value', $crud->testKey);

        $crud->testKey = null;
        $this->assertObjectHasAttribute('testKey', $crud);

        unset($crud->testKey);
        $this->assertObjectNotHasAttribute('testKey', $crud);
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
}