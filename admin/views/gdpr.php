<?php
$q = trim((string) ($_GET['q'] ?? ''));
$subs = $bks = [];
if (mb_strlen($q) >= 3) {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $st = db()->prepare("SELECT * FROM submissions WHERE data LIKE ? ESCAPE '\\' ORDER BY id DESC LIMIT 500");
    $st->execute([$like]);
    $subs = $st->fetchAll();
    $st = db()->prepare("SELECT * FROM bookings WHERE first || ' ' || last LIKE ? ESCAPE '\\' OR email LIKE ? ESCAPE '\\' OR phone LIKE ? ESCAPE '\\' ORDER BY date DESC LIMIT 500");
    $st->execute([$like, $like, $like]);
    $bks = array_map('minigym_row', $st->fetchAll());
}
$ret = site('retention') ?? [];
$stats = db()->query("SELECT (SELECT COUNT(*) FROM submissions), (SELECT MIN(created) FROM submissions), (SELECT COUNT(*) FROM bookings)")->fetch(PDO::FETCH_NUM);
?>
<div class="grid2">
  <section class="card">
    <h2>Žádost o výmaz nebo přístup k údajům</h2>
    <p class="muted">Když vás někdo požádá o kopii nebo smazání svých údajů (má na to právo podle GDPR), vyhledejte ho podle e-mailu, telefonu nebo jména.
      Odpovědět musíte do jednoho měsíce.</p>
    <form method="get" class="filters">
      <input type="hidden" name="p" value="gdpr">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="e-mail, telefon nebo jméno (min. 3 znaky)" style="min-width:280px" required minlength="3">
      <button class="btn btn--sm">Vyhledat</button>
    </form>
  </section>
  <section class="card">
    <h2>Automatické mazání</h2>
    <p>Formuláře se mažou po <b><?= (int) ($ret['forms'] ?? 24) ?> měsících</b>, rezervace miniGYM po <b><?= (int) ($ret['minigym'] ?? 12) ?> měsících</b>, IP adresy po 30 dnech, zálohy po 30 dnech.
      <a href="<?= e(admin_url('settings')) ?>#uchovani">Změnit lhůty</a></p>
    <p class="muted">Aktuálně uloženo: <?= (int) $stats[0] ?> formulářů<?= $stats[1] ? ' (nejstarší ' . e(date('j. n. Y', strtotime($stats[1]))) . ')' : '' ?>, <?= (int) $stats[2] ?> rezervací.</p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="gdpr-cleanup"><button class="btn btn--sm btn--ghost">Spustit mazání hned</button></form>
  </section>
</div>

<?php if ($q !== ''): ?>
<form method="post" class="card" data-confirm="Opravdu trvale smazat vybrané záznamy?">
  <?= csrf_field() ?><input type="hidden" name="action" value="gdpr-delete"><input type="hidden" name="q" value="<?= e($q) ?>">
  <div class="card__head">
    <h2>Nalezeno: <?= count($subs) + count($bks) ?> záznamů pro „<?= e($q) ?>“</h2>
    <?php if ($subs || $bks): ?>
      <div class="filters">
        <a class="btn btn--sm btn--ghost" href="<?= e(url('admin/api.php?action=gdpr-export&q=' . rawurlencode($q))) ?>">Stáhnout kopii údajů (pro žadatele)</a>
        <button class="btn btn--sm btn--danger">Smazat vybrané</button>
      </div>
    <?php endif; ?>
  </div>
  <p class="muted">Pozor: hledá se podle textu, zkontrolujte, že vybrané záznamy opravdu patří žadateli.</p>
  <?php foreach ($subs as $r): $d = json_decode($r['data'], true) ?: []; ?>
    <label class="gdpr-hit">
      <input type="checkbox" name="sub[]" value="<?= (int) $r['id'] ?>" checked>
      <span><b>Formulář „<?= e($r['form']) ?>“</b> · <?= e(date('j. n. Y H:i', strtotime($r['created']))) ?>
        <small><?= e(implode(' · ', array_slice(array_filter(array_map(fn($k, $v) => in_array($k, form_sensitive_labels($r['form']), true) ? null : ($v !== '' ? $v : null), array_keys($d), $d)), 0, 6))) ?></small></span>
    </label>
  <?php endforeach; ?>
  <?php foreach ($bks as $b): ?>
    <label class="gdpr-hit">
      <input type="checkbox" name="bk[]" value="<?= e($b['id']) ?>" checked>
      <span><b>Rezervace miniGYM</b> · <?= e(minigym_when($b)) ?><small><?= e($b['name'] . ' · ' . $b['email'] . ' · ' . $b['phone']) ?></small></span>
    </label>
  <?php endforeach; ?>
  <?php if (!$subs && !$bks): ?><p>Žádné záznamy.</p><?php endif; ?>
</form>
<?php endif; ?>
