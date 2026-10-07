<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

/**
 * Adresy jsou stejné jako na původním WordPress webu, aby fungovaly staré odkazy i Google.
 */
$routes = site_routes();   // seznam stránek je v lib/seo.php (používá ho i sitemap.php)

$path = current_path();
$route = $routes[$path] ?? null;

if ($route === null && team($path)) {
    $route = ['page' => 'team', 'title' => team($path)['title'], 'team' => team($path)];
}

if ($route === null) {
    http_response_code(404);
    $route = ['page' => '404', 'title' => 'Stránka nenalezena'];
}

$route['slug'] = $path;
$page = $route;
ob_start();
require ROOT . '/pages/' . $route['page'] . '.php';
$content = ob_get_clean();

render_template('layout', ['page' => $page, 'content' => $content]);
