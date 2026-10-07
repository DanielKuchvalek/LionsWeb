<?php /** @var array $page */ $flyer = block($page['slug'] . '.flyer'); ?>
<section class="section section--compact">
  <div class="container flyer-grid">
    <?php if ($flyer): ?><a class="flyer" href="<?= e(media($flyer)) ?>" data-zoom><?= img($flyer, $page['title'], '', false) ?></a><?php endif; ?>
    <div class="form-card"><?= render_form($page['form']) ?></div>
  </div>
</section>
