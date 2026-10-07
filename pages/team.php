<?php
/** @var array $page */
$t = $page['team'];
$comps = csh_team_competitions($t);
$canJoin = !empty($t['cta']) || !empty($t['register']);
?>

<?php if (!empty($t['heading'])): ?>
<section class="section section--compact team-intro">
  <div class="container team-intro__grid">
    <?php if (!empty($t['photo'])): ?>
      <div class="team-intro__photo"><?= img($t['photo'], $t['heading'], '', false) ?></div>
    <?php endif; ?>
    <div class="team-intro__body">
      <span class="kicker"><?= e($t['label']) ?></span>
      <h2><?= e($t['heading']) ?></h2>
      <?php foreach ($t['info'] as $line): ?><p class="team-intro__info"><?= inline_html($line) ?></p><?php endforeach; ?>

      <?php if (!empty($t['training'])): ?>
        <div class="training">
          <h3><?= icon('clock', 20) ?><?= e($t['training_title']) ?></h3>
          <ul>
            <?php foreach ($t['training'] as $row):
              $parts = array_map('trim', explode('…', $row)); ?>
              <li><b><?= e($parts[0]) ?></b><span><?= e($parts[1] ?? '') ?></span><em><?= e($parts[2] ?? '') ?></em></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($t['notes']): ?>
        <ul class="team-intro__notes"><?php foreach ($t['notes'] as $n): ?><li><?= e($n) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>

      <?php if ($canJoin): ?>
        <div class="team-intro__actions">
          <a class="btn btn--red btn--lg" href="#prihlaska"><?= e(block('team.join_title')) ?> <?= icon('arrow', 18) ?></a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<nav class="subnav" aria-label="Sekce týmu">
  <div class="container">
    <?php if ($canJoin): ?><a class="subnav__cta" href="#prihlaska">PŘIHLÁŠKA</a><?php endif; ?>
    <?php if (!empty($t['sportlyzer'])): ?><a href="#tym">TÝM</a><?php endif; ?>
    <?php if ($comps): ?><a href="#utkani">UTKÁNÍ</a><a href="#tabulka">TABULKA</a><?php endif; ?>
    <?php if (!empty($t['sportlyzer'])): ?><a href="#kalendar">KALENDÁŘ</a><?php endif; ?>
  </div>
</nav>

<?php if ($canJoin): ?>
<!-- PŘIHLÁŠKA -->
<section class="join" id="prihlaska">
  <div class="container join__grid">
    <div class="join__info">
      <span class="kicker"><?= e($t['label']) ?></span>
      <h2><?= e(block('team.join_title')) ?></h2>
      <?= block_html('team.join_text') ?>
      <ol class="join__steps">
        <?php foreach (block_lines('team.join_steps') as $step): ?><li><?= inline_html($step) ?></li><?php endforeach; ?>
      </ol>
      <?php if (!empty($t['training'])): ?>
        <p class="join__next"><?= icon('calendar', 18) ?> <?= e($t['training_title']) ?> <?= e(implode(', ', array_map(fn($r) => mb_strtolower(trim(explode('…', $r)[0])), $t['training']))) ?></p>
      <?php endif; ?>
    </div>
    <div class="join__card">
      <?= render_form('prihlaska', ['tym' => $t['title']], ['title' => block('team.join_form_title')]) ?>
      <?php if (!empty($t['register'])): ?>
        <div class="join__or"><span>nebo</span></div>
        <div class="join__register">
          <div><strong><?= e(block('team.register_title')) ?></strong><small><?= e(block('team.register_text')) ?></small></div>
          <?= sportlyzer_register(150) ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($t['cta'])): ?>
        <a class="join__contact" href="<?= e(url('kontakt-new/')) ?>"><?= e(block('team.cta')) ?> <?= icon('arrow', 16) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($t['sportlyzer'])): ?>
<!-- TÝM (Sportlyzer) -->
<section class="section section--compact" id="tym">
  <div class="container">
    <?= section_title('TÝM', $t['label']) ?>
    <?= sportlyzer_team((int) $t['sportlyzer']) ?>
  </div>
</section>
<?php endif; ?>

<?php foreach ($comps as $ci => $comp):
  $matches = csh_lions_matches($comp);
  $upcoming = array_values(array_filter($matches, fn($m) => $m['state'] !== 'finished'));
  $played = array_reverse(array_values(array_filter($matches, fn($m) => $m['state'] === 'finished')));
  $standings = csh_standings($comp);
  $tabId = 'c' . $ci;
?>
<!-- UTKÁNÍ (automaticky z handball.cz) -->
<section class="section section--dark section--compact" <?= $ci === 0 ? 'id="utkani"' : '' ?> data-live-section="team" data-team="<?= e($t['key']) ?>">
  <div class="container">
    <div class="section-title section-title--light section-title--split">
      <div><span class="kicker"><?= e($t['label']) ?></span><h2>UTKÁNÍ – <?= e($comp['name']) ?></h2></div>
      <a class="btn btn--ghost btn--sm" href="<?= e($comp['url']) ?>" target="_blank" rel="noopener">handball.cz <?= icon('external', 14) ?></a>
    </div>

    <?php if (!$matches): ?>
      <p class="empty">Rozpis utkání zatím není ve svazovém systému zveřejněn.</p>
    <?php else: ?>
      <div class="tabs" data-tabs>
        <div class="tabs__bar" role="tablist">
          <button role="tab" aria-selected="true" aria-controls="<?= $tabId ?>-up" id="<?= $tabId ?>-up-tab">Rozpis <span><?= count($upcoming) ?></span></button>
          <button role="tab" aria-selected="false" aria-controls="<?= $tabId ?>-res" id="<?= $tabId ?>-res-tab">Výsledky <span><?= count($played) ?></span></button>
        </div>
        <div class="tabs__panel" role="tabpanel" id="<?= $tabId ?>-up" aria-labelledby="<?= $tabId ?>-up-tab">
          <div class="match-list match-list--dark">
            <?php foreach ($upcoming as $m) echo match_row($m, false, $comp['seasons']); ?>
            <?php if (!$upcoming): ?><p class="empty">Všechna utkání této soutěže jsou odehraná.</p><?php endif; ?>
          </div>
        </div>
        <div class="tabs__panel" role="tabpanel" id="<?= $tabId ?>-res" aria-labelledby="<?= $tabId ?>-res-tab" hidden>
          <div class="match-list match-list--dark">
            <?php foreach ($played as $m) echo match_row($m, false, $comp['seasons']); ?>
            <?php if (!$played): ?><p class="empty">Zatím žádné odehrané utkání.</p><?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- TABULKA (automaticky z handball.cz) -->
<section class="section section--compact" <?= $ci === 0 ? 'id="tabulka"' : '' ?>>
  <div class="container">
    <?= section_title('TABULKA – ' . $comp['name'], $t['label']) ?>
    <?= standings_table($standings) ?>
  </div>
</section>
<?php endforeach; ?>

<?php $tp = array_values(array_filter($t['partners'] ?? [], fn($p) => !empty($p[1]))); if ($tp): ?>
<section class="section section--tint section--compact" data-track-section="Partneři týmu">
  <div class="container">
    <?= section_title(count($tp) > 1 ? 'PARTNEŘI TÝMU' : 'PARTNER TÝMU', $t['label'], 'section-title--center') ?>
    <ul class="team-partners">
      <?php foreach ($tp as [$href, $logo, $name]): ?>
        <li><a href="<?= e(safe_url($href ?: '#')) ?>" target="_blank" rel="noopener" title="<?= e($name) ?>"><?= img($logo, $name) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($t['sportlyzer'])): ?>
<!-- KALENDÁŘ (Sportlyzer) -->
<section class="section section--tint" id="kalendar">
  <div class="container">
    <?= section_title('KALENDÁŘ', $t['label']) ?>
    <?= sportlyzer_calendar((int) $t['sportlyzer'], 600) ?>
  </div>
</section>
<?php endif; ?>

<?php foreach ($t['widgets'] ?? [] as $w): ?>
<section class="section section--compact"><div class="container"><?= custom_widget($w) ?></div></section>
<?php endforeach; ?>
