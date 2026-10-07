<?php
$q = trim((string) ($_GET['q'] ?? ''));
$type = (string) ($_GET['type'] ?? '');
$items = media_list($type, $q);
$shown = array_slice($items, 0, 300);
?>
<section class="card">
  <form method="post" enctype="multipart/form-data" class="dropzone" data-dropzone>
    <?= csrf_field() ?><input type="hidden" name="action" value="media-upload">
    <p><b>Přetáhněte sem fotky nebo videa</b> nebo <label class="btn btn--red btn--sm">vyberte soubory<input type="file" name="files[]" multiple accept="image/*,video/mp4" hidden onchange="this.form.submit()"></label></p>
    <small class="muted">JPG, PNG, GIF, WEBP, MP4 · max <?= e(ini_get('upload_max_filesize')) ?> na soubor · velké fotky se automaticky zmenší na 2000 px.</small>
  </form>
</section>

<section class="card">
  <div class="card__head">
    <h2>Knihovna (<?= count($items) ?>)</h2>
    <form method="get" class="filters">
      <input type="hidden" name="p" value="media">
      <select name="type" onchange="this.form.submit()">
        <option value="">Vše</option><option value="image"<?= $type === 'image' ? ' selected' : '' ?>>Obrázky</option><option value="video"<?= $type === 'video' ? ' selected' : '' ?>>Videa</option>
      </select>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Hledat…">
      <button class="btn btn--sm">Hledat</button>
    </form>
  </div>
  <div class="media-grid">
    <?php foreach ($shown as $m): ?>
      <figure class="media-item">
        <a href="<?= e($m['url']) ?>" target="_blank"><?= $m['type'] === 'image' ? '<img src="' . e($m['url']) . '" loading="lazy" alt="">' : '<video src="' . e($m['url']) . '#t=0.5" preload="metadata" muted></video>' ?></a>
        <figcaption>
          <span title="<?= e($m['path']) ?>"><?= e($m['name']) ?></span>
          <small><?= human_size($m['size']) ?> · <?= date('j. n. Y', $m['mtime']) ?></small>
          <div class="media-item__actions">
            <button type="button" class="linkbtn" data-copy="<?= e($m['path']) ?>">kopírovat cestu</button>
            <?php if ($m['own']): ?>
              <form method="post" data-confirm="Smazat soubor <?= e($m['name']) ?>? Pokud je někde použitý, přestane se zobrazovat.">
                <?= csrf_field() ?><input type="hidden" name="action" value="media-delete"><input type="hidden" name="path" value="<?= e($m['path']) ?>">
                <button class="linkbtn linkbtn--danger">smazat</button>
              </form>
            <?php endif; ?>
          </div>
        </figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
  <?php if (count($items) > count($shown)): ?><p class="muted">Zobrazeno prvních <?= count($shown) ?> – upřesněte hledání.</p><?php endif; ?>
</section>
