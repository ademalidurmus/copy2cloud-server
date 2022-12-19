<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Copy2Cloud\App;
use Copy2Cloud\Base\Utilities\Config;
use Copy2Cloud\Base\Utilities\Container;

try {
    define('APP_VERSION', '0.0.1');

    $config = Config::init(__DIR__ . '/../storage/config/c2c.conf');
    Container::init($config);

    define('APP_NAME', $config->general['app_name'] ?? 'copy2cloud');

    $app = new App();
    $app->run();
} catch (Throwable $th) {
    error_log('Application could not run! Error: ' . $th->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
}
