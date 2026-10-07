<?php
declare(strict_types=1);

/**
 * Loga týmů z handball.cz přes vlastní server (s cache).
 * Prohlížeč návštěvníka tak nekontaktuje cizí server → žádný přenos IP adresy třetí straně.
 */
require dirname(__DIR__) . '/bootstrap.php';

$path = '/' . ltrim((string) ($_GET['p'] ?? ''), '/');
if (!preg_match('~^/storage/[^?#]+\.(png|jpe?g|gif|webp|svg)$~i', $path) || str_contains($path, '..')) {
    http_response_code(404);
    exit;
}
$dir = STORAGE . '/cache/logos';
if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
}
$file = $dir . '/' . md5($path);
$types = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

$miss = $file . '.miss';
if (is_file($miss) && time() - filemtime($miss) < 3600 && !is_file($file)) {   // nedávno se nepodařilo – nezkoušet znovu
    http_response_code(404);
    exit;
}
if (!is_file($file) || time() - filemtime($file) > 30 * 86400) {
    $parts = array_map('rawurlencode', explode('/', ltrim($path, '/')));
    $ch = curl_init(csh_cfg('base') . '/' . implode('/', $parts));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6, CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_USERAGENT => 'LIONS-Handball-Web/1.0', CURLOPT_MAXFILESIZE => 3 * 1048576]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $mime = $body ? (new finfo(FILEINFO_MIME_TYPE))->buffer($body) : '';
    if ($code === 200 && in_array($mime, $types, true)) {
        @file_put_contents($file . '.tmp', $body);
        @rename($file . '.tmp', $file);
    } elseif (!is_file($file)) {
        @touch($miss);
        http_response_code(404);   // prohlížeč zobrazí iniciály týmu
        exit;
    }
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
if (!in_array($mime, $types, true)) {
    http_response_code(404);
    exit;
}
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=604800');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($file));
readfile($file);
