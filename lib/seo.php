<?php
declare(strict_types=1);

/**
 * SEO: kanonické adresy, popisy stránek, sitemap a strukturovaná data (JSON-LD, schema.org).
 * Všechno je jen navíc – když tu cokoli selže, stránka se vykreslí dál bez toho.
 */

/** Veřejné stránky webu (adresa => nastavení). Používá index.php i sitemap.php. */
function site_routes(): array
{
    return [
        ''                                      => ['page' => 'home'],
        'lions-nabor'                           => ['page' => 'nabor',    'title' => 'LIONS NÁBOR',
            'description' => 'LIONS NÁBOR – přidej se k házené v Hostivici. Nábor dětí a mládeže do týmů LIONS Handball.'],
        'kontakt-new'                           => ['page' => 'kontakt',  'title' => 'KONTAKTY',
            'description' => 'Kontakty na LIONS Handball Hostivice – adresa, e-mail a kontaktní formulář.'],
        'sponsors'                              => ['page' => 'partneri', 'title' => 'Partneři',
            'description' => 'Partneři a podporovatelé klubu LIONS Handball Hostivice.'],
        'kup-si-tym'                            => ['page' => 'patron',   'title' => 'KUP SI TÝM',
            'description' => 'Staňte se patronem LIONS týmu – program spolupráce pro partnery a podporovatele klubu LIONS Handball Hostivice.'],
        'mini-gym'                              => ['page' => 'minigym',  'title' => 'LIONS miniGYM BOOKING',
            'description' => 'LIONS miniGYM BOOKING – rezervace termínů v LIONS miniGYM Hostivice.'],
        'mini-gym/zrusit'                       => ['page' => 'minigym-cancel', 'title' => 'Zrušení rezervace miniGYM', 'noindex' => true],
        'lvicata-zs-hostivice'                  => ['page' => 'lvicata',  'title' => 'LVÍČATA … KROUŽEK MÍČOVEK ZŠ Hostivice', 'form' => 'lvicata-hostivice',
            'description' => 'LVÍČATA – kroužek míčovek pro děti na ZŠ Hostivice. Informace, termíny a přihláška.'],
        'lvicata-krouzek-micovek-zs-chyne-vida' => ['page' => 'lvicata',  'title' => 'LVÍČATA … KROUŽEK MÍČOVEK ZŠ Chýně VIDA', 'form' => 'lvicata-vida',
            'description' => 'LVÍČATA – kroužek míčovek pro děti na ZŠ Chýně VIDA. Informace, termíny a přihláška.'],
        'skolni-liga-minihazene-lvicata-zapad'  => ['page' => 'lvicata',  'title' => block('skolni-liga-minihazene-lvicata-zapad.title'), 'form' => 'skolni-liga',
            'description' => 'Školní liga miniházené a Školský pohár házené Praha západ – turnaje pro základní školy.'],
        'czech-handball-camps'                  => ['page' => 'projekt',  'title' => 'CZECH HANDBALL CAMPs', 'fb' => 'camp',
            'description' => 'CZECH HANDBALL CAMPs – celorepublikové házenkářské kempy pořádané klubem LIONS Handball Hostivice.'],
        'championship-zactva-mladsiho'          => ['page' => 'projekt',  'title' => 'CHAMPIONSHIP ŽAKŮ Mladších', 'fb' => 'champ',
            'description' => 'CHAMPIONSHIP žáků mladších – celorepubliková soutěž v házené, kterou organizuje LIONS Handball Hostivice.'],
        'sport-tridy-zs-hostivice'              => ['page' => 'projekt',  'title' => 'SPORT TŘÍDY ZŠ Hostivice', 'fb' => 'tridy',
            'description' => 'SPORT TŘÍDY ZŠ Hostivice – LIONS Handball je garantem sportovních tříd na ZŠ Hostivice.'],
        'letni-sport-primestske-tabory'         => ['page' => 'tabory',   'title' => 'LETNÍ SPORT PŘÍMĚSTSKÉ TÁBORY',
            'description' => 'Letní SPORT příměstské tábory LIONS Handball Hostivice pro děti.'],
        'ochrana-osobnich-udaju'                => ['page' => 'legal',    'title' => block('privacy.title'), 'group' => 'privacy'],
        'zasady-cookies'                        => ['page' => 'legal',    'title' => block('cookies.title'), 'group' => 'cookies'],
    ];
}

/** Absolutní veřejná adresa pro vyhledávače (config/site.php → 'canonical_url'). */
function seo_url(string $path = ''): string
{
    if (preg_match('~^https?://~', $path)) {
        return $path;
    }
    $root = rtrim((string) site('canonical_url', ''), '/');
    if ($root === '') {
        return abs_url(url($path));
    }
    // url() přidá BASE_URL (podsložku) – ta na veřejné adrese není
    $rel = url($path);
    if (BASE_URL !== '' && str_starts_with($rel, BASE_URL)) {
        $rel = substr($rel, strlen(BASE_URL));
    }
    return $root . '/' . ltrim($rel, '/');
}

/** Adresa stránky ve tvaru, jaký používá menu (s lomítkem na konci, úvod = '') */
function seo_page_path(string $slug): string
{
    return $slug === '' ? '' : $slug . '/';
}

function seo_description(array $page): string
{
    if (!empty($page['description'])) {
        return (string) $page['description'];
    }
    if (($page['page'] ?? '') === 'team' && !empty($page['title'])) {
        return $page['title'] . ' – LIONS Handball Hostivice: tréninky, trenéři, zápasy, výsledky a tabulky.';
    }
    return (string) site('description');
}

function seo_noindex(array $page): bool
{
    return !empty($page['noindex']) || ($page['page'] ?? '') === '404';
}

/** Všechny adresy pro sitemap.php */
function seo_sitemap_paths(): array
{
    $paths = [];
    foreach (site_routes() as $slug => $r) {
        if (empty($r['noindex'])) {
            $paths[] = seo_page_path($slug);
        }
    }
    foreach (array_keys($GLOBALS['TEAMS'] ?? []) as $key) {
        $paths[] = seo_page_path((string) $key);
    }
    return array_values(array_unique($paths));
}

function seo_json_ld(array $data): string
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    return $json ? '<script type="application/ld+json">' . $json . '</script>' : '';
}

/** Klub jako SportsOrganization (na úvodní stránce) */
function seo_organization(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'SportsOrganization',
        'name'     => site('title'),
        'alternateName' => 'LIONS Handball',
        'sport'    => 'Handball',
        'url'      => seo_url(),
        'logo'     => seo_url(media(site('logo_small') ?: site('logo'))),
        'email'    => site('email'),
        'address'  => [
            '@type' => 'PostalAddress',
            'streetAddress'   => 'Hájecká 1866',
            'postalCode'      => '253 01',
            'addressLocality' => 'Hostivice',
            'addressCountry'  => 'CZ',
        ],
        'sameAs'   => array_values(array_filter((array) site('social', []))),
    ];
}

/** Utkání LIONS jako SportsEvent (data z handball.cz) */
function seo_events(array $matches): array
{
    $out = [];
    foreach ($matches as $m) {
        if (empty($m['start']) || !($m['start'] instanceof DateTimeInterface) || ($m['state'] ?? '') === 'finished') {
            continue;
        }
        $name = fn(string $n) => csh_is_lions($n) ? (string) site('title') : $n;
        $home = $name((string) $m['home']);
        $away = $name((string) $m['away']);
        $venue = trim((string) ($m['venue'] ?? ''));
        $isHome = !empty($m['lions_home']);
        $placeName = $venue !== '' ? $venue : ($isHome ? 'LIONS Aréna Hostivice' : $home);
        $teams = [['@type' => 'SportsTeam', 'name' => $home], ['@type' => 'SportsTeam', 'name' => $away]];
        $end = DateTimeImmutable::createFromInterface($m['start'])->modify('+90 minutes');   // házená: 2× 30 min + přestávka
        $t = !empty($m['team_key']) ? team((string) $m['team_key']) : null;
        $comp = (string) ($m['comp_name'] ?? '');
        $out[] = [
            '@context'  => 'https://schema.org',
            '@type'     => 'SportsEvent',
            'name'      => $home . ' – ' . $away . ($comp !== '' ? ' (' . $comp . ')' : ''),
            'description' => 'Utkání v házené' . ($t && !empty($t['title']) ? ' – ' . $t['title'] : '') . ($comp !== '' ? ', soutěž ' . $comp : '')
                . ': ' . $home . ' – ' . $away . ', ' . $placeName . '.',
            'image'     => [seo_url(media('2026/05/ChatGPT-Image-May-7-2026-05_49_38-PM.png')), seo_url(media(site('logo_small') ?: site('logo')))],
            'sport'     => 'Handball',
            'startDate' => $m['start']->format(DATE_ATOM),
            'endDate'   => $end->format(DATE_ATOM),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'homeTeam'  => ['@type' => 'SportsTeam', 'name' => $home],
            'awayTeam'  => ['@type' => 'SportsTeam', 'name' => $away],
            'competitor' => $teams,
            'performer' => $teams,
            'location'  => [
                '@type'   => 'Place',
                'name'    => $placeName,
                'address' => $isHome ? 'Hájecká 1866, 253 01 Hostivice' : ($venue !== '' ? $venue : $home),
            ],
            'organizer' => ['@type' => 'Organization', 'name' => 'Český svaz házené', 'url' => 'https://www.handball.cz'],
            'url'       => $m['url'] ?? seo_url(),
        ];
    }
    return $out;
}
