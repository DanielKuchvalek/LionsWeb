<?php $link = block('letni-sport-primestske-tabory.link'); $flyer = block('letni-sport-primestske-tabory.flyer'); ?>
<section class="section section--compact">
  <div class="container flyer-grid">
    <?php if ($flyer): ?><a class="flyer" href="<?= e(safe_url($link)) ?>" target="_blank" rel="noopener"><?= img($flyer, 'SPORT TÁBORY', '', false) ?></a><?php endif; ?>
    <div class="flyer-side flyer-side--center">
      <h2 class="display"><?= e(block('letni-sport-primestske-tabory.title')) ?><br><span><?= e(block('letni-sport-primestske-tabory.title2')) ?></span></h2>
      <?php if ($link): ?><a class="btn btn--red btn--lg" href="<?= e(safe_url($link)) ?>" target="_blank" rel="noopener"><?= e($link) ?> <?= icon('external', 16) ?></a><?php endif; ?>
    </div>
  </div>
</section>

<section class="section section--tint">
  <div class="container gallery">
    <?php foreach (block_lines('letni-sport-primestske-tabory.gallery') as $p): ?>
      <a href="<?= e(media($p)) ?>" data-zoom><?= img($p, 'LIONS letní sportovní tábory') ?></a>
    <?php endforeach; ?>
  </div>
</section>
