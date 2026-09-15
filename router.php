<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

if (preg_match('#^/produto/([a-zA-Z0-9-]+)/?$#', $path, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/produto-detalhe.php';
    return true;
}

if (preg_match('#^/categoria/([a-zA-Z0-9-]+)/?$#', $path, $matches)) {
    $_GET['categoria'] = $matches[1];
    require __DIR__ . '/produto.php';
    return true;
}

if (preg_match('#^/(termos|privacidade|devolucoes)/?$#', $path, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/pagina.php';
    return true;
}

if ($path === '/contactos' || $path === '/contactos/') {
    require __DIR__ . '/contactos.php';
    return true;
}

if ($path === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    return true;
}

require __DIR__ . '/index.php';
return true;
