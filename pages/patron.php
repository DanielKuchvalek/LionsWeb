<?php
$pairs = function (string $key): array {
    $out = [];
    foreach (block_lines($key) as $line) {
        [$a, $b] = array_map('trim', explode('|', $line, 2)) + [1 => null];
        $out[] = [$a, $b];
    }
    return $out;
};
$emailLink = '<a href="mailto:' . e(site('email')) . '">' . e(site('email')) . '</a>';
?>
<section class="section section--navy patron-hero">
  <div class="container patron-hero__grid">
    <div>
      <h2 class="display"><?= e(block('patron.hero_title')) ?><br><span><?= e(block('patron.hero_title2')) ?></span></h2>
      <p class="display-sub"><?= nl2br(e(block('patron.hero_sub')), false) ?></p>
      <a class="btn btn--red btn--lg" href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?> <?= icon('mail', 18) ?></a>
    </div>
    <div class="jersey-stack" data-carousel>
      <?php foreach (block_lines('patron.jerseys') as $i => $src): ?>
        <a class="leo-carousel__item<?= $i === 0 ? ' is-active' : '' ?>" href="<?= e(media(preg_replace('/-\d+x\d+(\.\w+)$/', '$1', $src))) ?>" data-zoom><?= img($src, 'LIONS dresy', '', $i > 0) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container patron-grid">
    <div class="patron-text">
      <div class="lead"><?= block_html('patron.lead') ?></div>
      <div class="steps">
        <?php foreach ($pairs('patron.steps') as [$h, $p]): ?>
          <div class="step"><h3><?= e($h) ?></h3><?php if ($p): ?><p><?= str_replace(e(site('email')), $emailLink, inline_html($p)) ?></p><?php endif; ?></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="photo-mosaic">
      <?php foreach (block_lines('patron.photos') as $p): ?>
        <a href="<?= e(media($p)) ?>" data-zoom><?= img($p, 'LIONS Handball') ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--dark section--soft">
  <div class="container center">
    <h2 class="display"><?= e(block('patron.mid_title')) ?><br><span><?= e(block('patron.mid_title2')) ?></span></h2>
    <ul class="claims"><?php foreach (block_lines('patron.claims') as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ul>
    <?= block_html('patron.signature') ?>
  </div>
</section>

<section class="section">
  <div class="container patron-grid patron-grid--rev">
    <div class="photo-mosaic photo-mosaic--2">
      <?php foreach (block_lines('patron.photos2') as $p): ?>
        <a href="<?= e(media($p)) ?>" data-zoom><?= img($p, 'LIONS Handball') ?></a>
      <?php endforeach; ?>
    </div>
    <ul class="facts">
      <?php foreach ($pairs('patron.facts') as [$h, $p]): ?>
        <li><?php if ($p !== null): ?><h3><?= e($h) ?></h3><p><?= inline_html($p) ?></p><?php else: ?><p><?= inline_html($h) ?></p><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section section--red section--soft center">
  <div class="container">
    <h2 class="display"><?= e(block('patron.contact_title')) ?></h2>
    <a class="mega-link" href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a>
  </div>
</section>
