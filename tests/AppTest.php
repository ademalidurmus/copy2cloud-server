<?php

declare(strict_types=1);

namespace Copy2Cloud\Tests;

use Copy2Cloud\App;
use Copy2Cloud\Base\Exceptions\InvalidArgumentException;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Exceptions\ServiceUnavailableException;
use Copy2Cloud\Base\Utilities\Config;
use Copy2Cloud\Base\Utilities\Container;
use Copy2Cloud\Base\Utilities\Log;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use function PHPUnit\Framework\at;

final class AppTest extends TestCase
{
    /**
     * @return void
     */
    public function testInit()
    {
        try {
            $app = new App();
            $this->assertInstanceOf("\Copy2Cloud\App", $app);

            $mockRedis = $this->createMock(Client::class);
            Container::set(Container::RESOURCE_REDIS, $mockRedis);

            Container::clean(Container::RESOURCE_LOG);

            $config = Config::init(__DIR__ . '/.mock/mock_config.ini');
            $config->log['filename'] = 'php://output';
            $config->log['level'] = 'error';

            Container::init($config);

        } finally {
            Container::clean(Container::RESOURCE_REDIS);
        }
    }


    /**
     * @depends testInit
     * @return void
     * @throws MaintenanceModeException
     */
    public function testRunForInvalidRoute()
    {
        $this->expectOutputString('{"status":404,"message":"Endpoint does not exist!","identifier":2}');
        $app = new App();
        $app->run();
    }

    /**
     * @depends testInit
     * @return void
     * @throws MaintenanceModeException
     *
     * @todo clean error logs from unit test outputs
     */
    public function testRunDebugMode()
    {
        Container::clean(Container::RESOURCE_LOG);
        Container::getConfig()->log['level'] = 'debug';

        $this->expectOutputRegex('/{"status":404,"message":"Endpoint does not exist!","identifier":2}/');
        $this->expectOutputRegex('/\[(?P<date>.*)\]\s(?<channel>.*)\.(?<severity>.*):\s(?<message>.*)\s(\[|\{)(?<context>.*)(\]|\})\s\[(?<extra>.*)\]/');
        $app = new App();
        $app->run();
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testRunForValidEndpoints()
    {
        Container::clean(Container::RESOURCE_LOG);
        Container::getConfig()->log['level'] = 'error';

        $_SERVER['REQUEST_URI'] = '/v1/ping';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputString('{"status":200,"message":"pong"}');
        $app = new App();
        $app->run();
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public function testAddRouteError()
    {
        $this->expectException(InvalidArgumentException::class);
        $app = new App();
        $app->addRoute(['TEST', '', '']);
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public function testRegisterError()
    {
        $this->expectOutputString('');
        $app = new App();
        $app->register('TEST');
    }

    /**
     * @return void
     * @throws MaintenanceModeException
     */
    public function testRunError()
    {
        try {
            $mockLog = $this->getMockBuilder(Log::class)
                ->onlyMethods(['isDebugEnabled'])
                ->getMock();

            $mockLog->expects(at(0))
                ->method('isDebugEnabled')
                ->willThrowException(
                    new ServiceUnavailableException('Sample exception!')
                );

            Container::set(Container::RESOURCE_LOG, $mockLog);

            $this->expectOutputRegex('/\[(?P<date>.*)\]\s(?<channel>.*)\.(?<severity>.*):\s(?<message>.*)\s(\[|\{)(?<context>.*)(\]|\})\s\[(?<extra>.*)\]/');
            $app = new App();
            $app->run();
        } finally {
            Container::clean(Container::RESOURCE_LOG);
        }
    }
}