<?php
$id = preg_replace('/[^a-z0-9\-]/', '', (string) ($_GET['f'] ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 50;
$where = 'form = ?';
$args = [$id];
if ($q !== '') { $where .= ' AND data LIKE ?'; $args[] = '%' . $q . '%'; }
$cnt = db()->prepare("SELECT COUNT(*) FROM submissions WHERE $where");
$cnt->execute($args);
$total = (int) $cnt->fetchColumn();
$st = db()->prepare("SELECT * FROM submissions WHERE $where ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($args);
$rows = $st->fetchAll();
$sensitive = form_sensitive_labels($id);
?>
<div class="toolbar">
  <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('forms')) ?>">← všechny formuláře</a>
  <form method="get" class="filters">
    <input type="hidden" name="p" value="form-view"><input type="hidden" name="f" value="<?= e($id) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Hledat jméno, e-mail…">
    <button class="btn btn--sm">Hledat</button>
    <?php if ($total): ?><a class="btn btn--sm" href="<?= e(url('admin/api.php?action=csv&f=' . $id)) ?>">Stáhnout pro Excel (CSV)</a><?php endif; ?>
  </form>
</div>
<?php if (!$rows): ?><section class="card"><p class="muted">Žádné záznamy.</p></section><?php endif; ?>
<div class="entries">
  <?php foreach ($rows as $r): $data = json_decode($r['data'], true) ?: []; ?>
    <article class="card entry">
      <header>
        <b><?= e(date('j. n. Y H:i', strtotime($r['created']))) ?></b>
        <span>
          <?php if ($r['mail_status'] === 'sent'): ?><span class="tag tag--ok">e-mail odeslán</span>
          <?php else: ?><span class="tag tag--warn" title="<?= e((string) $r['mail_error']) ?>">e-mail neodešel</span>
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="submission-resend"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="_back" value="<?= e($_SERVER['REQUEST_URI']) ?>"><button class="linkbtn">poslat znovu</button></form>
          <?php endif; ?>
          <form method="post" class="inline" data-confirm="Smazat tento záznam?">
            <?= csrf_field() ?><input type="hidden" name="action" value="submission-delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="_back" value="<?= e($_SERVER['REQUEST_URI']) ?>">
            <button class="linkbtn linkbtn--danger">smazat</button>
          </form>
        </span>
      </header>
      <dl>
        <?php foreach ($data as $h => $v): if ($v === '') continue; ?>
          <dt><?= e($h) ?></dt>
          <dd><?php if (in_array($h, $sensitive, true)): ?><details class="secret"><summary>•••••• zobrazit</summary><?= e($v) ?></details><?php elseif (filter_var($v, FILTER_VALIDATE_EMAIL)): ?><a href="mailto:<?= e($v) ?>"><?= e($v) ?></a><?php elseif (preg_match('/^[+0-9 ()\/-]{9,20}$/', $v)): ?><a href="tel:<?= e(preg_replace('/\s+/', '', $v)) ?>"><?= e($v) ?></a><?php else: ?><?= nl2br(e($v)) ?><?php endif; ?></dd>
        <?php endforeach; ?>
      </dl>
    </article>
  <?php endforeach; ?>
</div>
<?php if ($total > $per): ?>
  <div class="row">
    <?php for ($i = 1; $i <= (int) ceil($total / $per); $i++): ?>
      <a class="btn btn--sm <?= $i === $page ? '' : 'btn--ghost' ?>" href="<?= e(admin_url('form-view', ['f' => $id, 'q' => $q, 'page' => $i])) ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>
