<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Where the application core lives. In development it sits one level up, as
// Laravel ships. On the production host the core is kept outside the web root
// in a sibling "private" folder (/home/<user>/private next to public_html), so
// nothing but this folder's files can ever be requested over the web.
$core = is_file(__DIR__.'/../vendor/autoload.php')
    ? __DIR__.'/..'
    : __DIR__.'/../private';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $core.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $core.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $core.'/bootstrap/app.php';

// The folder being served is the public folder, wherever it is — so asset
// versioning and uploads resolve against public_html rather than the unused
// copy of public/ inside the core.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
