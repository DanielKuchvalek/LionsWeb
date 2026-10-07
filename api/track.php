<?php
declare(strict_types=1);

/**
 * Příjem anonymní statistiky z prohlížeče (assets/js/app.js → navigator.sendBeacon).
 * Tělo: JSON {k: "view"|"click", p: stránka, t: cíl/odkud, l: popisek/zařízení, s: sekce}
 * Odpověď je vždy prázdná 204 – návštěvníka nic nezdrží ani nerozbije.
 */
require dirname(__DIR__) . '/bootstrap.php';
require ROOT . '/lib/stats.php';

http_response_code(204);
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    exit;
}
// roboti se nepočítají (správce vynechá už prohlížeč – značka z administrace)
if (preg_match('/bot|crawl|spider|slurp|lighthouse|headless|preview|monitor/i', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
    exit;
}
$raw = (string) file_get_contents('php://input', false, null, 0, 4096);
$d = json_decode($raw, true);
if (!is_array($d)) {
    exit;
}
try {
    if (!rate_limit('track', 600, 600)) {   // ochrana proti zahlcení z jedné adresy
        exit;
    }
    $page = (string) ($d['p'] ?? '/');
    if (!preg_match('~^/[^\s]*$~', $page)) {
        exit;
    }
    stats_record((string) ($d['k'] ?? ''), $page, (string) ($d['t'] ?? ''), (string) ($d['l'] ?? ''), (string) ($d['s'] ?? ''));
} catch (Throwable $e) {
    error_log('LIONS stats: ' . $e->getMessage());
}
