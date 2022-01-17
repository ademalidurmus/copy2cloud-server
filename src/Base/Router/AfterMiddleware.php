<?php

namespace Copy2Cloud\Base\Router;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Container;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Json;
use Copy2Cloud\Base\Log;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class AfterMiddleware
{
    /**
     * Example middleware invokable class
     *
     * @param ServerRequest|Request $request PSR-7 request
     * @param RequestHandler $handler PSR-15 request handler
     *
     * @return Response
     * @throws MaintenanceModeException
     */
    public function __invoke(ServerRequest|Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        $headers = $response->getHeaders();
        foreach ($headers as $key => $value) {
            $exp = explode('-', $key);
            array_walk(
                $exp,
                function ($v, $k) use (&$exp) {
                    $exp[$k] = ucfirst($v);
                }
            );
            $key = implode('-', $exp);
            switch ($key) {
                case 'Content-Type':
                case 'Location':
                case 'Content-Disposition':
                    // $key
                    break;

                default:
                    $response = $response->withoutHeader($key);
                    $response = $response->withHeader("X-{$key}", $value[0]);
                    break;
            }
        }
        $response = $response->withHeader('X-Powered-By', APP_NAME);
        $response = $response->withHeader('X-App-Version', APP_VERSION);
        $response = $response->withHeader('X-Status', $response->getStatusCode());

        Log::requestResponseLog(
            [
                CommonConstants::RESPONSE => [
                    'headers' => (array)$response->getHeaders(),
                    // 'body' => Container::getLog()->isDebugEnabled() ? Json::decode($response->getBody()->getContents(), true) : [],
                ]
            ],
            true
        );

        return $response;
    }
}
