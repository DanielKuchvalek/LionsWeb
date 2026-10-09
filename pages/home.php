<?php
$upcomingHome = csh_upcoming_home(max(1, (int) block('home.matches_count')));
$upcomingAll  = csh_upcoming_all(6, 7);    // všechna utkání na týden dopředu (aspoň 6)
$results      = csh_latest_results(8, 7);  // všechny výsledky za poslední týden (aspoň 8)
$hasData      = csh_all_lions_matches() !== [];
$heroLink     = block('home.hero_link');
$heroHref     = preg_match('~^https?://~', $heroLink) ? $heroLink : url($heroLink);
?>

<!-- ============ NÁBOR / HERO ============ -->
<section class="hero">
  <h1 class="sr-only"><?= e(site('title')) ?> – házená pro děti, mládež i dospělé</h1>
  <div class="container hero__grid">
    <a class="hero__banner" href="<?= e(safe_url($heroHref)) ?>">
      <?= img(block('home.hero_banner'), 'LIONS NÁBOR', '', false) ?>
    </a>
    <div class="hero__videos">
      <?php foreach ([1 => 'dot--blue', 2 => 'dot--red'] as $i => $dot): if (!block("home.video$i")) continue; ?>
        <figure class="video-card">
          <video controls preload="metadata" playsinline>
            <source src="<?= e(media(block("home.video$i"))) ?>#t=0.5" type="video/mp4">
          </video>
          <figcaption><span class="dot <?= $dot ?>"></span><?= e(block("home.video{$i}_label")) ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ NEJBLIŽŠÍ DOMÁCÍ UTKÁNÍ (automaticky z handball.cz) ============ -->
<section class="section section--dark section--compact" id="utkani" data-live-section="home">
  <div class="container">
    <div class="section-title section-title--center section-title--light">
      <h2><?= e(block('home.matches_title')) ?></h2>
      <p class="subtitle"><?= e(block('home.matches_subtitle')) ?></p>
    </div>
    <?php if ($upcomingHome): ?>
      <div class="match-grid"><?php foreach ($upcomingHome as $m) echo match_card($m); ?></div>
    <?php elseif (!$hasData): ?>
      <?= csh_unavailable() ?>
    <?php else: ?>
      <p class="empty">Momentálně nejsou v kalendáři svazu naplánována žádná domácí utkání.</p>
    <?php endif; ?>
  </div>
</section>

<!-- ============ VÝSLEDKY + PROGRAM (automaticky z handball.cz) ============ -->
<?php if ($results || $upcomingAll): ?>
<section class="section section--tint section--compact">
  <div class="container results-grid">
    <?php if ($results): ?>
    <div class="panel">
      <div class="panel__head"><h2>POSLEDNÍ VÝSLEDKY</h2><span class="panel__src">handball.cz</span></div>
      <div class="match-list"><?php foreach ($results as $m) echo match_row($m, true); ?></div>
    </div>
    <?php endif; ?>
    <?php if ($upcomingAll): ?>
    <div class="panel">
      <div class="panel__head"><h2>PROGRAM TÝMŮ</h2><span class="panel__src">handball.cz</span></div>
      <div class="match-list"><?php foreach ($upcomingAll as $m) echo match_row($m, true); ?></div>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ============ ROZPISY A VÝSLEDKY – odkazy na soutěže ============ -->
<section class="section section--compact" id="vysledky">
  <div class="container">
    <div class="links-grid">
      <?php foreach (site('results_links') as $group => $links): ?>
        <div class="links-card">
          <span class="kicker"><?= e($group) ?></span>
          <h2>ROZPISY A VÝSLEDKY</h2>
          <ul>
            <?php foreach ($links as [$label, $href]): ?>
              <li><a href="<?= e(safe_url($href)) ?>" target="_blank" rel="noopener"><span><?= e($label) ?></span><?= icon('arrow', 18) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="links-note"><?= block_html('home.links_note') ?></div>
  </div>
</section>

<!-- ============ O KLUBU ============ -->
<section class="section section--navy about">
  <div class="container about__grid">
    <div class="about__text">
      <h2><?= e(block('home.about_title')) ?></h2>
      <?= block_html('home.about_text') ?>
      <div class="team-chips">
        <?php foreach ($GLOBALS['TEAMS'] as $key => $t): if (empty($t['in_menu'])) continue; ?>
          <a href="<?= e(url($key . '/')) ?>"><?= e($t['menu_label'] ?: $t['title']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="about__media"><?= img(block('home.about_image'), 'LIONS Handball') ?></div>
  </div>
</section>

<!-- ============ FACEBOOK ============ -->
<section class="section section--tint">
  <div class="container fb-grid">
    <?= facebook_feed('mladez') ?>
    <?= facebook_feed('team') ?>
  </div>
</section>

<!-- ============ SPORTLYZER KALENDÁŘ ============ -->
<section class="section" id="kalendar">
  <div class="container">
    <?= section_title(block('home.calendar_title'), null, 'section-title--center') ?>
    <?= sportlyzer_calendar(null, 650) ?>
  </div>
</section>

<?php
// Strukturovaná data pro Google (klub + nejbližší utkání). Při chybě se prostě vynechají.
try {
    $seoMatches = [];
    foreach (array_merge($upcomingHome, $upcomingAll) as $m) {
        $seoMatches[$m['id']] = $m;
    }
    echo seo_json_ld(seo_organization());
    foreach (seo_events(array_values($seoMatches)) as $ev) {
        echo seo_json_ld($ev);
    }
} catch (Throwable $e) {
    error_log('LIONS seo: ' . $e->getMessage());
}
