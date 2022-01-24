<?php

namespace Copy2Cloud\Base\Router;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Utilities\Container;
use Copy2Cloud\Base\Utilities\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Http\ServerRequest;

class BeforeMiddleware
{
    /**
     * Example middleware invokable class
     *
     * @param ServerRequest|Request $request PSR-7 request
     * @param RequestHandler $handler PSR-15 request handler
     * @return ResponseInterface
     * @throws MaintenanceModeException
     */
    public function __invoke(ServerRequest|Request $request, RequestHandler $handler): ResponseInterface
    {
        Container::set(CommonConstants::REQUEST, $request);

        Log::requestResponseLog([
            'type' => 'API',
            'meta' => [
                'method' => $request->getMethod(),
                'path' => $request->getUri()->getPath(),
                'ip' => (string)($request->getServerParam('HTTP_X_FORWARDED_FOR') ?? $request->getServerParam('REMOTE_ADDR')),
            ],
            CommonConstants::REQUEST => [
                'headers' => $request->getHeaders(),
                // 'query_params' => $request->getQueryParams(),
                // 'body' => (array)$request->getParsedBody(),
            ]
        ]);

        return $handler->handle($request);
    }
}
