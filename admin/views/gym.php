<?php
$all = minigym_load();
$ref = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($_GET['week'] ?? '')) ?: new DateTimeImmutable('today');
$monday = $ref->modify('monday this week');
$days = [];
for ($i = 0; $i < 7; $i++) $days[] = $monday->modify("+$i day");
$byDay = [];
foreach ($all as $b) $byDay[$b['date']][] = $b;
function minigym_slots_cache(string $date): array
{
    static $c = [];
    return $c[$date] ??= minigym_slots($date);
}
$filter = (string) ($_GET['f'] ?? 'upcoming');
$q = trim((string) ($_GET['q'] ?? ''));
$today = date('Y-m-d');
$list = array_filter($all, function ($b) use ($filter, $today, $q) {
    if ($filter === 'upcoming' && $b['date'] < $today) return false;
    if ($filter === 'past' && $b['date'] >= $today) return false;
    if ($q !== '' && mb_stripos($b['name'] . ' ' . $b['email'] . ' ' . $b['phone'] . ' ' . ($b['note'] ?? ''), $q) === false) return false;
    return true;
});
if ($filter === 'past') $list = array_reverse($list);
$dn = ['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne'];
?>
<div class="toolbar">
  <a class="btn btn--red" href="<?= e(admin_url('gym-edit')) ?>">+ Přidat rezervaci</a>
  <div class="toolbar__nav">
    <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('gym', ['week' => $monday->modify('-7 day')->format('Y-m-d')])) ?>">← předchozí týden</a>
    <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('gym')) ?>">tento týden</a>
    <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('gym', ['week' => $monday->modify('+7 day')->format('Y-m-d')])) ?>">další týden →</a>
    <form method="get" class="inline"><input type="hidden" name="p" value="gym"><input type="date" name="week" value="<?= e($ref->format('Y-m-d')) ?>" onchange="this.form.submit()"></form>
  </div>
</div>

<section class="card card--flush">
  <h2 class="card__title">Týden <?= e($monday->format('j. n.')) ?> – <?= e($days[6]->format('j. n. Y')) ?></h2>
  <div class="week-wrap">
    <table class="week">
      <thead><tr><th></th>
        <?php foreach ($days as $i => $d): ?><th class="<?= $d->format('Y-m-d') === $today ? 'is-today' : '' ?>"><?= $dn[$i] ?> <small><?= $d->format('j. n.') ?></small></th><?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php foreach (minigym_slot_times() as $time => $end): $t1 = hm_to_min($time); $t2 = $t1 + minigym_step(); ?>
        <tr><th><?= e($time) ?></th>
          <?php foreach ($days as $d):
            $ds = $d->format('Y-m-d');
            $starting = $covering = [];
            foreach ($byDay[$ds] ?? [] as $b) {
                $b1 = hm_to_min($b['time']); $b2 = hm_to_min($b['end']);
                if ($b1 >= $t1 && $b1 < $t2) $starting[] = $b;                 // začíná v tomto řádku
                elseif (minigym_overlaps($t1, $t2, $b1, $b2)) $covering[] = $b; // pokračuje z dřívějška
            }
            $free = true;
            foreach (minigym_slots_cache($ds) as $sl) if ($sl['time'] === $time) $free = $sl['taken'] < minigym_capacity();
            $past = "$ds $time" < date('Y-m-d H:i'); ?>
            <td class="<?= ($starting || $covering) ? 'is-booked' : '' ?><?= $past ? ' is-past' : '' ?>">
              <?php foreach ($starting as $b): ?>
                <a class="bk" href="<?= e(admin_url('gym-edit', ['id' => $b['id']])) ?>" title="<?= e($b['name'] . ' · ' . $b['time'] . '–' . $b['end'] . ' · ' . $b['phone'] . ' · ' . $b['email']) ?>"><?= e($b['time']) ?> <?= e($b['name']) ?></a>
              <?php endforeach; ?>
              <?php foreach ($covering as $b): ?>
                <a class="bk bk--cont" href="<?= e(admin_url('gym-edit', ['id' => $b['id']])) ?>" title="<?= e($b['name'] . ' · ' . $b['time'] . '–' . $b['end']) ?>">↳ <?= e($b['name']) ?></a>
              <?php endforeach; ?>
              <?php if ($free && !$past): ?><a class="add" href="<?= e(admin_url('gym-edit', ['date' => $ds, 'time' => $time])) ?>" aria-label="Přidat">+</a><?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="card">
  <div class="card__head">
    <h2>Seznam rezervací</h2>
    <form method="get" class="filters">
      <input type="hidden" name="p" value="gym">
      <select name="f" onchange="this.form.submit()">
        <option value="upcoming"<?= $filter === 'upcoming' ? ' selected' : '' ?>>Nadcházející</option>
        <option value="past"<?= $filter === 'past' ? ' selected' : '' ?>>Proběhlé</option>
        <option value="all"<?= $filter === 'all' ? ' selected' : '' ?>>Všechny</option>
      </select>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Hledat jméno, e-mail, telefon…">
      <button class="btn btn--sm">Filtrovat</button>
      <a class="btn btn--sm btn--ghost" href="<?= e(url('admin/api.php?action=gym-csv')) ?>">Export CSV</a>
    </form>
  </div>
  <div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>Termín</th><th>Jméno</th><th>E-mail</th><th>Telefon</th><th>Poznámka</th><th>Vytvořeno</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($list as $b): ?>
      <tr>
        <td class="nowrap"><b><?= e((new DateTimeImmutable($b['date']))->format('j. n. Y')) ?></b> <?= e($b['time']) ?>–<?= e($b['end']) ?></td>
        <td><?= e($b['name']) ?></td>
        <td><?php if ($b['email']): ?><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a><?php endif; ?></td>
        <td class="nowrap"><?php if ($b['phone']): ?><a href="tel:<?= e(preg_replace('/\s+/', '', $b['phone'])) ?>"><?= e($b['phone']) ?></a><?php endif; ?></td>
        <td><?= e($b['note'] ?? '') ?></td>
        <td class="muted nowrap"><?= e(isset($b['created']) ? date('j. n. H:i', strtotime($b['created'])) : '') ?> <?= ($b['source'] ?? 'web') === 'admin' ? '<span class="tag">admin</span>' : '' ?></td>
        <td class="actions">
          <a class="btn btn--sm btn--ghost" href="<?= e(admin_url('gym-edit', ['id' => $b['id']])) ?>">Upravit</a>
          <form method="post" data-confirm="Opravdu smazat rezervaci <?= e($b['name'] . ' ' . minigym_when($b)) ?>?">
            <?= csrf_field() ?><input type="hidden" name="action" value="gym-delete"><input type="hidden" name="id" value="<?= e($b['id']) ?>">
            <input type="hidden" name="_back" value="<?= e($_SERVER['REQUEST_URI']) ?>">
            <button class="btn btn--sm btn--danger">Smazat</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="7" class="muted">Žádné rezervace.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</section>
