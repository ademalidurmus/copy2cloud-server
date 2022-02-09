<?php

namespace Copy2Cloud\Tests\Rest;

use Copy2Cloud\Base\Constants\HttpStatusCodes;
use Copy2Cloud\Base\Constants\Limitations;
use Copy2Cloud\Base\Exceptions\DuplicateEntryException;
use Copy2Cloud\Base\Exceptions\InvalidArgumentException;
use Copy2Cloud\Base\Exceptions\UnexpectedValueException;
use Copy2Cloud\Base\Utilities\Container;
use Copy2Cloud\Base\Utilities\Json;
use Copy2Cloud\Core\Contents\Content;
use Copy2Cloud\Rest\Contents;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function PHPUnit\Framework\at;

class ContentsTest extends TestCase
{
    /**
     * @throws DuplicateEntryException
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
     * @throws EnvironmentIsBrokenException
     */
    public function testCreate()
    {
        $requestData = [
            'key' => 'test',
            'content' => 'test content',
            'secret' => 'test_secret'
        ];
        $request = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParsedBody'])
            ->getMock();
        $request->expects(at(0))
            ->method('getParsedBody')
            ->willReturn($requestData);

        $responseData = [
            'key' => 'test',
            'value' => 'test content',
            'secret' => 'test_secret',
            'insert_time' => time(),
            'expire_time' => time() + Limitations::DEFAULT_TTL,
            'ttl' => Limitations::DEFAULT_TTL,
            'attributes' => [
                'size' => 12,
            ],
            'acl' => [
                'owner' => '127.0.0.1',
            ],
        ];
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['withJson', 'getStatusCode', 'getBody'])
            ->getMock();
        $response->expects(at(0))
            ->method('withJson')
            ->willReturn($response);
        $response->expects(at(1))
            ->method('getStatusCode')
            ->willReturn(HttpStatusCodes::CREATED);
        $response->expects(at(2))
            ->method('getBody')
            ->willReturn(Json::encode($responseData));

        $mockRedis = $this->getMockBuilder(Client::class)
            ->addMethods(['exists', 'hmset', 'expire'])
            ->getMock();

        $mockRedis->expects(at(0))
            ->method('exists')
            ->willReturn(0);

        $mockRedis->expects(at(1))
            ->method('hmset')
            ->willReturn('OK');

        $mockRedis->expects(at(2))
            ->method('expire')
            ->willReturn(1);

        Container::set(Container::RESOURCE_REDIS, $mockRedis);

        $contents = new Contents();
        $response = $contents->create($request, $response, []);
        $this->assertEquals(HttpStatusCodes::CREATED, $response->getStatusCode());
        $this->assertEquals(Json::encode($responseData), $response->getBody());
    }

    /**
     * @return void
     */
    public function testPrepareResponse()
    {
        $content = new Content();
        $content->key = 'test';
        $content->content = 'test';
        $content->insert_time = time();
        $content->expire_time = time() + 100;
        $content->secret = '123456';
        $content->destroy_count = -1;
        $content->attributes = [];
        $content->acl = [];

        $response = Contents::prepareResponse($content, ['secret']);
        $this->assertIsArray($response);
        $this->assertArrayNotHasKey('secret', $response);
        $this->assertEquals('test', $response['content']);
    }
}
