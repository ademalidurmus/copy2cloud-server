<?php

namespace Copy2Cloud\Rest;

use Copy2Cloud\Base\Constants\ErrorCodes;
use Copy2Cloud\Base\Constants\HttpStatusCodes;
use Copy2Cloud\Base\Exceptions\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;

class Defaults extends Base
{
    /**
     * @var string[][]
     */
    protected static array $routes = [
        ['GET /v1/ping', 'ping'],
        ['GET /', 'error'],
        ['GET /{routes:.+}', 'error'],
        ['POST /{routes:.+}', 'error'],
        ['PUT /{routes:.+}', 'error'],
        ['DELETE /{routes:.+}', 'error'],
        ['PATCH /{routes:.+}', 'error'],
        ['HEAD /{routes:.+}', 'error'],
        ['OPTIONS /{routes:.+}', 'cors'],
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
     * @return ResponseInterface|Response
     */
    public function ping(Request $request, Response $response, array $args): Response|ResponseInterface
    {
        $body = [
            'status' => HttpStatusCodes::OK,
            'message' => 'pong',
        ];
        return $response->withJson($body, HttpStatusCodes::OK);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return mixed
     * @throws NotFoundException
     */
    public function error(Request $request, Response $response, array $args): mixed
    {
        throw new NotFoundException('Endpoint does not exist!', ErrorCodes::ENDPOINT_NOT_FOUND);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function cors(Request $request, Response $response, array $args): Response
    {
        return $response;
    }
}
