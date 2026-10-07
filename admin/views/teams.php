<div class="toolbar"><a class="btn btn--red" href="<?= e(admin_url('team-edit')) ?>">+ Přidat tým</a>
<span class="muted">Pořadí v tomto seznamu = pořadí v menu „LIONS TÝMY“.</span></div>
<section class="card card--flush">
<table class="tbl">
  <thead><tr><th></th><th>Tým</th><th>Adresa</th><th>V menu</th><th>Soutěže ČSH</th><th>Sportlyzer</th><th></th></tr></thead>
  <tbody>
  <?php $keys = array_keys($GLOBALS['TEAMS']); foreach ($GLOBALS['TEAMS'] as $k => $t): $i = array_search($k, $keys, true); ?>
    <tr>
      <td class="order">
        <?php foreach (['up' => '▲', 'down' => '▼'] as $dir => $ch): if (($dir === 'up' && $i === 0) || ($dir === 'down' && $i === count($keys) - 1)) { echo '<span></span>'; continue; } ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="team-move"><input type="hidden" name="t" value="<?= e($k) ?>"><input type="hidden" name="dir" value="<?= $dir ?>"><button class="iconbtn" title="Posunout"><?= $ch ?></button></form>
        <?php endforeach; ?>
      </td>
      <td><a href="<?= e(admin_url('team-edit', ['t' => $k])) ?>"><b><?= e($t['title']) ?></b></a><br><small class="muted"><?= e($t['label']) ?></small></td>
      <td><a href="<?= e(url($k . '/')) ?>" target="_blank">/<?= e($k) ?>/</a></td>
      <td><?= $t['in_menu'] ? '<span class="tag tag--ok">ano</span>' : '<span class="tag">ne</span>' ?></td>
      <td><?php foreach ($t['csh'] as $c): ?><code><?= e($c['slug']) ?></code><br><?php endforeach; ?><?= $t['csh'] ? '' : '<span class="muted">—</span>' ?></td>
      <td><?= $t['sportlyzer'] ? e((string) $t['sportlyzer']) : '<span class="muted">—</span>' ?></td>
      <td class="actions"><a class="btn btn--sm btn--ghost" href="<?= e(admin_url('team-edit', ['t' => $k])) ?>">Upravit</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</section>
