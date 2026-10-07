<?php
declare(strict_types=1);

/**
 * Mapa webu pro vyhledávače (https://www.lionshandball.cz/sitemap.php).
 * Seznam stránek se bere z lib/seo.php + týmy z administrace, takže je vždy aktuální.
 */
require __DIR__ . '/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach (seo_sitemap_paths() as $path) {
    $priority = $path === '' ? '1.0' : '0.7';
    echo '  <url><loc>' . e(seo_url($path)) . '</loc><changefreq>weekly</changefreq><priority>' . $priority . "</priority></url>\n";
}
echo "</urlset>\n";
