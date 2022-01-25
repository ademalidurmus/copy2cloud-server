<?php

namespace Copy2Cloud\Tests;

use Copy2Cloud\App;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Exceptions\ServiceUnavailableException;
use Copy2Cloud\Base\Utilities\Config;
use Copy2Cloud\Base\Utilities\Container;
use PHPUnit\Framework\TestCase;
use Predis\Client;

final class AppTest extends TestCase
{
    public function testInit()
    {
        $app = new App();
        $this->assertInstanceOf("\Copy2Cloud\App", $app);
    }

    /**
     * @throws MaintenanceModeException
     */
    public function testRun()
    {
        try {
            $mockRedis = $this->createMock(Client::class);
            Container::set(Container::RESOURCE_REDIS, $mockRedis);


            $config = Config::init(__DIR__ . '/.mock/mock_config.ini');
            $config->log['filename'] = 'php://output';
            $config->log['level'] = 'error';

            Container::init($config);

            $this->expectOutputString('{"status":404,"message":"Endpoint does not exist!","identifier":2}');
            $app = new App();
            $app->run();
        } finally {
            Container::clean(Container::RESOURCE_REDIS);
        }
    }
}