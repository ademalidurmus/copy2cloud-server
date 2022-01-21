<?php

namespace Copy2Cloud\Rest;

use Copy2Cloud\Base\Constants\HttpStatusCodes;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;

class Contents extends Base
{
    /**
     * @var string[][]
     */
    protected static array $routes = [
        ['POST /v1/contents', 'create'],
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
    public function create(Request $request, Response $response, array $args): Response|ResponseInterface
    {
        $_POST = $request->getParsedBody();

        $body = [];
        return $response->withJson($body, HttpStatusCodes::CREATED);
    }

}