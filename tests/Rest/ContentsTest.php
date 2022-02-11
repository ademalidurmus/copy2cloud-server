<?php

declare(strict_types=1);

namespace Copy2Cloud\Tests\Rest;

use Copy2Cloud\Base\Constants\HttpStatusCodes;
use Copy2Cloud\Base\Constants\Limitations;
use Copy2Cloud\Base\Exceptions\AccessDeniedException;
use Copy2Cloud\Base\Exceptions\DuplicateEntryException;
use Copy2Cloud\Base\Exceptions\InvalidArgumentException;
use Copy2Cloud\Base\Exceptions\NotFoundException;
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
            'content' => 'test content',
            'secret' => 'test_secret',
            'update_time' => time(),
            'insert_time' => time(),
            'expire_time' => time() + Limitations::DEFAULT_TTL,
            'ttl' => Limitations::DEFAULT_TTL,
            'attributes' => [
                'size' => 12,
            ],
            'acl' => [
                'owner' => '127.0.0.1',
            ],
            'destroy_count' => -1,
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
     * @throws EnvironmentIsBrokenException
     * @throws UnexpectedValueException
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    public function testRead()
    {
        $queryParams = [
            'secret' => 'test_secret'
        ];
        $request = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQueryParams'])
            ->getMock();
        $request->expects(at(0))
            ->method('getQueryParams')
            ->willReturn($queryParams);

        $responseData = [
            'key' => 'test',
            'content' => 'test content',
            'secret' => 'test_secret',
            'update_time' => time(),
            'insert_time' => time(),
            'expire_time' => time() + Limitations::DEFAULT_TTL,
            'ttl' => Limitations::DEFAULT_TTL,
            'attributes' => [
                'size' => 12,
            ],
            'acl' => [
                'owner' => '127.0.0.1',
            ],
            'destroy_count' => -1,
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
            ->willReturn(HttpStatusCodes::OK);
        $response->expects(at(2))
            ->method('getBody')
            ->willReturn(Json::encode($responseData));

        $redisData = $responseData;
        $redisData['content'] = 'def5020003a8e27e5ea479d4b2f719cfcac1bf5ec450fd937bcffdec1ed7092da1c3e22a759ebc9e556dfaf76b6dac354d1f1980acc8fdabc0e933e7475e2eb8eed74463a7f447fdc1fb5836b00d224defb638cea967b377de2c0ae2e9a3ab8f7526d9016d6a25f8';
        $redisData['acl'] = 'def50200db216bd35792900b66e80d8a643dea88ab52d8bac4e69ee8a4e55210e8dda0f26fb57e0ded413c04b31d9c0ce5e6b0fb6c65bb5a6b56d5b57f1259517b167ead26940f31eb387d7cdd29a83f5a87092d48e1aaac5288e71202c0ea9302fee24c56b48338bd1a3013f0123a563097bf513eec';
        $redisData['attributes'] = 'def502000bb9906f7735354dc9657ce144c5156fea2b8d6e26241b53432d320581f7b96412380e58e0596d2235e58e1fd28e95ce2a6c859503ba9857b94eabbd2c9af8f1401f28eea97c0618639fb8c18a1900e9467d8d86b060a128cd412188cbd40670acb54b260809';

        $mockRedis = $this->getMockBuilder(Client::class)
            ->addMethods(['hgetall', 'ttl'])
            ->getMock();
        $mockRedis->expects(at(0))
            ->method('hgetall')
            ->willReturn($redisData);
        $mockRedis->expects(at(1))
            ->method('ttl')
            ->willReturn(Limitations::DEFAULT_TTL);

        Container::set(Container::RESOURCE_REDIS, $mockRedis);

        $contents = new Contents();
        $response = $contents->read($request, $response, ['key' => 'test']);
        $this->assertEquals(HttpStatusCodes::OK, $response->getStatusCode());
        $this->assertEquals(Json::encode($responseData), $response->getBody());
    }

    /**
     * @return void
     * @throws AccessDeniedException
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnexpectedValueException
     */
    public function testUpdate()
    {
        Container::setClientIp('127.0.0.1');

        $queryParams = [
            'secret' => 'test_secret'
        ];
        $body = [];
        $request = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQueryParams', 'getParsedBody'])
            ->getMock();
        $request->expects(at(0))
            ->method('getParsedBody')
            ->willReturn($body);
        $request->expects(at(1))
            ->method('getQueryParams')
            ->willReturn($queryParams);

        $responseData = [
            'key' => 'test',
            'content' => 'test content',
            'secret' => 'test_secret',
            'update_time' => time(),
            'insert_time' => time(),
            'expire_time' => time() + Limitations::DEFAULT_TTL,
            'ttl' => Limitations::DEFAULT_TTL,
            'attributes' => [
                'size' => 12,
            ],
            'acl' => [
                'owner' => '127.0.0.1',
            ],
            'destroy_count' => -1,
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
            ->willReturn(HttpStatusCodes::OK);
        $response->expects(at(2))
            ->method('getBody')
            ->willReturn(Json::encode($responseData));

        $redisData = $responseData;
        $redisData['content'] = 'def5020003a8e27e5ea479d4b2f719cfcac1bf5ec450fd937bcffdec1ed7092da1c3e22a759ebc9e556dfaf76b6dac354d1f1980acc8fdabc0e933e7475e2eb8eed74463a7f447fdc1fb5836b00d224defb638cea967b377de2c0ae2e9a3ab8f7526d9016d6a25f8';
        $redisData['acl'] = 'def50200db216bd35792900b66e80d8a643dea88ab52d8bac4e69ee8a4e55210e8dda0f26fb57e0ded413c04b31d9c0ce5e6b0fb6c65bb5a6b56d5b57f1259517b167ead26940f31eb387d7cdd29a83f5a87092d48e1aaac5288e71202c0ea9302fee24c56b48338bd1a3013f0123a563097bf513eec';
        $redisData['attributes'] = 'def502000bb9906f7735354dc9657ce144c5156fea2b8d6e26241b53432d320581f7b96412380e58e0596d2235e58e1fd28e95ce2a6c859503ba9857b94eabbd2c9af8f1401f28eea97c0618639fb8c18a1900e9467d8d86b060a128cd412188cbd40670acb54b260809';

        $mockRedis = $this->getMockBuilder(Client::class)
            ->addMethods(['hgetall', 'ttl', 'exists', 'hmset', 'expire'])
            ->getMock();
        $mockRedis->expects(at(0))
            ->method('hgetall')
            ->willReturn($redisData);
        $mockRedis->expects(at(1))
            ->method('ttl')
            ->willReturn(Limitations::DEFAULT_TTL);
        $mockRedis->expects(at(2))
            ->method('exists')
            ->willReturn(1);
        $mockRedis->expects(at(3))
            ->method('hmset')
            ->willReturn('OK');
        $mockRedis->expects(at(4))
            ->method('expire')
            ->willReturn(1);

        Container::set(Container::RESOURCE_REDIS, $mockRedis);

        $contents = new Contents();
        $response = $contents->update($request, $response, ['key' => 'test']);
        $this->assertEquals(HttpStatusCodes::OK, $response->getStatusCode());
        $this->assertEquals(Json::encode($responseData), $response->getBody());
    }

    public function testDelete()
    {
        Container::setClientIp('127.0.0.1');

        $queryParams = [
            'secret' => 'test_secret'
        ];
        $request = $this->getMockBuilder(ServerRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQueryParams'])
            ->getMock();
        $request->expects(at(0))
            ->method('getQueryParams')
            ->willReturn($queryParams);

        $responseData = [
            'key' => 'test',
            'content' => 'test content',
            'secret' => 'test_secret',
            'update_time' => time(),
            'insert_time' => time(),
            'expire_time' => time() + Limitations::DEFAULT_TTL,
            'ttl' => Limitations::DEFAULT_TTL,
            'attributes' => [
                'size' => 12,
            ],
            'acl' => [
                'owner' => '127.0.0.1',
            ],
            'destroy_count' => -1,
        ];
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['withStatus', 'getStatusCode'])
            ->getMock();
        $response->expects(at(0))
            ->method('withStatus')
            ->willReturn($response);
        $response->expects(at(1))
            ->method('getStatusCode')
            ->willReturn(HttpStatusCodes::NO_CONTENT);

        $redisData = $responseData;
        $redisData['content'] = 'def5020003a8e27e5ea479d4b2f719cfcac1bf5ec450fd937bcffdec1ed7092da1c3e22a759ebc9e556dfaf76b6dac354d1f1980acc8fdabc0e933e7475e2eb8eed74463a7f447fdc1fb5836b00d224defb638cea967b377de2c0ae2e9a3ab8f7526d9016d6a25f8';
        $redisData['acl'] = 'def50200db216bd35792900b66e80d8a643dea88ab52d8bac4e69ee8a4e55210e8dda0f26fb57e0ded413c04b31d9c0ce5e6b0fb6c65bb5a6b56d5b57f1259517b167ead26940f31eb387d7cdd29a83f5a87092d48e1aaac5288e71202c0ea9302fee24c56b48338bd1a3013f0123a563097bf513eec';
        $redisData['attributes'] = 'def502000bb9906f7735354dc9657ce144c5156fea2b8d6e26241b53432d320581f7b96412380e58e0596d2235e58e1fd28e95ce2a6c859503ba9857b94eabbd2c9af8f1401f28eea97c0618639fb8c18a1900e9467d8d86b060a128cd412188cbd40670acb54b260809';

        $mockRedis = $this->getMockBuilder(Client::class)
            ->addMethods(['hgetall', 'ttl', 'del'])
            ->getMock();
        $mockRedis->expects(at(0))
            ->method('hgetall')
            ->willReturn($redisData);
        $mockRedis->expects(at(1))
            ->method('ttl')
            ->willReturn(Limitations::DEFAULT_TTL);
        $mockRedis->expects(at(2))
            ->method('del')
            ->willReturn(1);

        Container::set(Container::RESOURCE_REDIS, $mockRedis);

        $contents = new Contents();
        $response = $contents->delete($request, $response, ['key' => 'test']);
        $this->assertEquals(HttpStatusCodes::NO_CONTENT, $response->getStatusCode());
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
