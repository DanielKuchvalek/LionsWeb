<?php
declare(strict_types=1);

function site(string $key, $default = null)
{
    return $GLOBALS['SITE'][$key] ?? $default;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function detect_base_url(): string
{
    if (site('base_url') !== null) {
        return rtrim((string) site('base_url'), '/');
    }
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $root = realpath(ROOT) ?: ROOT;
    if ($docRoot !== '' && str_starts_with($root, $docRoot)) {
        return rtrim(str_replace('\\', '/', substr($root, strlen($docRoot))), '/');
    }
    return '';
}

/** Interní odkaz, např. url('lions-nabor/') */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Absolutní adresa (pro odkazy v e-mailech). Lze pevně nastavit v config/site.php → 'site_url'. */
function abs_url(string $path): string
{
    if (preg_match('~^https?://~', $path)) {
        return $path;
    }
    $root = site('site_url');
    if (!$root) {
        // Hlavičku Host posílá prohlížeč (i útočník) – použijeme ji jen, když je na seznamu povolených adres
        $host = strtolower(preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
        $allowed = array_map('strtolower', (array) site('hosts', []));
        $ok = is_local_request() || in_array(preg_replace('/:\d+$/', '', $host), $allowed, true);
        if (!$ok) {
            $host = $allowed[0] ?? 'localhost';
        }
        $root = (is_https() ? 'https://' : 'http://') . $host;
    } else {
        $root = preg_replace('~(https?://[^/]+).*~', '$1', rtrim((string) $root, '/'));
    }
    return $root . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . ltrim($path, '/')) . $v;
}

function media_manifest(): array
{
    static $manifest = null;
    if ($manifest === null) {
        $f = ROOT . '/assets/media/manifest.json';
        $manifest = is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
    }
    return $manifest;
}

/**
 * Obrázek/video z původního webu (wp-content/uploads/...). Vrací optimalizovanou verzi
 * z assets/media podle manifestu (PNG bez průhlednosti převedené na JPG apod.).
 */
function media(string $rel): string
{
    $manifest = media_manifest();
    $rel = ltrim($rel, '/');
    $target = $manifest[$rel] ?? $rel;
    $parts = array_map('rawurlencode', explode('/', $target));
    return url('assets/media/' . implode('/', $parts));
}

function media_size(string $rel): array
{
    $manifest = media_manifest();
    $file = ROOT . '/assets/media/' . ($manifest[$rel] ?? $rel);
    $s = is_file($file) ? @getimagesize($file) : false;
    return $s ? [$s[0], $s[1]] : [null, null];
}

/** <img> s rozměry (kvůli CLS) a lazy loadingem */
function img(string $rel, string $alt = '', string $class = '', bool $lazy = true): string
{
    [$w, $h] = media_size($rel);
    return sprintf(
        '<img src="%s" alt="%s"%s%s%s decoding="async">',
        e(media($rel)),
        e($alt),
        $class !== '' ? ' class="' . e($class) . '"' : '',
        $w ? " width=\"$w\" height=\"$h\"" : '',
        $lazy ? ' loading="lazy"' : ''
    );
}

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (BASE_URL !== '' && str_starts_with($path, BASE_URL)) {
        $path = substr($path, strlen(BASE_URL));
    }
    return trim(rawurldecode($path), '/');
}

function is_active(string $menuUrl): bool
{
    return trim($menuUrl, '/') === current_path();
}

/** Česky formátované datum */
function cz_date(DateTimeInterface $d, string $format = 'full'): string
{
    static $days = ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'];
    static $daysShort = ['ne', 'po', 'út', 'st', 'čt', 'pá', 'so'];
    static $months = [1 => 'ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince'];
    $w = (int) $d->format('w');
    return match ($format) {
        'day'   => $days[$w],
        'short' => $daysShort[$w] . ' ' . $d->format('j. n.'),
        'long'  => $d->format('j. ') . $months[(int) $d->format('n')] . ' ' . $d->format('Y'),
        default => $days[$w] . ' ' . $d->format('j. ') . $months[(int) $d->format('n')] . ' ' . $d->format('Y'),
    };
}

function icon(string $name, int $size = 20): string
{
    $paths = [
        'facebook'  => '<path fill="currentColor" d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z"/>',
        'instagram' => '<path fill="currentColor" d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm0 8.2a3.2 3.2 0 1 1 0-6.4 3.2 3.2 0 0 1 0 6.4ZM17.3 5.5a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4ZM21.9 7.9c-.1-1.6-.4-3-1.6-4.2S17.7 2.2 16.1 2.1C14.5 2 9.5 2 7.9 2.1c-1.6.1-3 .4-4.2 1.6S2.2 6.3 2.1 7.9C2 9.5 2 14.5 2.1 16.1c.1 1.6.4 3 1.6 4.2s2.6 1.5 4.2 1.6c1.6.1 6.6.1 8.2 0 1.6-.1 3-.4 4.2-1.6s1.5-2.6 1.6-4.2c.1-1.6.1-6.6 0-8.2Zm-2.1 10a3.3 3.3 0 0 1-1.9 1.9c-1.3.5-4.4.4-5.9.4s-4.6.1-5.9-.4a3.3 3.3 0 0 1-1.9-1.9c-.5-1.3-.4-4.4-.4-5.9s-.1-4.6.4-5.9a3.3 3.3 0 0 1 1.9-1.9C7.4 3.7 10.5 3.8 12 3.8s4.6-.1 5.9.4a3.3 3.3 0 0 1 1.9 1.9c.5 1.3.4 4.4.4 5.9s.1 4.6-.4 5.9Z"/>',
        'youtube'   => '<path fill="currentColor" d="M23 7.2a3 3 0 0 0-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 0 0 1 7.2 31 31 0 0 0 .5 12a31 31 0 0 0 .5 4.8 3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .5-4.8 31 31 0 0 0-.5-4.8ZM9.8 15V9l5.8 3-5.8 3Z"/>',
        'pin'       => '<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5" fill="none" stroke="currentColor" stroke-width="2"/>',
        'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="m3 7 9 6 9-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'clock'     => '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M8 3v4M16 3v4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'arrow'     => '<path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'external'  => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'chevron'   => '<path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'menu'      => '<path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'close'     => '<path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'dumbbell'  => '<path d="M6 7v10M18 7v10M3 9v6M21 9v6M6 12h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

function team(string $key): ?array
{
    $t = $GLOBALS['TEAMS'][$key] ?? null;
    if ($t) {
        $t['key'] = $key;
    }
    return $t;
}

function render_template(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require ROOT . '/templates/' . $name . '.php';
}

/** Nadpis sekce ve stylu originálu: malý "kicker" + velký nadpis */
function section_title(string $title, ?string $kicker = null, string $class = ''): string
{
    $h = '<div class="section-title ' . e($class) . '">';
    if ($kicker) {
        $h .= '<span class="kicker">' . e($kicker) . '</span>';
    }
    $h .= '<h2>' . e($title) . '</h2></div>';
    return $h;
}

/* =====================================================================
 * Bezpečnostní hlavičky (posílá PHP, takže platí na Apachi i Nginxu)
 * ===================================================================== */

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
}

function is_local_request(): bool
{
    $host = strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    return $host === '' || $host === 'localhost' || str_starts_with($host, '127.') || str_starts_with($host, '192.168.')
        || str_starts_with($host, '10.') || str_ends_with($host, '.local') || str_ends_with($host, '.test');
}

function send_security_headers(): void
{
    if (headers_sent() || PHP_SAPI === 'cli') {
        return;
    }
    header_remove('X-Powered-By');

    // Na ostrém webu vždy HTTPS (lokálně a ve vnitřní síti ne, tam certifikát není)
    if (!is_https() && !is_local_request() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && site('force_https') !== false) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
    // HSTS jen na vlastní doméně klubu – na sdíleném hostingu (endora.site) by platilo i pro jiné weby
    if (is_https() && str_ends_with(strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')), 'lionshandball.cz')) {
        header('Strict-Transport-Security: max-age=31536000');
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    // Odkud smí stránka načítat obsah. Skripty 'unsafe-inline' + https: kvůli vlastním widgetům
    // vkládaným v administraci (Sportlyzer apod.); vložené rámy jen přes https.
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data: https:; font-src 'self'; media-src 'self'; connect-src 'self'; frame-src https:; "
        . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'" . (is_https() ? '; upgrade-insecure-requests' : ''));
}

/** Odkaz zadaný v administraci – povolí jen http(s), mailto, tel a adresy v rámci webu (žádné javascript: apod.). */
function safe_url(?string $href): string
{
    $href = trim((string) $href);
    if (preg_match('~[\x00-\x20\x7f\\\\]~', $href)) {   // prohlížeč by mezery/tabulátory a \ tiše vyhodil ("java\tscript:")
        return '#';
    }
    if ($href === '' || preg_match('~^(https?://|mailto:|tel:|/(?!/)|#|\?)~i', $href) || !preg_match('~^[a-z][a-z0-9+.\-]*:~i', $href) && !str_starts_with($href, '//')) {
        return $href === '' ? '#' : $href;
    }
    return '#';
}
