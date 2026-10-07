<?php /** @var array $page */
$flyer = block($page['slug'] . '.flyer');
$link = block($page['slug'] . '.link');
?>
<section class="section section--compact">
  <div class="container flyer-grid">
    <?php if ($flyer): ?>
      <a class="flyer" href="<?= e($link ? safe_url($link) : media($flyer)) ?>"<?= $link ? ' target="_blank" rel="noopener"' : ' data-zoom' ?>><?= img($flyer, $page['title'], '', false) ?></a>
    <?php endif; ?>
    <div class="flyer-side">
      <?= facebook_feed($page['fb'], 900) ?>
      <?php if ($link): ?>
        <a class="btn btn--red btn--lg btn--block" href="<?= e(safe_url($link)) ?>" target="_blank" rel="noopener"><?= e(preg_replace('~^https?://~', '', $link)) ?> <?= icon('external', 16) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
