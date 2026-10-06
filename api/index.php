<?php
// Punto de entrada para Vercel. Mantiene la estructura PHP existente y evita
// que los archivos de código se entreguen como texto plano.
$root = realpath(__DIR__ . '/..');
$path = ltrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$path = $path === '' ? 'index.php' : $path;

if (str_contains($path, '..') || $path === 'api/index.php') {
    http_response_code(404);
    exit('No encontrado.');
}

$target = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
if (!is_file($target)) {
    http_response_code(404);
    exit('No encontrado.');
}

$extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));
if ($extension === 'php') {
    chdir($root);
    require_once $root . '/lib/session.php';
    rangers_enable_firebase_sessions();
    require $target;
    exit;
}

$types = [
    'css' => 'text/css; charset=UTF-8', 'js' => 'application/javascript; charset=UTF-8',
    'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp',
    'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'ttf' => 'font/ttf', 'ico' => 'image/x-icon',
];
header('Content-Type: ' . ($types[$extension] ?? 'application/octet-stream'));
header('Cache-Control: public, max-age=3600');
readfile($target);
