<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));


$script = $_SERVER['SCRIPT_NAME'] ?? '';

if (str_ends_with($script, '/public/index.php')
    && ! str_starts_with($_SERVER['REQUEST_URI'] ?? '', dirname($script).'/')) {
    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = dirname($script, 2).'/index.php';
}


if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}


require __DIR__.'/../vendor/autoload.php';


$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
