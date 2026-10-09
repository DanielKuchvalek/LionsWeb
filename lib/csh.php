<?php
declare(strict_types=1);

/**
 * Napojení na informační systém Českého svazu házené (handball.cz).
 *
 * Používá stejné veřejné JSON API, ze kterého se plní samotný web handball.cz:
 *   /api/public/competition/{slug}                     – soutěž + její části
 *   /api/public/competition/{slug}/competition-matches – všechna utkání soutěže
 *   /api/public/competition-part-teams/{partId}        – tabulka části soutěže
 *
 * Odpovědi se ukládají do storage/cache. Když handball.cz neodpovídá, použije se
 * poslední uložená verze, takže web nikdy nespadne kvůli výpadku svazového systému.
 */

const CSH_STATE_SCHEDULED = 1;
const CSH_STATE_LIVE      = 2;
const CSH_STATE_FINISHED  = 4;

function csh_cfg(string $key)
{
    return site('csh')[$key] ?? null;
}

/**
 * Stáhne JSON z API s cache. $ttl v sekundách.
 *
 * Ochrany, aby pomalý nebo nedostupný handball.cz nezpomalil web:
 *  - čerstvá cache → vrátí se hned bez dotazu na svaz,
 *  - prošlou cache obnovuje vždy jen jeden požadavek (zámek), ostatní dostanou starou verzi,
 *  - po chybě spojení se další minutu na svaz vůbec nevolá ("jistič"), použije se uložená verze.
 */
function csh_fetch(string $path, ?int $ttl = null): ?array
{
    $ttl ??= (int) csh_cfg('cache_ttl');
    $dir = STORAGE . '/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $file = $dir . '/csh_' . md5($path) . '.json';
    $readCache = static function () use ($file): ?array {
        $d = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
        return is_array($d) ? $d : null;
    };

    $age = is_file($file) ? time() - (int) @filemtime($file) : PHP_INT_MAX;
    if ($age < $ttl && ($cached = $readCache()) !== null) {
        return $cached;
    }

    // jistič: svaz nedávno neodpovídal → nezkoušet znovu
    $breaker = $dir . '/csh_down';
    if (is_file($breaker) && time() - (int) @filemtime($breaker) < 60) {
        return $readCache();
    }

    // obnovu dělá jen jeden požadavek najednou
    $lock = @fopen($file . '.lock', 'c');
    $haveLock = $lock && flock($lock, LOCK_EX | LOCK_NB);
    if (!$haveLock && ($cached = $readCache()) !== null) {
        if ($lock) fclose($lock);
        return $cached;
    }
    if (!$haveLock && $lock) {
        flock($lock, LOCK_EX); // bez cache nezbývá než počkat na výsledek
        $cached = $readCache();
        if ($cached !== null && time() - (int) @filemtime($file) < $ttl) {
            flock($lock, LOCK_UN);
            fclose($lock);
            return $cached;
        }
    }

    try {
        $data = csh_http_get(csh_cfg('base') . $path);
        if ($data !== null) {
            $tmp = $file . '.' . getmypid() . '.tmp';
            if (@file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE)) !== false) {
                @rename($tmp, $file);
            }
            @unlink($breaker);
            return $data;
        }
        @touch($breaker);
        error_log('LIONS: handball.cz neodpověděl pro ' . $path);
        return $readCache();
    } finally {
        if ($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}

function csh_http_get(string $url): ?array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => min(6, (int) csh_cfg('timeout')),
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_USERAGENT      => 'LIONS-Handball-Web/1.0 (+https://www.lionshandball.cz)',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_ENCODING       => '',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => [
            'timeout' => (int) csh_cfg('timeout'),
            'header'  => "Accept: application/json\r\nUser-Agent: LIONS-Handball-Web/1.0\r\n",
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        $code = $body === false ? 0 : 200;
    }
    if ($body === false || $code !== 200) {
        return null;
    }
    $json = json_decode((string) $body, true);
    return is_array($json) ? $json : null;
}

/** Veřejná adresa na handball.cz */
function csh_competition_url(array $comp): string
{
    return csh_cfg('base') . '/souteze/' . $comp['sex'] . '/' . $comp['slug'];
}

function csh_competition(array $comp): ?array
{
    $r = csh_fetch('/api/public/competition/' . rawurlencode($comp['slug']), 6 * 3600);
    return $r['data'] ?? null;
}

/** Logo týmu – přes vlastní server (api/logo.php), aby prohlížeč nekontaktoval handball.cz */
function csh_logo(?string $path): ?string
{
    if (!$path) {
        return null;
    }
    if (preg_match('~^https?://[^/]+(/.*)$~', $path, $m)) {
        $path = $m[1];
    }
    return url('api/logo.php') . '?p=' . rawurlencode(ltrim($path, '/'));
}

function csh_is_lions(?string $name): bool
{
    return $name !== null && mb_strtolower(trim($name)) === mb_strtolower((string) csh_cfg('team_name'));
}

/**
 * Všechna utkání soutěže (normalizovaná). Když se v soutěži zrovna hraje
 * nebo brzy začne zápas, zkrátí se platnost cache na 'live_ttl'.
 */
function csh_matches(array $comp): array
{
    static $memo = [];
    $key = $comp['slug'] . '|' . ($comp['team_key'] ?? '');
    return $memo[$key] ??= csh_matches_load($comp);
}

function csh_matches_load(array $comp): array
{
    $path = '/api/public/competition/' . rawurlencode($comp['slug']) . '/competition-matches';
    $raw = csh_fetch($path);
    if (!$raw) {
        return [];
    }
    $matches = [];
    foreach ($raw['data'] ?? [] as $row) {
        $m = $row['data'] ?? $row;
        if (empty($m['id'])) {
            continue;
        }
        $matches[] = csh_normalize_match($m, $comp);
    }

    // "Horká" soutěž = něco se hraje → příště obnovit dřív
    $now = time();
    foreach ($matches as $m) {
        if ($m['start'] && ($m['state'] === 'live'
            || ($m['state'] === 'upcoming' && abs($m['start']->getTimestamp() - $now) < 3 * 3600)
            || ($m['state'] === 'upcoming' && $m['start']->getTimestamp() < $now && $now - $m['start']->getTimestamp() < 4 * 3600))) {
            $file = STORAGE . '/cache/csh_' . md5($path) . '.json';
            $ttl = (int) csh_cfg('cache_ttl');
            $live = (int) csh_cfg('live_ttl');
            if (is_file($file) && (time() - filemtime($file)) < $ttl - $live) {
                @touch($file, time() - $ttl + $live);
            }
            break;
        }
    }

    usort($matches, fn($a, $b) => ($a['ts'] <=> $b['ts']));
    return $matches;
}

function csh_normalize_match(array $m, array $comp): array
{
    $start = null;
    if (!empty($m['matchStart'])) {
        try {
            // POZOR: IS ČSH posílá čas s příznakem "Z" (UTC), ale ve skutečnosti je to český místní čas
            // (web handball.cz ho zobrazuje bez převodu). Příznak časového pásma proto ignorujeme
            // a čas bereme jako Europe/Prague – jinak by byl o 1 h (zima) / 2 h (léto) posunutý.
            $local = preg_replace('/(Z|[+-]\d{2}:?\d{2})$/', '', (string) $m['matchStart']);
            $start = new DateTimeImmutable($local, new DateTimeZone('Europe/Prague'));
        } catch (Exception $e) {
            $start = null;
        }
    }
    $stateId = (int) ($m['competitionMatchStateId'] ?? 0);
    $state = match (true) {
        $stateId === CSH_STATE_LIVE     => 'live',
        $stateId >= 3                   => 'finished',
        default                         => 'upcoming',
    };
    $home = trim((string) ($m['homeTeamName'] ?? ''));
    $away = trim((string) ($m['guestTeamName'] ?? ''));
    $lionsHome = csh_is_lions($home);
    $lionsAway = csh_is_lions($away);
    $hs = $m['homeTeamScore'];
    $as = $m['guestTeamScore'];

    $result = null;
    if ($state === 'finished' && ($lionsHome || $lionsAway) && $hs !== null && $as !== null) {
        $our = $lionsHome ? $hs : $as;
        $their = $lionsHome ? $as : $hs;
        $result = $our > $their ? 'W' : ($our < $their ? 'L' : 'D');
    }

    return [
        'id'         => $m['id'],
        'start'      => $start,
        'ts'         => $start ? $start->getTimestamp() : PHP_INT_MAX,
        'state'      => $state,
        'home'       => $home,
        'away'       => $away,
        'home_logo'  => csh_logo($m['homeTeamClubPhotoUrl'] ?? null),
        'away_logo'  => csh_logo($m['guestTeamClubPhotoUrl'] ?? null),
        'home_score' => $hs,
        'away_score' => $as,
        'forfeit'    => !empty($m['isForfeited']),
        'venue'      => trim((string) ($m['sportFieldName'] ?? '')),
        'code'       => $m['compoundCode'] ?? '',
        'comp_name'  => $m['competitionName'] ?? '',
        'comp_slug'  => $comp['slug'],
        'season_id'  => $m['competitionSeasonId'] ?? null,
        'lions_home' => $lionsHome,
        'lions_away' => $lionsAway,
        'is_lions'   => $lionsHome || $lionsAway,
        'result'     => $result,
        'url'        => csh_competition_url($comp) . '/detail/zapas/' . rawurlencode($m['id']),
        'team_key'   => $comp['team_key'] ?? null,
    ];
}

/** Tabulka první (aktuální) části soutěže */
function csh_standings(array $comp): array
{
    $c = csh_competition($comp);
    if (!$c || empty($c['competitionParts'])) {
        return [];
    }
    $parts = $c['competitionParts'];
    usort($parts, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
    $part = $parts[0];

    $raw = csh_fetch('/api/public/competition-part-teams/' . rawurlencode($part['id']));
    $rows = [];
    foreach ($raw['data'] ?? [] as $r) {
        $r = $r['data'] ?? $r;
        $rows[] = [
            'pos'    => $r['standing'],
            'team'   => trim((string) $r['competitionTeamName']),
            'logo'   => csh_logo($r['teamClubPhotoUrl'] ?? null),
            'played' => (int) $r['gamesPlayed'],
            'won'    => (int) $r['gamesWon'],
            'tied'   => (int) $r['gamesTied'],
            'lost'   => (int) $r['gamesLost'],
            'gf'     => (int) $r['goalsScored'],
            'ga'     => (int) $r['goalsConceded'],
            'points' => (int) $r['points'],
            'lions'  => csh_is_lions($r['competitionTeamName'] ?? ''),
        ];
    }
    usort($rows, fn($a, $b) => ($a['pos'] ?? 999) <=> ($b['pos'] ?? 999));
    return $rows;
}

/** Soutěže týmu obohacené o název ze svazu (prázdné/neaktivní soutěže se vynechají) */
function csh_team_competitions(array $team): array
{
    $out = [];
    foreach ($team['csh'] ?? [] as $comp) {
        $comp['team_key'] = $team['key'];
        $entity = csh_competition($comp);
        if (!$entity || empty($entity['competitionParts'])) {
            continue; // soutěž v této sezóně ještě/už neběží
        }
        $comp['name'] = $entity['name'] ?? $comp['slug'];
        $comp['url'] = csh_competition_url($comp);
        $comp['seasons'] = [];
        foreach ($entity['competitionParts'] as $p) {
            foreach ($p['competitionSeasons'] ?? [] as $s) {
                $comp['seasons'][$s['id']] = $s['name'];
            }
        }
        $out[] = $comp;
    }
    return $out;
}

/** Utkání LIONS v dané soutěži */
function csh_lions_matches(array $comp): array
{
    return array_values(array_filter(csh_matches($comp), fn($m) => $m['is_lions']));
}

/** Všechna utkání LIONS napříč všemi týmy */
function csh_all_lions_matches(): array
{
    static $memo = null;
    if ($memo !== null) {
        return $memo;
    }
    $all = [];
    foreach ($GLOBALS['TEAMS'] as $key => $t) {
        foreach ($t['csh'] ?? [] as $comp) {
            $comp['team_key'] = $key;
            foreach (csh_lions_matches($comp) as $m) {
                $all[$m['id']] = $m;
            }
        }
    }
    $all = array_values($all);
    usort($all, fn($a, $b) => $a['ts'] <=> $b['ts']);
    return $memo = $all;
}

function csh_is_home_venue(array $m): bool
{
    return $m['lions_home'] && mb_stripos($m['venue'], (string) csh_cfg('home_venue')) !== false;
}

/** Nejbližší domácí utkání v LIONS Aréně (+ právě probíhající) */
function csh_upcoming_home(int $limit = 6): array
{
    $now = time();
    $list = array_filter(csh_all_lions_matches(), fn($m) => csh_is_home_venue($m)
        && ($m['state'] === 'live' || ($m['state'] === 'upcoming' && $m['ts'] > $now - 2 * 3600)));
    return array_slice(array_values($list), 0, $limit);
}

/**
 * Nejbližší utkání všech týmů (doma i venku).
 * $days > 0: všechna utkání v příštích $days dnech (celý víkend), aspoň $limit, nejvýš $max.
 */
function csh_upcoming_all(int $limit = 8, int $days = 0, int $max = 30): array
{
    $now = time();
    $list = array_values(array_filter(csh_all_lions_matches(), fn($m) => $m['state'] === 'live'
        || ($m['state'] === 'upcoming' && $m['ts'] > $now - 2 * 3600)));
    return csh_window($list, $limit, $days > 0 ? fn($m) => $m['ts'] <= $now + $days * 86400 : null, $max);
}

/** Poslední výsledky ($days > 0: všechny za posledních $days dní, aspoň $limit, nejvýš $max) */
function csh_latest_results(int $limit = 8, int $days = 0, int $max = 30): array
{
    $since = time() - $days * 86400;
    $list = array_filter(csh_all_lions_matches(), fn($m) => $m['state'] === 'finished' && $m['home_score'] !== null);
    $list = array_reverse(array_values($list));
    return csh_window($list, $limit, $days > 0 ? fn($m) => $m['ts'] >= $since : null, $max);
}

/** Prvních $limit položek + všechny další, které splní $inWindow (seznam je seřazený), nejvýš $max */
function csh_window(array $list, int $limit, ?callable $inWindow, int $max): array
{
    if ($inWindow === null) {
        return array_slice($list, 0, $limit);
    }
    $out = [];
    foreach ($list as $i => $m) {
        if (count($out) >= $max || ($i >= $limit && !$inWindow($m))) {
            break;
        }
        $out[] = $m;
    }
    return $out;
}

function csh_any_live(array $matches): bool
{
    foreach ($matches as $m) {
        if ($m['state'] === 'live') {
            return true;
        }
    }
    return false;
}
