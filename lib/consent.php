<?php
declare(strict_types=1);

/**
 * Souhlas s cookies třetích stran (Facebook, Sportlyzer, Google Mapy…).
 *
 * Vložený obsah těchto služeb se načte AŽ po souhlasu návštěvníka – do té doby
 * se místo něj zobrazuje zástupný box s tlačítkem „Povolit“.
 * Web sám žádné sledovací ani reklamní cookies nepoužívá.
 * Volba se ukládá do cookie "lions_consent" (nezbytná cookie, 6 měsíců).
 */

const CONSENT_VERSION = 1;   // zvýšením se souhlas vyžádá znovu (např. při přidání nové služby)

/**
 * Vyžadovat souhlas s cookies (lišta + zástupné boxy)?
 * Výchozí stav: NE – vložený obsah (Sportlyzer, mapy…) se načte hned a lišta se nezobrazuje.
 * Zapnout jde v config/site.php: 'cookie_banner' => true.
 */
function consent_required(): bool
{
    return site('cookie_banner') === true;
}

function consent_services(): array
{
    return [
        'sportlyzer' => [
            'name'   => 'Sportlyzer',
            'desc'   => 'Kalendáře tréninků a utkání, soupisky týmů a online registrace členů. Poskytovatel Sportlyzer může při zobrazení ukládat cookies.',
            'policy' => 'https://www.sportlyzer.com/',
        ],
        'facebook' => [
            'name'   => 'Facebook',
            'desc'   => 'Zobrazení příspěvků z facebookových stránek klubu. Meta Platforms Ireland Ltd. může ukládat cookies a zpracovávat údaje o vašem prohlížení.',
            'policy' => 'https://www.facebook.com/privacy/policy/',
        ],
        'maps' => [
            'name'   => 'Google Mapy',
            'desc'   => 'Mapa s místem tréninků. Google Ireland Ltd. může ukládat cookies.',
            'policy' => 'https://policies.google.com/privacy',
        ],
        'external' => [
            'name'   => 'Ostatní vložený obsah',
            'desc'   => 'Další widgety vložené do stránek týmů (např. videa nebo formuláře jiných služeb).',
            'policy' => null,
        ],
    ];
}

/** Ke které službě patří adresa vloženého obsahu */
function consent_service_for(string $src): string
{
    $host = strtolower((string) parse_url($src, PHP_URL_HOST));
    return match (true) {
        str_contains($host, 'sportlyzer')                                   => 'sportlyzer',
        str_contains($host, 'facebook') || str_contains($host, 'fb.com')    => 'facebook',
        str_contains($host, 'google')                                       => 'maps',
        default                                                             => 'external',
    };
}

/** Zástupný box místo vloženého obsahu, dokud návštěvník nesouhlasí */
function consent_placeholder(string $service): string
{
    if (!consent_required()) {
        return '';
    }
    $s = consent_services()[$service] ?? consent_services()['external'];
    return '<div class="consent-ph">'
        . '<p><strong>' . e($s['name']) . '</strong></p>'
        . '<p>' . e($s['desc']) . ' Obsah se zobrazí po vašem souhlasu.</p>'
        . '<div class="consent-ph__btns">'
        . '<button type="button" class="btn btn--navy btn--sm" data-consent-allow="' . e($service) . '">Povolit ' . e($s['name']) . ' a zobrazit</button>'
        . '<button type="button" class="linkbtn-plain" data-consent-open>Nastavení cookies</button>'
        . '</div></div>';
}

/** Lišta + dialog nastavení (vkládá se do layoutu) */
function consent_ui(): string
{
    $services = consent_services();
    if (!consent_required()) {
        return '<script>window.LIONS_CONSENT = ' . json_encode(['version' => CONSENT_VERSION, 'services' => array_keys($services), 'auto' => true]) . ';</script>';
    }
    ob_start(); ?>
    <div class="cookiebar" data-cookiebar hidden role="region" aria-label="Souhlas s cookies">
      <div class="cookiebar__inner">
        <div class="cookiebar__text">
          <strong>Cookies a vložený obsah</strong>
          <p>Web LIONS nepoužívá žádné sledovací ani reklamní cookies. Některé části stránek ale zobrazují obsah jiných služeb
             (Facebook, Sportlyzer, Google Mapy), které cookies ukládat mohou – ty načteme jen s vaším souhlasem.
             <a href="<?= e(url('zasady-cookies/')) ?>">Více informací</a></p>
        </div>
        <div class="cookiebar__btns">
          <button type="button" class="btn btn--red btn--sm" data-consent-all>Povolit vše</button>
          <button type="button" class="btn btn--ghost-dark btn--sm" data-consent-none>Jen nezbytné</button>
          <button type="button" class="linkbtn-plain" data-consent-open>Nastavení</button>
        </div>
      </div>
    </div>

    <div class="consent-modal" data-consent-modal hidden role="dialog" aria-modal="true" aria-labelledby="consent-title">
      <form class="consent-modal__box" data-consent-form>
        <h2 id="consent-title">Nastavení cookies</h2>
        <p class="muted">Vyberte, který obsah jiných služeb smíme načíst. Svou volbu můžete kdykoli změnit odkazem „Nastavení cookies“ v patičce.</p>
        <label class="consent-opt consent-opt--locked">
          <input type="checkbox" checked disabled>
          <span><b>Nezbytné</b><small>Uložení této volby a přihlášení do administrace. Bez nich web nefunguje.</small></span>
        </label>
        <?php foreach ($services as $key => $s): ?>
          <label class="consent-opt">
            <input type="checkbox" name="<?= e($key) ?>" value="1">
            <span><b><?= e($s['name']) ?></b><small><?= e($s['desc']) ?><?php if ($s['policy']): ?> <a href="<?= e($s['policy']) ?>" target="_blank" rel="noopener">Zásady služby</a><?php endif; ?></small></span>
          </label>
        <?php endforeach; ?>
        <div class="consent-modal__btns">
          <button type="submit" class="btn btn--navy btn--sm">Uložit volbu</button>
          <button type="button" class="btn btn--red btn--sm" data-consent-all>Povolit vše</button>
          <button type="button" class="linkbtn-plain" data-consent-none>Odmítnout vše</button>
        </div>
      </form>
    </div>
    <script>window.LIONS_CONSENT = <?= json_encode(['version' => CONSENT_VERSION, 'services' => array_keys($services)]) ?>;</script>
    <?php return (string) ob_get_clean();
}
