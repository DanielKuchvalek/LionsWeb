<?php
$ages = [];
foreach (block_lines('nabor.ages') as $line) {
    [$year, $teams] = array_map('trim', explode('|', $line, 2)) + [1 => ''];
    $links = [];
    foreach (array_filter(array_map('trim', explode(';', $teams))) as $pair) {
        [$label, $href] = array_map('trim', explode('=', $pair, 2)) + [1 => ''];
        $links[] = [$label, $href];
    }
    $ages[$year] = $links;
}
$leo = block_lines('nabor.leo');
?>
<section class="section section--compact">
  <div class="container">
    <div class="banner-card"><?= img(block('nabor.banner'), 'LIONS NÁBOR', '', false) ?></div>
  </div>
</section>

<section class="section section--navy nabor-intro">
  <div class="container nabor-intro__grid">
    <div class="nabor-intro__text">
      <h2><?= e(block('nabor.title')) ?></h2>
      <div class="big"><?= block_html('nabor.big') ?></div>
      <hr>
      <?= block_html('nabor.text') ?>
      <hr>
      <?= block_html('nabor.text2') ?>
      <a class="btn btn--red btn--lg" href="#formular">Zájem o informace <?= icon('arrow', 18) ?></a>
    </div>
    <?php if ($leo): ?>
    <div class="leo-carousel" data-carousel>
      <?php foreach ($leo as $i => $src): ?>
        <div class="leo-carousel__item<?= $i === 0 ? ' is-active' : '' ?>"><?= img($src, 'LIONS maskot Leo', '', $i > 0) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section--compact">
  <div class="container">
    <?= section_title(block('nabor.ages_title'), null, 'section-title--center') ?>
    <div class="age-grid">
      <?php foreach ($ages as $year => $teams): ?>
        <div class="age-card">
          <h3><?= e($year) ?></h3>
          <?php foreach ($teams as [$label, $href]): ?>
            <a class="btn btn--navy" href="<?= e(url($href)) ?>"><?= e($label) ?> <?= icon('arrow', 16) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--tint section--compact" id="formular">
  <div class="container container--narrow">
    <div class="form-card"><?= render_form('nabor') ?></div>
  </div>
</section>
