<?php

namespace Copy2Cloud\Tests\Rest;

use Copy2Cloud\App;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Utilities\Container;
use PHPUnit\Framework\TestCase;

class DefaultsTest extends TestCase
{
    /**
     * @return void
     * @throws MaintenanceModeException
     *
     * @todo improve cors check
     */
    public function testCors()
    {
        Container::clean(Container::RESOURCE_LOG);
        Container::getConfig()->log['level'] = 'error';

        $_SERVER['REQUEST_URI'] = '/v1/contents';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $this->expectOutputString('');
        $app = new App();
        $app->run();
    }
}
