<?php
$id = (string) ($_REQUEST['r'] ?? '');
$token = (string) ($_REQUEST['t'] ?? '');
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = minigym_cancel_by_token($id, $token);
}
$b = $result ? null : minigym_find_by_token($id, $token);
?>
<section class="section section--compact">
  <div class="container container--narrow">
    <div class="form-card">
      <?php if ($result): ?>
        <h2 class="form-card__title"><?= $result['ok'] ? 'Rezervace zrušena' : 'Zrušení se nepodařilo' ?></h2>
        <div class="lions-form__status <?= $result['ok'] ? 'is-ok' : 'is-error' ?>"><?= e($result['message']) ?></div>
      <?php elseif (!$b): ?>
        <h2 class="form-card__title">Rezervace nenalezena</h2>
        <p>Odkaz je neplatný, nebo už byla rezervace zrušena.</p>
      <?php elseif (!minigym_is_future($b)): ?>
        <h2 class="form-card__title">Termín už proběhl</h2>
        <p>Rezervaci <?= e(minigym_when($b)) ?> už nelze zrušit.</p>
      <?php else: ?>
        <h2 class="form-card__title">Zrušit rezervaci?</h2>
        <p>Opravdu chcete zrušit rezervaci LIONS miniGYM?</p>
        <p><strong><?= e(ucfirst(minigym_day_label($b))) ?>, <?= e($b['time']) ?>–<?= e($b['end']) ?></strong><br><?= e($b['name']) ?></p>
        <form method="post">
          <input type="hidden" name="r" value="<?= e($id) ?>">
          <input type="hidden" name="t" value="<?= e($token) ?>">
          <button class="btn btn--red btn--lg" type="submit">Ano, zrušit rezervaci</button>
        </form>
      <?php endif; ?>
      <p style="margin-top:20px"><a href="<?= e(url('mini-gym/')) ?>">← zpět na rezervace miniGYM</a></p>
    </div>
  </div>
</section>
