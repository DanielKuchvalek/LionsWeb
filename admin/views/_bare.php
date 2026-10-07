<?php /** @var string $body @var string $title */ ?><!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> – administrace LIONS</title>
  <link rel="stylesheet" href="<?= e(url('assets/css/fonts.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('admin/assets/admin.css')) ?>?v=<?= filemtime(ROOT . '/admin/assets/admin.css') ?>">
</head>
<body class="bare">
  <div class="auth">
    <img src="<?= e(media(site('logo'))) ?>" alt="LIONS" width="90">
    <?php foreach (flash() as $f): ?><div class="flash flash--<?= e($f['type']) ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>
    <?= $body ?>
    <a class="auth__back" href="<?= e(url()) ?>">← zpět na web</a>
  </div>
</body>
</html>
