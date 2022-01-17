<?php

namespace Copy2Cloud\Base\Router;

use Copy2Cloud\Base\Constants\CommonConstants;
use Copy2Cloud\Base\Container;
use Copy2Cloud\Base\Exceptions\DefaultException;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Json;
use Copy2Cloud\Base\Log;
use Psr\Http\Message\ResponseInterface;
use Slim\Handlers\ErrorHandler;

class HttpErrorHandler extends ErrorHandler
{
    /**
     * @var DefaultException
     */
    protected $exception;

    /**
     * @return ResponseInterface
     * @throws MaintenanceModeException
     */
    protected function respond(): ResponseInterface
    {
        $this->request = Container::get(CommonConstants::REQUEST) ?? Container::set(CommonConstants::REQUEST, $this->request);

        if (Container::getLog()->isDebugEnabled()) {
            Container::getLog()->error('', ['exception' => $this->exception]); // TODO: need improvement
        }

        $statusCode = $this->exception->getCode();
        $message = $this->exception->getMessage();

        $identifier = 0;
        if (method_exists($this->exception, 'getIdentifier')) {
            $identifier = $this->exception->getIdentifier();
        }

        $body = [
            'status' => $statusCode,
            'message' => $message,
            'identifier' => $identifier,
        ];

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(Json::encode($body));
        $response = $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
        $response = $response->withHeader('X-Powered-By', APP_NAME);
        $response = $response->withHeader('X-App-Version', APP_VERSION);
        $response = $response->withHeader('X-Status', $statusCode);
        $response = $response->withHeader('X-Message', $message);
        $response = $response->withHeader('X-Identifier', $identifier);
        $response = $response->withStatus($statusCode);

        Log::requestResponseLog(
            [
                CommonConstants::RESPONSE => [
                    'headers' => $response->getHeaders(),
                    // 'body' => Json::decode($response->getBody()->getContents(), true) ?? [],
                ]
            ],
            true
        );

        return $response;
    }
}
