<?php
$id = (string) ($_GET['id'] ?? '');
$b = $id !== '' ? minigym_find($id) : null;
$old = $_SESSION['old'] ?? null;
unset($_SESSION['old']);
$v = $old ?? ($b ?? ['date' => $_GET['date'] ?? date('Y-m-d'), 'time' => $_GET['time'] ?? '']);
$times = [];
foreach (minigym_slot_times() as $t => $end) $times[$t] = "$t – $end";
?>
<div class="toolbar"><a class="btn btn--ghost btn--sm" href="<?= e(admin_url('gym', ['week' => $v['date'] ?? ''])) ?>">← zpět na rezervace</a></div>

<form method="post" class="card form-narrow">
  <h2><?= $b ? 'Upravit rezervaci' : 'Nová rezervace' ?></h2>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="gym-save">
  <input type="hidden" name="id" value="<?= e($b['id'] ?? '') ?>">
  <div class="cols2">
    <label class="af"><span class="af__label">Datum</span><input type="date" name="date" value="<?= e($v['date'] ?? '') ?>" required></label>
    <?= a_select('time', $v['time'] ?? '', 'Čas', ['' => '— vyberte —'] + $times) ?>
    <?= a_text('first', $v['first'] ?? '', 'Křestní jméno', '', ['required' => 'required']) ?>
    <?= a_text('last', $v['last'] ?? '', 'Příjmení') ?>
    <?= a_text('email', $v['email'] ?? '', 'E-mail', '', ['type' => 'email']) ?>
    <?= a_text('phone', $v['phone'] ?? '', 'Telefon') ?>
  </div>
  <?= a_textarea('note', $v['note'] ?? '', 'Interní poznámka (na webu se nezobrazuje)', '', 2) ?>
  <?= a_check('force', false, 'Povolit i když je termín obsazený', 'Např. pro skupinový trénink nebo blokaci posilovny.') ?>
  <?php if ($b): ?><p class="muted">Vytvořeno <?= e(date('j. n. Y H:i', strtotime($b['created'] ?? 'now'))) ?> (<?= e(($b['source'] ?? 'web') === 'admin' ? 'administrace' : 'web') ?>)<?= isset($b['updated']) ? ', upraveno ' . e(date('j. n. Y H:i', strtotime($b['updated']))) : '' ?>.</p><?php endif; ?>
  <div class="row">
    <button class="btn btn--red"><?= $b ? 'Uložit změny' : 'Přidat rezervaci' ?></button>
  </div>
</form>
<?php if ($b): ?>
<form method="post" class="form-narrow danger-zone" data-confirm="Opravdu smazat tuto rezervaci?">
  <?= csrf_field() ?><input type="hidden" name="action" value="gym-delete"><input type="hidden" name="id" value="<?= e($b['id']) ?>">
  <input type="hidden" name="_back" value="<?= e(admin_url('gym', ['week' => $b['date']])) ?>">
  <button class="btn btn--danger btn--sm">Smazat rezervaci</button>
</form>
<?php endif; ?>
