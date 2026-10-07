<section class="section">
  <div class="container">
    <?= section_title(block('partneri.title'), null, 'section-title--center') ?>
    <div class="partners-grid">
      <?php foreach (site('partners') as [$href, $logo, $name]): ?>
        <a class="partner" href="<?= e(safe_url($href)) ?>" target="_blank" rel="noopener" title="<?= e($name) ?>"><?= img($logo, $name) ?></a>
      <?php endforeach; ?>
    </div>
    <p class="center-cta"><a class="btn btn--red btn--lg" href="<?= e(url('kup-si-tym/')) ?>">BÝT PATRON <?= icon('arrow', 18) ?></a></p>
  </div>
</section>
