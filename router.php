<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/routing.php';

$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = route_normalize_path($rawPath);
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

if (route_dispatch_pretty_path($path)) {
    return true;
}

if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}

http_response_code(404);
require __DIR__ . '/pagina.php';
return true;
