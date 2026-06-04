<?php

declare(strict_types=1);

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    http_response_code(500);
    die('TraceOn requires PHP 8.3.0 or higher. Current: ' . PHP_VERSION);
}

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require_once __DIR__ . '/../app/Config/constants.php';

$router = new App\Core\Router();

require_once __DIR__ . '/../routes.php';

$router->dispatch();
