<?php
$today = date('Y-m-d');
$gymToday = minigym_load($today, $today);
$gymNext = minigym_load($today);
$formCounts = db()->query('SELECT form, COUNT(*) FROM submissions GROUP BY form')->fetchAll(PDO::FETCH_KEY_PAIR);
$failedMails = (int) db()->query("SELECT COUNT(*) FROM submissions WHERE mail_status <> 'sent'")->fetchColumn();
$upcoming = csh_upcoming_all(5);
$cacheFiles = glob(STORAGE . '/cache/csh_*.json') ?: [];
$cacheAge = $cacheFiles ? time() - min(array_map('filemtime', $cacheFiles)) : null;
$discover = isset($_GET['discover']) ? ($_SESSION['discover'] ?? null) : null;
?>
<?php if ($failedMails): ?><div class="flash flash--error">⚠ <?= $failedMails ?> formulářů se nepodařilo poslat e-mailem (data jsou uložená). Zkontrolujte <a href="<?= e(admin_url('health')) ?>">Stav systému</a> a nastavení SMTP.</div><?php endif; ?>
<div class="tiles">
  <a class="tile" href="<?= e(admin_url('gym')) ?>"><b><?= count($gymToday) ?></b><span>rezervací miniGYM dnes</span><small><?= count($gymNext) ?> nadcházejících celkem</small></a>
  <a class="tile" href="<?= e(admin_url('forms')) ?>"><b><?= array_sum($formCounts) ?></b><span>přijatých formulářů</span><small><?= count($formCounts) ?> typů formulářů</small></a>
  <a class="tile" href="<?= e(admin_url('teams')) ?>"><b><?= count($GLOBALS['TEAMS']) ?></b><span>týmů na webu</span><small><?= count(array_filter($GLOBALS['TEAMS'], fn($t) => $t['in_menu'])) ?> v menu</small></a>
  <a class="tile" href="<?= e(admin_url('stats', ['d' => 7])) ?>"><b><?= number_format(stats_total('view', 7), 0, ',', ' ') ?></b><span>zobrazení za 7 dní</span><small><?= number_format(stats_total('click', 7), 0, ',', ' ') ?> kliknutí · návštěvnost</small></a>
  <a class="tile" href="<?= e(admin_url('media')) ?>"><b>+</b><span>nahrát fotky</span><small>obrázky a videa</small></a>
</div>

<div class="grid2">
  <section class="card">
    <h2>Nejbližší utkání (handball.cz)</h2>
    <?php if (!$upcoming): ?><p class="muted">Žádná naplánovaná utkání nebo data zatím nejsou načtená.</p><?php endif; ?>
    <table class="tbl">
      <?php foreach ($upcoming as $m): ?>
        <tr><td><?= e($m['start']?->format('j. n. H:i')) ?></td><td><?= e(team_tag((string) (team($m['team_key'])['home_tag'] ?? ''))) ?></td><td><?= e($m['home']) ?> – <?= e($m['away']) ?></td></tr>
      <?php endforeach; ?>
    </table>
    <form method="post" class="row">
      <?= csrf_field() ?><input type="hidden" name="action" value="cache-clear">
      <button class="btn btn--sm">Načíst data z handball.cz znovu</button>
      <small class="muted"><?= $cacheAge !== null ? 'Nejstarší data jsou ' . max(1, (int) round($cacheAge / 60)) . ' min stará.' : '' ?> Obnovují se automaticky každých 15 min, během zápasu každou minutu.</small>
    </form>
  </section>

  <section class="card">
    <h2>Nová sezóna – soutěže ČSH</h2>
    <p class="muted">Najde na handball.cz všechny soutěže, ve kterých je přihlášený tým „<?= e(csh_cfg('team_name')) ?>“. Slug soutěže pak vložte u příslušného týmu.</p>
    <form method="post" data-busy="Hledám… (může trvat až 30 s)">
      <?= csrf_field() ?><input type="hidden" name="action" value="csh-discover">
      <button class="btn btn--sm">Vyhledat soutěže LIONS</button>
    </form>
    <?php if ($discover !== null): ?>
      <table class="tbl">
        <tr><th>Soutěž</th><th>Slug</th><th>Pohlaví</th><th>Stav</th><th>Přiřazeno</th></tr>
        <?php foreach ($discover as $c):
          $assigned = [];
          foreach ($GLOBALS['TEAMS'] as $k => $t) foreach ($t['csh'] as $x) if ($x['slug'] === $c['slug']) $assigned[] = $t['title']; ?>
          <tr><td><?= e($c['name']) ?></td><td><code><?= e($c['slug']) ?></code></td><td><?= e($c['sex']) ?></td>
            <td><?= $c['active'] ? '<span class="tag tag--ok">běží</span>' : '<span class="tag">bez rozpisu</span>' ?></td>
            <td><?= $assigned ? e(implode(', ', $assigned)) : '<span class="tag tag--warn">nepřiřazeno</span>' ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$discover): ?><tr><td colspan="5">Nic nenalezeno.</td></tr><?php endif; ?>
      </table>
    <?php endif; ?>
  </section>
</div>

<section class="card">
  <h2>Rychlé odkazy</h2>
  <div class="quick">
    <a href="<?= e(admin_url('page-edit', ['g' => 'home'])) ?>">Texty úvodní stránky</a>
    <a href="<?= e(admin_url('gym-edit')) ?>">Přidat rezervaci miniGYM</a>
    <a href="<?= e(admin_url('team-edit')) ?>">Přidat tým</a>
    <a href="<?= e(admin_url('settings')) ?>#partneri">Partneři</a>
    <a href="<?= e(admin_url('settings')) ?>#odkazy">Odkazy „Rozpisy a výsledky“</a>
  </div>
</section>
