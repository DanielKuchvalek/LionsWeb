<?php $over = blocks_overrides(); ?>
<p class="muted">Texty a obrázky jednotlivých stránek. Výchozí obsah je převzatý z původního webu – kdykoli se k němu můžete vrátit tlačítkem „Vrátit původní“.
Týmy upravíte v sekci <a href="<?= e(admin_url('teams')) ?>">Týmy</a>, partnery a odkazy v <a href="<?= e(admin_url('settings')) ?>">Nastavení</a>.</p>
<div class="page-list">
  <?php foreach (blocks_registry() as $g => $def):
    $changed = count(array_filter(array_keys($over), fn($k) => str_starts_with($k, "$g."))); ?>
    <a class="card page-item" href="<?= e(admin_url('page-edit', ['g' => $g])) ?>">
      <b><?= e($def['label']) ?></b>
      <small>/<?= e($def['url']) ?></small>
      <span class="muted"><?= count($def['fields']) ?> polí<?= $changed ? " · <span class=\"tag tag--ok\">upraveno $changed</span>" : '' ?></span>
    </a>
  <?php endforeach; ?>
</div>
