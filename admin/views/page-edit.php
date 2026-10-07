<?php
$g = (string) ($_GET['g'] ?? '');
$def = blocks_registry()[$g] ?? null;
if (!$def) { echo '<p>Stránka neexistuje.</p>'; return; }
?>
<div class="toolbar">
  <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('pages')) ?>">← všechny stránky</a>
  <a class="btn btn--ghost btn--sm" href="<?= e(url($def['url'])) ?>" target="_blank">Zobrazit stránku ↗</a>
</div>
<form method="post" class="editor">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="blocks-save">
  <input type="hidden" name="group" value="<?= e($g) ?>">
  <section class="card">
    <h2><?= e($def['label']) ?></h2>
    <p class="muted">Tip: v delších textech oddělte odstavce prázdným řádkem, <code>**text**</code> = tučně.</p>
    <?php foreach ($def['fields'] as $field => $f):
      $name = "f[$field]";
      $val = block("$g.$field");
      switch ($f['type']) {
          case 'image':  echo a_media($name, $val, $f['label']); break;
          case 'video':  echo a_media($name, $val, $f['label'], 'video'); break;
          case 'images': echo a_media_list($name, $val, $f['label']); break;
          case 'textarea': echo a_textarea($name, $val, $f['label'], '', max(3, min(12, substr_count($val, "\n") + 3)), $f['default']); break;
          case 'lines':  echo a_textarea($name, $val, $f['label'], 'Jedna položka na řádek.', max(3, min(12, substr_count($val, "\n") + 2)), $f['default']); break;
          default:
              $reset = $val !== $f['default'] ? '<button type="button" class="linkbtn" data-reset="' . e($f['default']) . '">Vrátit původní</button>' : '';
              echo '<label class="af"><span class="af__label">' . e($f['label']) . $reset . '</span><input type="text" name="' . e($name) . '" value="' . e($val) . '"></label>';
      }
    endforeach; ?>
  </section>
  <div class="savebar"><button class="btn btn--red">Uložit změny</button></div>
</form>
