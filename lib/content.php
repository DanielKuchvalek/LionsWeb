<?php
declare(strict_types=1);

/**
 * Upravitelný obsah webu.
 *
 * Výchozí hodnoty jsou v config/*.php (původní texty z lionshandball.cz).
 * Co se změní v administraci, uloží se do storage/content/*.json a má přednost.
 */

function content_dir(): string
{
    $dir = STORAGE . '/content';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function json_read(string $file, $default = [])
{
    if (!is_file($file)) {
        return $default;
    }
    $data = json_decode((string) file_get_contents($file), true);
    return $data === null ? $default : $data;
}

/** Atomický zápis JSON (nejdřív do dočasného souboru, pak přejmenování) */
function json_write(string $file, $data): bool
{
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    $ok = file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
    return $ok && rename($tmp, $file);
}

/** Nastavení webu = config/site.php + úpravy z administrace */
function load_site(): array
{
    $site = require ROOT . '/config/site.php';
    $over = json_read(content_dir() . '/site.json');
    foreach ($over as $k => $v) {
        if (in_array($k, ['partners', 'results_links'], true) && site_is_old_default($v)) {
            continue;   // uložená jen původní výchozí verze → platí aktuální config/site.php
        }
        $site[$k] = is_array($v) && isset($site[$k]) && is_array($site[$k]) && !array_is_list($site[$k])
            ? array_replace($site[$k], $v)
            : $v;
    }
    return $site;
}

/**
 * Admin dřív ukládal partnery a odkazy „ROZPISY A VÝSLEDKY“ do site.json i neupravené.
 * Pokud tam je přesně tehdejší výchozí verze (sezóna 25/26), ignoruje se.
 */
function site_is_old_default($value): bool
{
    $hash = md5((string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    return in_array($hash, ['153645f730282d0ede7b45dece4beda1', '177ac8bffb51618abf24115d0300a176'], true);
}

/** Týmy = storage/content/teams.json, pokud existuje, jinak config/teams.php */
function load_teams(): array
{
    $file = content_dir() . '/teams.json';
    $teams = is_file($file) ? json_read($file) : (require ROOT . '/config/teams.php');
    $defaultMenu = ['pripravka', 'minizactvo', 'zakyne-mladsi', 'zaci-mladsi', 'zakyne-starsi', 'zaci-starsi', 'dorostenky-mladsi', 'dorost-mladsi', 'zeny', 'muzi'];
    foreach ($teams as $key => &$t) {
        $t += [
            'title' => $key, 'label' => '', 'home_tag' => '', 'photo' => null, 'heading' => null, 'info' => [],
            'training_title' => '', 'training' => [], 'notes' => [], 'cta' => false, 'register' => false,
            'sportlyzer' => null, 'jersey' => null, 'partners' => [], 'csh' => [], 'widgets' => [],
            'in_menu' => in_array($key, $defaultMenu, true), 'menu_label' => null,
        ];
        // starší formát: jeden partner {url, img, alt} → seznam [web, logo, název]
        if (!empty($t['partner']['img']) && !$t['partners']) {
            $t['partners'] = [[$t['partner']['url'] ?? '', $t['partner']['img'], $t['partner']['alt'] ?? '']];
        }
        unset($t['partner']);
    }
    unset($t);
    return $teams;
}

/** Registr textových/obrázkových bloků (definice + výchozí hodnoty) */
function blocks_registry(): array
{
    static $reg = null;
    return $reg ??= require ROOT . '/config/blocks.php';
}

function blocks_overrides(): array
{
    static $over = null;
    return $over ??= json_read(content_dir() . '/blocks.json');
}

/** Hodnota bloku, např. block('home.about_title') */
function block(string $key): string
{
    $over = blocks_overrides();
    if (array_key_exists($key, $over)) {
        return (string) $over[$key];
    }
    [$page, $field] = explode('.', $key, 2) + [1 => ''];
    return (string) (blocks_registry()[$page]['fields'][$field]['default'] ?? '');
}

/** Seznam hodnot (jedna položka na řádek) */
function block_lines(string $key): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/u', block($key)) ?: []), 'strlen'));
}

/** Text s odstavci (prázdný řádek = nový odstavec, **tučně**) */
function block_html(string $key, string $tag = 'p'): string
{
    return text_html(block($key), $tag);
}

/**
 * Jednoduché formátování textu z administrace:
 *   prázdný řádek = nový odstavec, **tučně**, [text](https://odkaz),
 *   "## Nadpis" = nadpis, řádky začínající "- " = odrážky.
 */
function text_html(string $text, string $tag = 'p'): string
{
    $out = '';
    foreach (preg_split('/\R{2,}/u', trim($text)) ?: [] as $para) {
        $para = trim($para);
        if ($para === '') {
            continue;
        }
        $lines = preg_split('/\R/u', $para);
        if (str_starts_with($para, '## ')) {
            $out .= '<h2>' . inline_html(substr(array_shift($lines), 3)) . '</h2>';
            if (!$lines) continue;
            $para = implode("\n", $lines);
            $lines = preg_split('/\R/u', $para);
        }
        if (count(array_filter($lines, fn($l) => str_starts_with(trim($l), '- '))) === count($lines)) {
            $out .= '<ul>' . implode('', array_map(fn($l) => '<li>' . inline_html(substr(trim($l), 2)) . '</li>', $lines)) . '</ul>';
            continue;
        }
        $out .= "<$tag>" . implode('<br>', array_map('inline_html', $lines)) . "</$tag>";
    }
    return $out;
}

/** Inline formátování jednoho řádku (**tučně**) */
function inline_html(string $text): string
{
    $h = e($text);
    $h = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $h) ?? $h;
    // [text](https://adresa) nebo [text](/stranka/)
    $h = preg_replace_callback('/\[([^\]]+)\]\(((?:https?:\/\/|mailto:|\/)[^)\s]+)\)/u', function ($m) {
        $href = html_entity_decode($m[2], ENT_QUOTES);
        if (str_starts_with($href, '/') && !str_starts_with($href, '//')) $href = url(ltrim($href, '/'));
        $ext = preg_match('~^https?://~', $href) ? ' target="_blank" rel="noopener"' : '';
        return '<a href="' . e($href) . '"' . $ext . '>' . $m[1] . '</a>';
    }, $h) ?? $h;
    return $h;
}

/** Menu – položka "LIONS TÝMY" se skládá automaticky z týmů označených "v menu" */
function build_menu(): array
{
    $menu = site('menu');
    foreach ($menu as &$item) {
        if (($item['auto'] ?? null) === 'teams') {
            $item['children'] = [];
            foreach ($GLOBALS['TEAMS'] as $key => $t) {
                if (!empty($t['in_menu'])) {
                    $item['children'][] = ['label' => $t['menu_label'] ?: $t['title'], 'url' => $key . '/'];
                }
            }
        }
    }
    return $menu;
}
