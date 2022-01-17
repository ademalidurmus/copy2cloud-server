<?php

namespace Copy2Cloud\Tests\Base;

use Copy2Cloud\Base\Crud;
use PHPUnit\Framework\TestCase;

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
}