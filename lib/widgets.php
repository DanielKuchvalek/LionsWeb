<?php
declare(strict_types=1);

/* =====================================================================
 * SPORTLYZER
 * ===================================================================== */

function sportlyzer_url(string $action, array $params = []): string
{
    $s = site('sportlyzer');
    if ($action === 'team') {
        return $s['club_url'] . '?' . http_build_query(['action' => 'teamWidget', 'lang' => $s['lang']] + $params);
    }
    $query = ['page' => 'club/widget', 'action' => $action] + $params + ['seed' => $s['seed'], 'lang' => $s['lang']];
    // Sportlyzer vyžaduje hvězdičku v seedu nezakódovanou
    return 'https://app.sportlyzer.com/?' . str_replace('%2A', '*', http_build_query($query));
}

/**
 * Vložený obsah cizí služby. Iframe vytvoří až JavaScript, a to jen se souhlasem
 * návštěvníka s danou službou – do té doby je vidět zástupný box.
 */
function embed_iframe(string $src, int $height, string $title, string $class = ''): string
{
    $service = consent_service_for($src);
    return sprintf(
        '<div class="embed %s" style="--h:%dpx" data-consent="%s" data-src="%s" data-title="%s">%s<noscript><a href="%s" target="_blank" rel="noopener">%s – otevřít</a></noscript></div>',
        e($class . (consent_required() ? '' : ' has-consent')), $height, e($service), e($src), e($title), consent_placeholder($service), e($src), e($title)
    );
}

function sportlyzer_calendar(?int $group = null, int $height = 650): string
{
    $p = $group ? ['mode' => 'list', 'groups' => $group, 'period' => 'agenda', 'event' => ''] : [];
    return embed_iframe(sportlyzer_url('calendar', $p), $height, 'Sportlyzer kalendář', 'embed--sportlyzer');
}

function sportlyzer_team(int $group, int $height = 800): string
{
    $url = sportlyzer_url('team', ['groupId' => $group]);
    $info = sportlyzer_team_info($url);
    if ($info === null) {   // Sportlyzer teď neodpovídá – pevná výška
        return embed_iframe($url, $height, 'Sportlyzer tým', 'embed--sportlyzer');
    }
    // výška podle počtu hráčů; když skupina nemá fotku, prázdnou plochu nahoře ořízneme
    return '<div class="slteam' . ($info['photo'] ? '' : ' slteam--nophoto') . '" style="--rows:' . $info['rows'] . '">'
        . embed_iframe($url, $height, 'Sportlyzer tým', 'embed--sportlyzer')
        . '</div>';
}

/**
 * Zjistí z widgetu Sportlyzeru počet hráčů a jestli má skupina fotku (výsledek se pamatuje 6 hodin).
 * Vrací ['rows' => int, 'photo' => bool] nebo null, když se nepodařilo načíst.
 */
function sportlyzer_team_info(string $url): ?array
{
    $file = STORAGE . '/cache/sl_team_' . md5($url) . '.json';
    if (is_file($file) && time() - filemtime($file) < 6 * 3600) {
        $c = json_decode((string) @file_get_contents($file), true);
        return is_array($c) && isset($c['rows']) ? $c : null;
    }
    $html = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_USERAGENT => 'LIONS web']);
        $body = curl_exec($ch);
        if (is_string($body) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200 && str_contains($body, 'team-header')) {
            $html = $body;
        }
    }
    $info = $html === null ? ['fail' => true] : [
        'rows'  => max(0, substr_count($html, 'class="avatar"')),
        'photo' => !str_contains($html, 'picture_placeholder'),
    ];
    @mkdir(dirname($file), 0775, true);
    @file_put_contents($file, json_encode($info), LOCK_EX);
    if ($html === null) {
        @touch($file, time() - 6 * 3600 + 1800);   // neúspěch zkusit znovu za 30 minut
        return null;
    }
    return $info;
}

function sportlyzer_register(int $height = 150): string
{
    return embed_iframe(sportlyzer_url('register'), $height, 'Sportlyzer registrace', 'embed--sportlyzer embed--register');
}

/** Libovolný widget z konfigurace: ['iframe' => url, 'height' => 600] nebo ['html' => '<script…>'] */
function custom_widget(array $w): string
{
    $out = '';
    if (!empty($w['title'])) {
        $out .= section_title($w['title'], $w['kicker'] ?? null);
    }
    if (!empty($w['iframe'])) {
        $out .= embed_iframe($w['iframe'], (int) ($w['height'] ?? 600), $w['title'] ?? 'Widget');
    } elseif (!empty($w['html'])) {
        // vložený kód se spustí až po souhlasu (může načítat cizí služby)
        $out .= '<div class="embed embed--html" data-consent="external"><template>' . $w['html'] . '</template>' . consent_placeholder('external') . '</div>';
    }
    return $out;
}

/* =====================================================================
 * FACEBOOK – oficiální Page Plugin
 * ===================================================================== */

function facebook_feed(string $key, int $height = 720): string
{
    $p = site('facebook_pages')[$key] ?? null;
    if (!$p) {
        return '';
    }
    $head = '<a class="fb-card__head" href="' . e(safe_url($p['url'])) . '" target="_blank" rel="noopener">' . icon('facebook', 22) . '<span>' . e($p['name']) . '</span>' . icon('external', 16) . '</a>';

    // Příspěvky přes oficiální Graph API (když je v administraci vložený klíč stránky):
    // načte je server, zobrazí se v designu webu, rychle, bez cookies a bez zamrzání.
    $posts = fb_posts($key);
    if ($posts) {
        $out = '<div class="fb-card">' . $head . '<div class="fb-posts" style="--h:' . $height . 'px">';
        foreach ($posts as $post) {
            $out .= '<a class="fb-post" href="' . e(safe_url($post['url'])) . '" target="_blank" rel="noopener">';
            if ($post['img']) {
                $out .= '<img src="' . e(safe_url($post['img'])) . '" alt="" loading="lazy" referrerpolicy="no-referrer">';
            }
            $out .= '<div class="fb-post__body"><time datetime="' . e($post['time']) . '">' . e(cz_date_short($post['time'])) . '</time>';
            if ($post['text'] !== '') {
                $out .= '<p>' . nl2br(e(mb_strimwidth($post['text'], 0, 320, '…'))) . '</p>';
            }
            $out .= '<span class="fb-post__more">Zobrazit na Facebooku ' . icon('external', 13) . '</span></div></a>';
        }
        return $out . '</div></div>';
    }

    // Bez klíče: jen karta s odkazem. (Vložený plugin Facebooku v dnešních prohlížečích často
    // nezobrazí příspěvky – blokují mu cookies třetích stran – a umí zamrznout celou stránku.)
    return '<div class="fb-card">' . $head
        . '<div class="fb-card__body"><div class="fb-card__intro">'
        . '<img src="' . e(media(site('logo'))) . '" alt="" width="56" height="77" loading="lazy">'
        . '<p><strong>' . e($p['name']) . '</strong>Aktuality, fotky a výsledky najdete na našem Facebooku.</p>'
        . '<div class="fb-card__btns"><a class="btn btn--red btn--sm" href="' . e(safe_url($p['url'])) . '" target="_blank" rel="noopener">' . icon('facebook', 16) . ' Otevřít na Facebooku</a></div>'
        . '</div></div></div>';
}

/** „12. 9. 2026“ z ISO data */
function cz_date_short(string $iso): string
{
    try {
        $d = (new DateTimeImmutable($iso))->setTimezone(new DateTimeZone('Europe/Prague'));
        return $d->format('j. n. Y');
    } catch (Throwable) {
        return '';
    }
}

/**
 * Poslední příspěvky facebookové stránky přes Graph API (klíč stránky = „token“ v administraci).
 * Výsledek se pamatuje 30 minut; když Facebook neodpoví, použije se poslední uložená verze.
 * Vrací [['text','img','url','time'], …] nebo null (bez klíče / nic k zobrazení).
 */
function fb_posts(string $key, int $limit = 5): ?array
{
    $token = (string) (site('facebook_pages')[$key]['token'] ?? '');
    if ($token === '') {
        return null;
    }
    $file = STORAGE . '/cache/fb_' . md5($key . '|' . $token) . '.json';
    $cached = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
    if (is_array($cached) && time() - filemtime($file) < 1800) {
        return $cached ?: null;
    }
    $posts = null;
    if (function_exists('curl_init')) {
        $url = 'https://graph.facebook.com/v21.0/me/posts?' . http_build_query([
            'fields' => 'message,created_time,permalink_url,full_picture,is_published',
            'limit' => $limit + 3, 'access_token' => $token,
        ]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6, CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]);
        $body = curl_exec($ch);
        $data = is_string($body) ? json_decode($body, true) : null;
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200 && isset($data['data']) && is_array($data['data'])) {
            $posts = [];
            foreach ($data['data'] as $d) {
                if (($d['is_published'] ?? true) === false || empty($d['permalink_url'])) continue;
                if (empty($d['message']) && empty($d['full_picture'])) continue;
                $posts[] = ['text' => (string) ($d['message'] ?? ''), 'img' => (string) ($d['full_picture'] ?? ''),
                            'url' => (string) $d['permalink_url'], 'time' => (string) ($d['created_time'] ?? '')];
                if (count($posts) >= $limit) break;
            }
        } else {
            error_log('LIONS facebook ' . $key . ': ' . (is_array($data) ? ($data['error']['message'] ?? 'HTTP ' . curl_getinfo($ch, CURLINFO_HTTP_CODE)) : 'bez odpovědi'));
        }
    }
    if ($posts === null) {                     // Facebook teď neodpovídá / neplatný klíč → poslední známé příspěvky
        if (!is_array($cached)) {
            @mkdir(dirname($file), 0775, true);
            @file_put_contents($file, '[]', LOCK_EX);
        }
        @touch($file, time() - 1800 + 300);   // zkusit znovu za 5 minut, ne při každém načtení stránky
        return $cached ?: null;
    }
    @mkdir(dirname($file), 0775, true);
    @file_put_contents($file, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $posts ?: null;
}

/* =====================================================================
 * UTKÁNÍ A TABULKY (data z handball.cz)
 * ===================================================================== */

function team_logo(?string $src, string $name, string $class = 'crest'): string
{
    if (csh_is_lions($name)) {
        return '<img class="' . e($class) . '" src="' . e(media(site('logo'))) . '" alt="LIONS" loading="lazy">';
    }
    if ($src) {
        return '<img class="' . e($class) . '" src="' . e($src) . '" alt="" loading="lazy" data-initials="' . e(initials($name)) . '">';
    }
    return '<span class="' . e($class) . ' crest--text">' . e(initials($name)) . '</span>';
}

function initials(string $name): string
{
    $words = preg_split('/[\s\-\.]+/u', preg_replace('/\b(TJ|SK|HC|HK|DHC|DHK|HBC|SHK|SC|Sokol|z\.s\.|Házená)\b/u', '', $name) ?? $name, -1, PREG_SPLIT_NO_EMPTY);
    $i = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $i .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    return $i ?: '?';
}

/** Štítek týmu vypsaný celými slovy: „ŽÁCI STD“ → „ŽÁCI STARŠÍ“, „DOROST MLD“ → „DOROSTENCI MLADŠÍ“ */
function team_tag(string $tag): string
{
    $tag = preg_replace('/\bDOROST(?=\s+(STD|MLD)\.?(\s|$))/iu', 'DOROSTENCI', $tag) ?? $tag;
    $tag = preg_replace('/\bSTD\b\.?/iu', 'STARŠÍ', $tag) ?? $tag;
    return preg_replace('/\bMLD\b\.?/iu', 'MLADŠÍ', $tag) ?? $tag;
}

function team_display_name(string $name): string
{
    return csh_is_lions($name) ? 'LIONS TEAM' : $name;
}

function match_score(array $m): string
{
    if ($m['state'] === 'upcoming' || $m['home_score'] === null) {
        return '<span class="score score--time">' . ($m['start'] ? e($m['start']->format('H:i')) : '–') . '</span>';
    }
    $cls = 'score' . ($m['state'] === 'live' ? ' score--live' : '') . ($m['result'] ? ' score--' . strtolower($m['result']) : '');
    return '<span class="' . $cls . '" data-score>' . (int) $m['home_score'] . '<i>:</i>' . (int) $m['away_score'] . '</span>';
}

function result_badge(?string $r): string
{
    return match ($r) {
        'W' => '<span class="badge badge--w" title="výhra">V</span>',
        'L' => '<span class="badge badge--l" title="prohra">P</span>',
        'D' => '<span class="badge badge--d" title="remíza">R</span>',
        default => '',
    };
}

/** Kompaktní karta utkání s odpočtem (homepage) */
function match_card(array $m): string
{
    $t = $m['team_key'] ? team($m['team_key']) : null;
    $tag = team_tag((string) ($t['home_tag'] ?? ''));
    $scored = $m['state'] !== 'upcoming' && $m['home_score'] !== null;
    ob_start(); ?>
    <article class="match-card<?= $m['state'] === 'live' ? ' is-live' : '' ?>" data-match="<?= e($m['id']) ?>">
      <header class="match-card__top">
        <?php if ($tag): ?><a class="pill" href="<?= e(url($t['key'] . '/')) ?>"><?= e($tag) ?></a><?php endif; ?>
        <span class="match-card__comp"><?= e($m['comp_name']) ?></span>
      </header>
      <div class="match-card__body">
        <div class="match-card__teams">
          <?php foreach (['home', 'away'] as $side): ?>
            <div class="match-card__team<?= csh_is_lions($m[$side]) ? ' is-lions' : '' ?>">
              <?= team_logo($m[$side . '_logo'], $m[$side], 'crest crest--md') ?>
              <strong><?= e(team_display_name($m[$side])) ?></strong>
              <?php if ($scored): ?><b class="match-card__score" data-score-<?= $side ?>><?= (int) $m[$side . '_score'] ?></b><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if ($m['start']): ?>
          <div class="match-card__when">
            <small><?= e(cz_date($m['start'], 'day')) ?></small>
            <b><?= e($m['start']->format('j. n.')) ?></b>
            <strong><?= e($m['start']->format('H:i')) ?></strong>
          </div>
        <?php endif; ?>
      </div>
      <footer class="match-card__foot">
        <?php if ($m['state'] === 'live'): ?>
          <span class="live-dot">PRÁVĚ SE HRAJE</span>
        <?php elseif ($m['start']): ?>
          <span class="countdown" data-countdown="<?= e($m['start']->format(DATE_ATOM)) ?>">za <b data-d>00</b>d <b data-h>00</b>h <b data-m>00</b>m <b data-s>00</b>s</span>
        <?php endif; ?>
        <a class="match-card__link" href="<?= e($m['url']) ?>" target="_blank" rel="noopener">Detail utkání <?= icon('external', 13) ?></a>
      </footer>
    </article>
    <?php return (string) ob_get_clean();
}

/** Řádek utkání v seznamu (rozpis / výsledky) */
function match_row(array $m, bool $showTeam = false, array $seasons = []): string
{
    $t = ($showTeam && $m['team_key']) ? team($m['team_key']) : null;
    $round = $seasons[$m['season_id']] ?? null;
    ob_start(); ?>
    <a class="match-row<?= $m['state'] === 'live' ? ' is-live' : '' ?><?= $m['is_lions'] ? '' : ' is-other' ?>" href="<?= e($m['url']) ?>" target="_blank" rel="noopener" data-match="<?= e($m['id']) ?>">
      <span class="match-row__date">
        <?php if ($m['start']): ?>
          <b><?= e($m['start']->format('j. n.')) ?></b><small><?= e(cz_date($m['start'], 'day')) ?></small>
        <?php else: ?><b>TBA</b><?php endif; ?>
      </span>
      <span class="match-row__body">
        <?php if ($t): ?><span class="pill pill--sm"><?= e(team_tag((string) $t['home_tag'])) ?></span><?php endif; ?>
        <span class="match-row__teams">
          <span class="match-row__team match-row__team--home<?= csh_is_lions($m['home']) ? ' is-lions' : '' ?>"><span><?= e(team_display_name($m['home'])) ?></span><?= team_logo($m['home_logo'], $m['home'], 'crest crest--sm') ?></span>
          <?= match_score($m) ?>
          <span class="match-row__team<?= csh_is_lions($m['away']) ? ' is-lions' : '' ?>"><?= team_logo($m['away_logo'], $m['away'], 'crest crest--sm') ?><span><?= e(team_display_name($m['away'])) ?></span></span>
        </span>
        <span class="match-row__venue"><?= icon('pin', 13) ?><?= e($m['venue'] ?: '—') ?><?= $round && !preg_match('/^\d+\. kolo$|^(podzim|jaro)/iu', $round) ? ' · turnaj ' . e($round) : '' ?><?= $showTeam ? ' · ' . e($m['comp_name']) : '' ?></span>
      </span>
      <span class="match-row__end"><?= $m['state'] === 'live' ? '<span class="live-dot">LIVE</span>' : result_badge($m['result']) ?></span>
    </a>
    <?php return (string) ob_get_clean();
}

function standings_table(array $rows): string
{
    if (!$rows) {
        return '<p class="muted">Tabulka zatím není k dispozici.</p>';
    }
    ob_start(); ?>
    <div class="table-wrap">
      <table class="standings">
        <thead><tr><th>#</th><th class="t-left">Tým</th><th title="Zápasy">Z</th><th title="Výhry">V</th><th title="Remízy">R</th><th title="Prohry">P</th><th class="hide-sm">Skóre</th><th class="hide-sm" title="Rozdíl skóre">+/-</th><th>Body</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr class="<?= $r['lions'] ? 'is-lions' : '' ?>">
            <td><span class="pos"><?= e($r['pos'] ?? '–') ?></span></td>
            <td class="t-left"><span class="standings__team"><?= team_logo($r['logo'], $r['team'], 'crest crest--xs') ?><?= e($r['team']) ?></span></td>
            <td><?= $r['played'] ?></td><td><?= $r['won'] ?></td><td><?= $r['tied'] ?></td><td><?= $r['lost'] ?></td>
            <td class="hide-sm"><?= $r['gf'] ?>:<?= $r['ga'] ?></td>
            <td class="hide-sm"><?= ($r['gf'] - $r['ga']) > 0 ? '+' : '' ?><?= $r['gf'] - $r['ga'] ?></td>
            <td><b><?= $r['points'] ?></b></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php return (string) ob_get_clean();
}

function csh_unavailable(string $link = 'https://www.handball.cz/'): string
{
    return '<div class="notice">Data ze systému Českého svazu házené se nepodařilo načíst. '
        . '<a href="' . e($link) . '" target="_blank" rel="noopener">Zobrazit na handball.cz ' . icon('external', 14) . '</a></div>';
}
