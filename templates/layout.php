<?php
/** @var array $page @var string $content */
$title = !empty($page['title']) ? $page['title'] . ' | ' . site('name') : site('title');
$isHome = ($page['page'] ?? '') === 'home';
$description = seo_description($page);
$noindex = seo_noindex($page);
$canonical = seo_url(seo_page_path((string) ($page['slug'] ?? '')));
?><!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>">
<?php if ($noindex): ?>
  <meta name="robots" content="noindex, follow">
<?php else: ?>
  <link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
  <meta name="theme-color" content="#0b1a36">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <meta property="og:image" content="<?= e(seo_url(media('2026/05/ChatGPT-Image-May-7-2026-05_49_38-PM.png'))) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:site_name" content="<?= e(site('title')) ?>">
  <meta property="og:locale" content="cs_CZ">
  <meta property="og:type" content="website">
  <link rel="icon" href="<?= e(media(site('favicon'))) ?>" sizes="32x32">
  <link rel="apple-touch-icon" href="<?= e(media(site('apple_icon'))) ?>">
  <link rel="preload" href="<?= e(url('assets/fonts/barlow-condensed-800-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('css/fonts.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
  <script>
  (function () {
    var d = document.documentElement;
    d.classList.add('js');
<?php if ($isHome): ?>
    // Úvodní animace: jen na úvodní stránce, jednou za návštěvu, ne pro vyhledávače
    // a ne pro lidi s vypnutými animacemi v systému.
    try {
      if (/[?&]intro=(1|pause)/.test(location.search)) {  // náhled: ?intro=1 (?intro=pause = zůstane stát)
        d.classList.add('has-intro');
      } else if (!sessionStorage.getItem('lionsIntro')
          && !matchMedia('(prefers-reduced-motion: reduce)').matches
          && !/bot|crawl|spider|slurp|lighthouse|headless/i.test(navigator.userAgent)) {
        d.classList.add('has-intro');
        sessionStorage.setItem('lionsIntro', '1');
      }
    } catch (e) {}
<?php endif; ?>
  })();
  </script>
</head>
<body class="page-<?= e($page['page'] ?? '') ?>">
<?php if ($isHome): ?>
<div class="intro" data-intro aria-hidden="true">
  <div class="intro__half intro__half--red"></div>
  <div class="intro__half intro__half--navy"></div>
  <div class="intro__center">
    <img src="<?= e(media(site('logo'))) ?>" alt="" width="160" height="221">
    <span class="intro__name">LIONS</span>
    <span class="intro__sub">Handball</span>
  </div>
</div>
<script>
  (function () {
    var root = document.documentElement, el = document.querySelector('[data-intro]');
    if (!root.classList.contains('has-intro')) { el.remove(); return; }
    if (/[?&]intro=pause/.test(location.search)) return;
    var start = Date.now(), done = false;
    function hide() {
      if (done) return;
      done = true;
      el.classList.add('is-out');
      setTimeout(function () { el.remove(); root.classList.remove('has-intro'); }, 700);
    }
    // zmizí, jakmile je stránka připravená (nejdřív po 1,1 s, aby animace doběhla)
    document.addEventListener('DOMContentLoaded', function () {
      setTimeout(hide, Math.max(0, 1100 - (Date.now() - start)));
    });
    setTimeout(hide, 2500);                    // pojistka: nikdy déle než 2,5 s
    el.addEventListener('click', hide);        // kliknutím přeskočit
    addEventListener('keydown', hide, { once: true });
  })();
</script>
<?php endif; ?>
<a class="skip-link" href="#main">Přeskočit na obsah</a>

<header class="site-header" data-header>
  <div class="container site-header__inner">
    <a class="brand" href="<?= e(url()) ?>" aria-label="LIONS Handball – úvod">
      <img src="<?= e(media(site('logo'))) ?>" alt="LIONS Handball" width="46" height="64">
      <span class="brand__text"><b>LIONS</b><small>Handball</small></span>
    </a>

    <nav class="main-nav" id="main-nav" aria-label="Hlavní menu" data-nav>
      <ul class="main-nav__list">
        <?php foreach (build_menu() as $i => $item): ?>
          <?php if (!empty($item['children'])):
            $open = false;
            foreach ($item['children'] as $c) { $open = $open || is_active($c['url']); } ?>
            <li class="has-sub<?= $open ? ' is-current' : '' ?>">
              <button type="button" class="main-nav__link" aria-expanded="false" aria-controls="sub-<?= $i ?>"><?= e($item['label']) ?><?= icon('chevron', 16) ?></button>
              <ul class="sub" id="sub-<?= $i ?>">
                <?php foreach ($item['children'] as $c): ?>
                  <li><a href="<?= e(url($c['url'])) ?>"<?= is_active($c['url']) ? ' aria-current="page"' : '' ?>><?= e($c['label']) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </li>
          <?php else: ?>
            <li><a class="main-nav__link" href="<?= e(url($item['url'])) ?>"<?= is_active($item['url']) ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
      <div class="main-nav__social">
        <?php foreach (site('social') as $net => $href): ?>
          <a href="<?= e(safe_url($href)) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($net)) ?>"><?= icon($net, 20) ?></a>
        <?php endforeach; ?>
      </div>
    </nav>

    <div class="site-header__actions">
      <a class="btn btn--red btn--sm hide-md" href="<?= e(url('lions-nabor/')) ?>">LIONS NÁBOR</a>
      <button class="nav-toggle" type="button" aria-controls="main-nav" aria-expanded="false" data-nav-toggle>
        <span class="sr-only">Menu</span><span class="nav-toggle__open"><?= icon('menu', 26) ?></span><span class="nav-toggle__close"><?= icon('close', 26) ?></span>
      </button>
    </div>
  </div>
</header>

<main id="main">
<?php if (!$isHome && !empty($page['title'])): ?>
  <section class="page-hero">
    <div class="container">
      <nav class="breadcrumbs" aria-label="Drobečková navigace"><a href="<?= e(url()) ?>">lionshandball.cz</a><span>›</span><?= e($page['title']) ?></nav>
      <h1><?= e($page['title']) ?></h1>
    </div>
  </section>
<?php endif; ?>

<?= $content ?>

  <section class="partner-strip">
    <div class="container">
      <ul class="partner-strip__list" aria-label="Partneři LIONS Handball">
        <?php foreach (site('partners') as [$href, $logo, $name]): if (!$logo) continue; ?>
          <li><a href="<?= e(safe_url($href)) ?>" target="_blank" rel="noopener" title="<?= e($name) ?>"><?= img($logo, $name) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="container site-footer__grid">
    <div class="site-footer__brand">
      <img src="<?= e(media(site('logo'))) ?>" alt="" width="70" height="97" loading="lazy">
      <div>
        <h2>LIONS Handball</h2>
        <p><?= icon('pin', 18) ?><span><?= e(site('address')) ?></span></p>
        <p><?= icon('mail', 18) ?><a href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a></p>
      </div>
    </div>
    <nav class="site-footer__links" aria-label="Rychlé odkazy">
      <a href="<?= e(url('lions-nabor/')) ?>">LIONS NÁBOR</a>
      <a href="<?= e(url('kontakt-new/')) ?>">KONTAKTY</a>
      <a href="<?= e(url('sponsors/')) ?>">PARTNEŘI</a>
      <a href="<?= e(url('kup-si-tym/')) ?>">BÝT PATRON</a>
      <a href="<?= e(url('mini-gym/')) ?>">LIONS miniGYM booking</a>
    </nav>
    <div class="site-footer__social">
      <span>Lions Handball</span>
      <div>
        <?php foreach (site('social') as $net => $href): ?>
          <a href="<?= e(safe_url($href)) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($net)) ?>"><?= icon($net, 22) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="container site-footer__bottom">
    <span>© <?= date('Y') ?> LIONS Handball Hostivice</span>
    <span class="site-footer__legal">
      <a href="<?= e(url('ochrana-osobnich-udaju/')) ?>">Ochrana osobních údajů</a>
      <a href="<?= e(url('zasady-cookies/')) ?>">Cookies</a>
      <?php if (consent_required()): ?><button type="button" data-consent-open>Nastavení cookies</button><?php endif; ?>
    </span>
    <span>Výsledky a tabulky: <a href="https://www.handball.cz" target="_blank" rel="noopener">Český svaz házené</a> · Kalendáře: <a href="https://www.sportlyzer.com" target="_blank" rel="noopener">Sportlyzer</a></span>
  </div>
</footer>

<div class="lightbox" data-lightbox hidden>
  <button type="button" class="lightbox__close" aria-label="Zavřít"><?= icon('close', 28) ?></button>
  <img alt="">
</div>

<?= consent_ui() ?>
<script>window.LIONS = { base: <?= json_encode(BASE_URL) ?> };</script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
