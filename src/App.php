<?php

namespace Copy2Cloud;

use Copy2Cloud\Base\Constants\ErrorCodes;
use Copy2Cloud\Base\Container;
use Copy2Cloud\Base\Exceptions\InvalidArgumentException;
use Copy2Cloud\Base\Exceptions\MaintenanceModeException;
use Copy2Cloud\Base\Router\AfterMiddleware;
use Copy2Cloud\Base\Router\BeforeMiddleware;
use Copy2Cloud\Base\Router\HttpErrorHandler;
use Respect\Validation\Validator as v;
use Slim\App as SlimApp;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteInterface;
use Throwable;

class App
{
    /**
     * @var SlimApp
     */
    protected SlimApp $app;

    public function __construct()
    {
    }

    /**
     * Method to register routes
     *
     * @throws InvalidArgumentException
     */
    public function registerRoutes()
    {
        $this->register('Defaults');
    }

    /**
     * Method to run application
     *
     * @return void
     * @throws MaintenanceModeException
     */
    public function run(): void
    {
        try {
            $this->app = AppFactory::create();
            $this->app->addBodyParsingMiddleware();
            $this->app->add(new BeforeMiddleware());
            $this->app->add(new AfterMiddleware());
            $this->app->addRoutingMiddleware();

            $isDebugEnabled = Container::getLog()->isDebugEnabled();

            $errorMiddleware = $this->app->addErrorMiddleware($isDebugEnabled, $isDebugEnabled, $isDebugEnabled, Container::getLog());
            $errorMiddleware->setDefaultErrorHandler(new HttpErrorHandler($this->app->getCallableResolver(), $this->app->getResponseFactory()));

            $this->registerRoutes();

            $this->app->run();
        } catch (Throwable $th) {
            Container::getLog()->error(
                'Unknown error occurred.',
                [
                    'type' => 'ERROR',
                    'body' => [
                        'message' => $th->getMessage(),
                        'file' => $th->getFile(),
                        'line' => $th->getLine(),
                        'trace' => $th->getTraceAsString()
                    ],
                ]
            );
            header('HTTP/1.1 500 Internal Server Error');
        }
    }

    /**
     * Method to register routes for given class and namespaces
     *
     * @param string $name
     * @param string|null $namespace
     * @throws InvalidArgumentException
     */
    public function register(string $name, ?string $namespace = null): void
    {
        if (v::nullType()->validate($namespace)) {
            $namespace = "\\Copy2Cloud\\Rest\\";
        }

        $className = $namespace . $name;

        $routes = null;
        if (method_exists($className, 'getRoutes')) {
            $routes = $className::getRoutes();
            foreach ($routes as &$route) {
                $exp = explode(' ', $route[0]);
                $route[2] = $route[1];
                $route[0] = $exp[0];
                $route[1] = $exp[1];

                if ($route[1] !== '/') {
                    $route[1] = rtrim($route[1], '/');
                }
            }
        }

        if (!v::arrayType()->validate($routes)) {
            $routes = [];
        }

        foreach ($routes as $value) {
            $value[2] = "{$className}:{$value[2]}";
            $this->addRoute($value);
        }
    }

    /**
     * Method to add route
     *
     * @param array $data
     * @return RouteInterface
     * @throws InvalidArgumentException
     */
    public function addRoute(array $data): RouteInterface
    {
        $data[0] = strtolower($data[0]);

        if (!v::in(['get', 'post', 'put', 'delete', 'head', 'options', 'patch'], true)->validate($data[0])) {
            throw new InvalidArgumentException('Invalid route type!', ErrorCodes::INVALID_ROUTE_TYPE);
        }

        if (v::in(['head'], true)->validate($data[0])) {
            return $this->app->map([strtoupper($data[0])], $data[1], $data[2]);
        }

        return $this->app->{$data[0]}($data[1], $data[2]);
    }
}
