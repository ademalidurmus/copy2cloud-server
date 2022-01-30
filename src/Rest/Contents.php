<?php

declare(strict_types=1);

namespace Copy2Cloud\Rest;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Constants\HttpStatusCodes;
use Copy2Cloud\Base\Exceptions\InvalidArgumentException;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Exceptions\NotFoundException;
use Copy2Cloud\Base\Exceptions\UnexpectedValueException;
use Copy2Cloud\Base\Utilities\Container;
use Copy2Cloud\Core\Contents\Content;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Psr\Http\Message\ResponseInterface;
use Respect\Validation\Validator as v;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;

class Contents extends Base
{
    /**
     * @var string[][]
     */
    protected static array $routes = [
        ['POST /v1/contents', 'create'],
        ['GET /v1/contents/{key}', 'read'],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Method to getting routes
     *
     * @return string[][]
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     * @throws MaintenanceModeException
     */
    public function create(Request $request, Response $response, array $args): Response|ResponseInterface
    {
        $_POST = $request->getParsedBody();

        $_POST['acl']['owner'] = Container::get(CommonConstants::REMOTE_ADDR);
        $content = new Content();
        $content->create($_POST);

        $body = self::prepareResponse($content);
        return $response->withJson($body, HttpStatusCodes::CREATED);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws EnvironmentIsBrokenException
     * @throws UnexpectedValueException
     * @throws NotFoundException
     */
    public function read(Request $request, Response $response, array $args): Response|ResponseInterface
    {
        $_GET = $request->getQueryParams();

        $content = new Content($args['key'], $_GET['secret'] ?? '');

        $body = self::prepareResponse($content);
        return $response->withJson($body, HttpStatusCodes::CREATED);
    }

    public static function prepareResponse(Content $data, array $without = []): array
    {
        $response = [
            'key' => (string)$data->key,
            'content' => (string)$data->content,
            'attributes' => (array)$data->attributes,
            'acl' => (array)$data->acl,
            'secret' => (string)$data->secret,
            'ttl' => (int)$data->ttl,
            'insert_time' => (int)$data->insert_time,
            'expire_time' => (int)$data->expire_time,
        ];

        foreach ($without as $key) {
            if (v::key($key)->validate($response)) {
                unset($response[$key]);
            }
        }

        return $response;
    }
}