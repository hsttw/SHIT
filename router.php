<?php
// Dev-server router for PHP's built-in server (`php -S`). Serves an existing
// static asset directly; otherwise hands the request to the Slim front
// controller. Used by `make serve`.
$root = __DIR__ . '/app/public';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path !== '/' && is_file($root . $path)) {
    return false;
}

require $root . '/index.php';
