<section class="section section--compact">
  <div class="container contact-grid">
    <div class="form-card">
      <h2 class="form-card__title"><?= e(block('kontakt.form_title')) ?></h2>
      <?= render_form('kontakt') ?>
    </div>

    <aside class="contact-side">
      <div class="info-card info-card--navy">
        <h3><?= icon('mail', 22) ?> E-mail</h3>
        <p><a href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a></p>
        <h3><?= icon('pin', 22) ?> Adresa</h3>
        <p><?= e(block('kontakt.address')) ?></p>
      </div>
      <div class="info-card">
        <span class="kicker"><?= e(block('kontakt.org_kicker')) ?></span>
        <h3><?= e(block('kontakt.org_name')) ?></h3>
        <p><?= e(block('kontakt.org_address')) ?></p>
        <dl>
          <dt>datová schránka ID</dt><dd><?= e(block('kontakt.org_ds')) ?></dd>
          <dt>bankovní účet u Komerční banky</dt><dd><?= e(block('kontakt.org_bank')) ?></dd>
          <dt>IČO</dt><dd><?= e(block('kontakt.org_ico')) ?></dd>
        </dl>
      </div>
    </aside>
  </div>
</section>

<section class="section section--tint section--compact">
  <div class="container">
    <?= section_title(block('kontakt.map_title'), null, 'section-title--center') ?>
    <?= embed_iframe(block('kontakt.map_url'), 420, 'Mapa – Handball Centrum Hostivice', 'embed--map') ?>
  </div>
</section>
