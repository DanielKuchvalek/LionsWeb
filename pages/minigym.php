<?php $today = date('Y-m-d'); ?>
<section class="section section--compact">
  <div class="container gym-grid">
    <div class="form-card">
      <h2 class="form-card__title"><?= e(block('minigym.title')) ?></h2>
      <p class="gym-service"><?= icon('dumbbell', 22) ?><?= e(minigym_service()) ?></p>

      <?php $gymToken = form_token('minigym'); ?>
      <form class="lions-form" id="gym-form" method="post" action="<?= e(url('api/minigym.php')) ?>" novalidate data-gym data-form-id="minigym"<?= form_human_attr($gymToken) ?>>
        <input type="hidden" name="_token" value="<?= e($gymToken) ?>">
        <input type="hidden" name="_hc" value="">
        <?= honeypot_field() ?>

        <fieldset class="field">
          <legend>Den <span class="req">*</span></legend>
          <div class="days" data-days>
            <?php for ($i = 0; $i < 14; $i++): $d = new DateTimeImmutable("today +$i day"); ?>
              <button type="button" class="day<?= $i === 0 ? ' is-active' : '' ?>" data-day="<?= $d->format('Y-m-d') ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>">
                <small><?= $i === 0 ? 'dnes' : ($i === 1 ? 'zítra' : e(['ne', 'po', 'út', 'st', 'čt', 'pá', 'so'][(int) $d->format('w')])) ?></small>
                <b><?= $d->format('j.') ?></b>
                <small><?= e(['', 'led', 'úno', 'bře', 'dub', 'kvě', 'čvn', 'čvc', 'srp', 'zář', 'říj', 'lis', 'pro'][(int) $d->format('n')]) ?></small>
              </button>
            <?php endfor; ?>
          </div>
          <label class="other-day"><span>Jiný den:</span>
            <input id="gym-date" type="date" name="date" min="<?= $today ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>" value="<?= $today ?>" required>
          </label>
        </fieldset>
        <fieldset class="field">
          <legend>Čas <span class="req">*</span></legend>
          <div class="slots" data-slots><p class="muted">Načítám volné termíny…</p></div>
          <input type="hidden" name="time" required>
        </fieldset>

        <div class="field__row">
          <div class="field"><label for="g-first">Křestní Jméno <span class="req">*</span></label><input id="g-first" name="first" autocomplete="given-name" required></div>
          <div class="field"><label for="g-last">Příjmení <span class="req">*</span></label><input id="g-last" name="last" autocomplete="family-name" required></div>
        </div>
        <div class="field__row">
          <div class="field"><label for="g-email">E-mail <span class="req">*</span></label><input id="g-email" type="email" name="email" autocomplete="email" required></div>
          <div class="field"><label for="g-phone">Telefon <span class="req">*</span></label><?= phone_input('g-phone', 'phone', true) ?></div>
        </div>

        <?= gdpr_checkbox() ?>
        <div class="lions-form__status" role="status" aria-live="polite"></div>
        <button class="btn btn--red btn--lg" type="submit">Rezervovat</button>
      </form>
    </div>

    <a class="flyer" href="<?= e(media(block('minigym.rules'))) ?>" data-zoom><?= img(block('minigym.rules'), 'Pravidla LIONS miniGYM') ?></a>
  </div>
</section>
