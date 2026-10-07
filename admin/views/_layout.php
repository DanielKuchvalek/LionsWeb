<?php
/** @var string $body @var string $title @var string $p */
$nav = [
    'dashboard' => ['Přehled', 'M3 12l9-8 9 8M5 10v10h14V10'],
    'gym'       => ['miniGYM rezervace', 'M6 7v10M18 7v10M3 9v6M21 9v6M6 12h12'],
    'teams'     => ['Týmy', 'M16 11a4 4 0 1 0-8 0M3 21a9 9 0 0 1 18 0'],
    'pages'     => ['Stránky a texty', 'M6 3h9l5 5v13H6zM14 3v6h6'],
    'media'     => ['Obrázky a videa', 'M3 5h18v14H3zM3 16l5-5 5 5 3-3 5 5'],
    'stats'     => ['Návštěvnost', 'M4 20V10M10 20V4M16 20v-7M22 20H2'],
    'forms'     => ['Přijaté formuláře', 'M4 4h16v16H4zM8 9h8M8 13h8M8 17h5'],
    'gdpr'      => ['Osobní údaje', 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6zM9 12l2 2 4-4'],
    'settings'  => ['Nastavení webu', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19 12l2-1-2-4-2 1-2-2 1-2-4-2-1 2h-2L9 2 5 4l1 2-2 2-2-1-2 4 2 1v2l-2 1 2 4 2-1 2 2-1 2 4 2 1-2h2l1 2 4-2-1-2 2-2 2 1 2-4-2-1z'],
    'health'    => ['Stav systému', 'M3 12h4l3-8 4 16 3-8h4'],
    'account'   => ['Účet a správci', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0'],
];
$active = ['gym-edit' => 'gym', 'team-edit' => 'teams', 'page-edit' => 'pages', 'form-view' => 'forms'][$p] ?? $p;
?><!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> – administrace LIONS</title>
  <link rel="icon" href="<?= e(media(site('favicon'))) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/fonts.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('admin/assets/admin.css')) ?>?v=<?= filemtime(ROOT . '/admin/assets/admin.css') ?>">
</head>
<body>
<div class="shell">
  <aside class="side" data-side>
    <a class="side__brand" href="<?= e(admin_url()) ?>"><img src="<?= e(media(site('logo'))) ?>" alt=""><span><b>LIONS</b><small>administrace</small></span></a>
    <nav>
      <?php foreach ($nav as $k => [$label, $d]): ?>
        <a href="<?= e(admin_url($k)) ?>"<?= $active === $k ? ' aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" width="20" height="20"><path d="<?= $d ?>" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="side__foot">
      <a href="<?= e(url()) ?>" target="_blank">↗ Zobrazit web</a>
      <span><?= e(admin_user()) ?> · <a href="<?= e(admin_url('logout')) ?>">Odhlásit</a></span>
    </div>
  </aside>
  <main class="main">
    <header class="top">
      <button class="top__menu" type="button" data-side-toggle aria-label="Menu">☰</button>
      <h1><?= e($title) ?></h1>
    </header>
    <?php foreach (flash() as $f): ?>
      <div class="flash flash--<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>
    <?= $body ?>
  </main>
</div>

<div class="modal" data-media-modal hidden>
  <div class="modal__box">
    <header>
      <h2>Knihovna médií</h2>
      <input type="search" placeholder="Hledat podle názvu…" data-mm-search>
      <label class="btn btn--red btn--sm">Nahrát nový<input type="file" hidden data-mm-upload accept="image/*,video/mp4"></label>
      <button type="button" class="modal__close" data-mm-close aria-label="Zavřít">×</button>
    </header>
    <div class="modal__status" data-mm-status></div>
    <div class="mm-grid" data-mm-grid></div>
  </div>
</div>

<script>try { localStorage.setItem("lionsAdmin", "1"); } catch (e) {}  /* návštěvy správců se do statistik nepočítají */
window.ADMIN = { api: <?= json_encode(url('admin/api.php')) ?>, csrf: <?= json_encode(csrf_token()) ?> };</script>
<script src="<?= e(url('admin/assets/admin.js')) ?>?v=<?= filemtime(ROOT . '/admin/assets/admin.js') ?>" defer></script>
</body>
</html>
