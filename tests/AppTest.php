<?php

namespace Copy2Cloud\Tests;

use Copy2Cloud\App;
use PHPUnit\Framework\TestCase;

final class AppTest extends TestCase
{
    public function testInit()
    {
        $app = new App();
        $this->assertInstanceOf("\Copy2Cloud\App", $app);
    }
}