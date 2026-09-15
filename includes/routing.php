<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function route_normalize_path(string $path): string
{
    $path = '/' . trim(rawurldecode($path), '/');
    $prefixes = array_filter([
        rtrim((string) (parse_url(APP_URL, PHP_URL_PATH) ?: ''), '/'),
        '/' . basename(dirname(__DIR__)),
    ]);

    foreach ($prefixes as $prefix) {
        if ($prefix !== '' && $prefix !== '/' && str_starts_with($path . '/', $prefix . '/')) {
            $path = substr($path, strlen($prefix)) ?: '/';
            break;
        }
    }

    return $path === '' ? '/' : $path;
}

function route_dispatch_pretty_path(string $path): bool
{
    if (preg_match('#^/produto/([a-zA-Z0-9-]+)/?$#', $path, $matches)) {
        $_GET['slug'] = $matches[1];
        require dirname(__DIR__) . '/produto-detalhe.php';
        return true;
    }

    if (preg_match('#^/categoria/([a-zA-Z0-9-]+)/?$#', $path, $matches)) {
        $_GET['categoria'] = $matches[1];
        require dirname(__DIR__) . '/produto.php';
        return true;
    }

    if (preg_match('#^/(termos|privacidade|devolucoes)/?$#', $path, $matches)) {
        $_GET['slug'] = $matches[1];
        require dirname(__DIR__) . '/pagina.php';
        return true;
    }

    if ($path === '/contactos' || $path === '/contactos/') {
        require dirname(__DIR__) . '/contactos.php';
        return true;
    }

    if ($path === '/sitemap.xml') {
        require dirname(__DIR__) . '/sitemap.php';
        return true;
    }

    return false;
}
