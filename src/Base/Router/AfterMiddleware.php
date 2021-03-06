<?php

declare(strict_types=1);

namespace Copy2Cloud\Base\Router;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Utilities\Log;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class AfterMiddleware
{
    /**
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
        $response = $response->withHeader('X-Powered-By', defined('APP_NAME') ? APP_NAME : 'copy2cloud');
        $response = $response->withHeader('X-App-Version', defined('APP_VERSION') ? APP_VERSION : '0.0.1');
        $response = $response->withHeader('X-Status', $response->getStatusCode());

        Log::requestResponseLog(
            [
                CommonConstants::RESPONSE => [
                    'headers' => $response->getHeaders(),
                    // 'body' => Container::getLog()->isDebugEnabled() ? Json::decode($response->getBody()->getContents(), true) : [],
                ]
            ],
            true
        );

        return $response;
    }
}
