<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Locate the Laravel application root.
// - Default (development): the app lives one directory above public/
// - Shared hosting (hPanel): public/* sits in public_html/, while the Laravel
//   app sits in a sibling folder. Set NINJA_APP_PATH below to that folder name
//   (relative to public_html). Example: /home/u123/ninja_crm
$candidates = [
    __DIR__.'/../laravel_app',  // shared-hosting recommended layout
    dirname(__DIR__).'/ninja_crm', // alt sibling folder
    __DIR__.'/..',              // default monolithic layout (dev)
];
$basePath = null;
foreach ($candidates as $p) {
    if (is_file($p.'/bootstrap/app.php')) { $basePath = realpath($p); break; }
}
if (! $basePath) {
    http_response_code(500);
    exit('Laravel application files not found. Edit public/index.php $candidates.');
}

if (file_exists($basePath.'/storage/framework/maintenance.php')) {
    require $basePath.'/storage/framework/maintenance.php';
}

require $basePath.'/vendor/autoload.php';

$app = require_once $basePath.'/bootstrap/app.php';

// When the public files are served from a directory other than {basePath}/public
// (e.g. hPanel where they live in ~/domains/<x>/public_html), tell Laravel where
// the public path actually is so Vite + asset() resolve correctly.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
