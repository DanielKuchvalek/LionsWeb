<?php
/**
 * Hlavní nastavení webu LIONS Handball.
 * Texty jsou převzaté 1:1 z původního webu lionshandball.cz.
 */
return [
    'name'        => 'LIONS Handball',
    'title'       => 'LIONS Handball Hostivice',
    'description' => 'LIONS Handball Hostivice – házená pro děti, mládež i dospělé v LIONS Aréně Handball Centra Hostivice.',

    // Když web běží v podsložce a autodetekce selže, nastavte ručně, např. '/LionsTest'. Jinak null.
    'base_url'    => null,

    // Veřejná adresa webu pro odkazy v e-mailech (např. 'https://www.lionshandball.cz'). null = zjistí se sama.
    'site_url'    => null,
    // Adresy, na kterých web běží (jen tyto se použijí v odkazech v e-mailech, když site_url není nastavené)
    // Hlavní adresa webu pro vyhledávače (canonical, sitemap, Open Graph)
    'canonical_url' => 'https://www.lionshandball.cz',
    'hosts'       => ['www.lionshandball.cz', 'lionshandball.cz', 'diplomovaprace.endora.site'],
    'cookie_banner' => false,   // true = lišta se souhlasem s cookies a vložený obsah až po souhlasu

    // Kam chodí odeslané formuláře a rezervace miniGYM
    'email'       => 'lions@lionshandball.cz',
    'mail_from'   => 'web@lionshandball.cz',

    'address'      => 'Hájecká 1866, 253 01 Hostivice - Břve, ... u Břevského rybníka',
    'social' => [
        'facebook'  => 'https://www.facebook.com/lionshandballmladez',
        'instagram' => 'https://www.instagram.com/lionshandballmladez/',
        'youtube'   => 'https://www.youtube.com/@lionshandball5749',
    ],

    'logo'         => '2019/04/LIONS_logo_NEW_2026-web.png',   // zmenšená verze pro web (originál: LIONS_logo_NEW_2026-1-scaled.png)
    'logo_small'   => '2019/04/cropped-LIONS_logo_NEW_2026_square-small-512-270x270.png',
    'favicon'      => '2019/04/cropped-LIONS_logo_NEW_2026_square-small-512-32x32.png',
    'apple_icon'   => '2019/04/cropped-LIONS_logo_NEW_2026_square-small-512-180x180.png',
    'header_image' => '2025/07/Lions-webpage-header-black.png',
    'partner_strip'=> '2026/04/LIONS-partner-lista-202604_black.png',

    /* ------------------------------------------------------------------
     * SPORTLYZER
     * seed = veřejný klíč klubu, club_url = profil klubu ve Finderu.
     * Widget lze do stránky vložit i ručně – viz 'widgets' u týmů (config/teams.php).
     * ------------------------------------------------------------------ */
    'sportlyzer' => [
        'seed'     => 'm3*Ya',
        'lang'     => 'cze',
        'club_url' => 'https://finder.sportlyzer.com/club/LIONS-Handball-Team/36248',
    ],

    /* ------------------------------------------------------------------
     * ČESKÝ SVAZ HÁZENÉ (handball.cz) – automatické výsledky, rozpisy, tabulky
     * ------------------------------------------------------------------ */
    'csh' => [
        'base'       => 'https://www.handball.cz',
        'team_name'  => 'LIONS Handball Team',   // takto se tým jmenuje v IS ČSH
        'home_venue' => 'Hostivice',             // podle toho poznáme domácí halu
        'cache_ttl'  => 900,                     // s. – běžná obnova dat
        'live_ttl'   => 60,                      // s. – když se zrovna hraje
        'timeout'    => 8,
    ],

    // Jak dlouho se uchovávají údaje z formulářů a rezervací (měsíce), pak se automaticky smažou
    'retention' => [
        'forms'   => 24,
        'minigym' => 12,
    ],

    // Facebook stránky (oficiální Page Plugin)
    'facebook_pages' => [
        'mladez' => ['name' => 'LIONS Handball mládež', 'url' => 'https://www.facebook.com/lionshandballmladez'],
        'team'   => ['name' => 'LIONS Handball',        'url' => 'https://www.facebook.com/420011958583476'],
        'camp'   => ['name' => 'Czech Handball CAMP',        'url' => 'https://www.facebook.com/1836837983279126'],
        'champ'  => ['name' => 'Championship házené žactva mladšího', 'url' => 'https://www.facebook.com/955195708000259'],
        'tridy'  => ['name' => 'Sport Třídy ZŠ Hostivice',   'url' => 'https://www.facebook.com/107427642120666'],
    ],

    /* ------------------------------------------------------------------
     * MENU – stejná struktura jako na původním webu
     * ------------------------------------------------------------------ */
    'menu' => [
        ['label' => 'HOME',        'url' => ''],
        ['label' => 'LIONS NÁBOR', 'url' => 'lions-nabor/'],
        ['label' => 'LIONS TÝMY',  'auto' => 'teams'],   // plní se z týmů (config/teams.php / administrace)
        ['label' => 'LVÍČATA kroužky', 'children' => [
            ['label' => 'LVÍČATA … KROUŽEK MÍČOVEK ZŠ Hostivice',   'url' => 'lvicata-zs-hostivice/'],
            ['label' => 'LVÍČATA … KROUŽEK MÍČOVEK ZŠ Chýně VIDA', 'url' => 'lvicata-krouzek-micovek-zs-chyne-vida/'],
        ]],
        ['label' => 'PROJEKTY', 'children' => [
            ['label' => 'PRAHA ZÁPAD … ŠKOLNÍ LIGA MINIHÁZENÉ a ŠKOLSKÝ POHÁR HÁZENÉ 25/26', 'url' => 'skolni-liga-minihazene-lvicata-zapad/'],
            ['label' => 'CZECH HANDBALL CAMPs',           'url' => 'czech-handball-camps/'],
            ['label' => 'CHAMPIONSHIP ŽÁKŮ Mladších',     'url' => 'championship-zactva-mladsiho/'],
            ['label' => 'letní SPORT příměstské TÁBORY',  'url' => 'letni-sport-primestske-tabory/'],
            ['label' => 'SPORT TŘÍDY ZŠ Hostivice',       'url' => 'sport-tridy-zs-hostivice/'],
        ]],
        ['label' => 'KONTAKTY',    'url' => 'kontakt-new/'],
        ['label' => 'PARTNEŘI',    'url' => 'sponsors/'],
        ['label' => 'BÝT PATRON',  'url' => 'kup-si-tym/'],
        ['label' => '… pro ČLENY', 'children' => [
            ['label' => 'LIONS miniGYM booking', 'url' => 'mini-gym/'],
        ]],
    ],

    /* ------------------------------------------------------------------
     * HOMEPAGE – sekce "ROZPISY A VÝSLEDKY" (odkazy na handball.cz)
     * ------------------------------------------------------------------ */
    'results_links' => [
        'mužská složka' => [
            ['ŽÁCI MLADŠÍ ... SKSH LIGA ... ROZPISY A VÝSLEDKY',                'https://www.handball.cz/souteze/muzi/stredoceska-liga-mladsich-zaku-chlapci'],
            ['ŽÁCI MLADŠÍ ... SKSH KVALIFIKACE DESETIBOJ ... ROZPISY A VÝSLEDKY', 'https://www.handball.cz/souteze/muzi/stredoceska-kvalifikace-o-desetiboj-mladsi-zaci'],
            ['ŽÁCI STARŠÍ ... SKSH LIGA ... ROZPISY A VÝSLEDKY',                'https://www.handball.cz/souteze/muzi/prvni-stredoceska-liga-starsiho-zactva'],
            ['ŽÁCI STARŠÍ ... ŽÁKOVSKÁ LIGA ... ROZPISY A VÝSLEDKY',            'https://www.handball.cz/souteze/muzi/zl-zaci-hl-cast-26'],
            ['DOROSTENCI MLADŠÍ ... 2.LIGA SZČ ... ROZPISY A VÝSLEDKY',         'https://www.handball.cz/souteze/muzi/2-liga-mladsi-dorostenci-szc'],
            ['MUŽI ... 2.LIGA SZČ ... ROZPISY A VÝSLEDKY',                      'https://www.handball.cz/souteze/muzi/2-liga-muzi-szc'],
        ],
        'ženská složka' => [
            ['ŽÁKYNĚ MLADŠÍ ... SKSH LIGA ... ROZPISY A VÝSLEDKY',                'https://www.handball.cz/souteze/zeny/stredoceska-liga-mladsich-zakyn'],
            ['ŽÁKYNĚ MLADŠÍ ... SKSH KVALIFIKACE DESETIBOJ ... ROZPISY A VÝSLEDKY', 'https://www.handball.cz/souteze/zeny/stredoceska-kvalifikace-o-desetiboj-mladsi-zakyne'],
            ['ŽÁKYNĚ STARŠÍ ... SKSH LIGA ... ROZPISY A VÝSLEDKY',                'https://www.handball.cz/souteze/zeny/stredoceska-liga-starsich-zakyn'],
            ['ŽÁKYNĚ STARŠÍ ... ŽÁKOVSKÁ LIGA ... ROZPISY A VÝSLEDKY',            'https://www.handball.cz/souteze/zeny/zl-zakyne-hl-cast-26'],
            ['DOROSTENKY MLADŠÍ ... 2.LIGA ČECHY ... ROZPISY A VÝSLEDKY',         'https://www.handball.cz/souteze/zeny/2-liga-mladsi-dorostenky-cechy'],
            ['ŽENY ... 2.LIGA ČECHY 2 ... ROZPISY A VÝSLEDKY',                   'https://www.handball.cz/souteze/zeny/liga-zeny-cechyyy'],
        ],
    ],

    // Partneři – stránka PARTNEŘI i lišta log v patičce (pořadí = pořadí v liště)
    'partners' => [
        ['https://www.prevodyzdarma.cz/',        '2024/08/PREVODYZDARMA.CZ-LogoSet-transparent-ver.4.2-300x100.png', 'PREVODYZDARMA.CZ'],
        ['https://www.hostivice-mesto.cz/',      '2024/06/web-logo_hostivice-mesto-1-130x130.png',                  'Město Hostivice'],
        ['https://www.topo.cz/',                 '2024/08/LOGO_topo_s_www_uprava-e1724790319111-300x98.png',        'TOPO'],
        ['http://www.kofola.cz',                 '2026/04/KOFOLA-logo-cerna-214x80.png',                            'Kofola'],
        ['http://www.proball.cz/',               '2024/06/web-logo_proball-2-574x133.png',                          'PROBALL.CZ'],
        ['https://nsa.gov.cz/',                  '2024/06/web-logo_nsa-1-300x55.png',                               'Národní sportovní agentura'],
        ['http://www.sportcentrumhostivice.cz/', '2024/06/web-logo_sch-300x55.png',                                 'Sport Centrum Hostivice'],
    ],
];
