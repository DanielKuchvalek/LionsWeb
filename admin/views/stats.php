<?php
/** Návštěvnost a prokliky – anonymní denní součty z tabulky stats (lib/stats.php) */
$periods = [1 => 'Dnes', 7 => '7 dní', 30 => '30 dní', 90 => '3 měsíce', 365 => 'Rok'];
$days = (int) ($_GET['d'] ?? 30);
$days = isset($periods[$days]) ? $days : 30;
$page = isset($_GET['page']) && $_GET['page'] !== '' ? (string) $_GET['page'] : null;
$scope = $page !== null ? ['page' => $page] : [];

$views  = stats_total('view', $days, $page);
$clicks = stats_total('click', $days, $page);
$daily  = stats_daily($days, $page);
$maxDay = max(1, ...array_values(array_map(fn($d) => max($d["view"], $d["click"]), $daily)));

$topPages    = stats_top('view', ['page'], $days, 25);
$topClicks   = stats_top('click', ['label', 'target', 'section'], $days, 30, $scope);
$bySection   = stats_top('click', ['section'], $days, 15, $scope);
$outbound    = stats_top('click', ['target', 'label'], $days, 20, $scope + ['target' => '%.%']);
$referrers   = stats_top('view', ['target'], $days, 15, $scope + ['target' => '!empty']);
$devices     = stats_top('view', ['label'], $days, 5, $scope);
$devTotal    = max(1, array_sum(array_column($devices, 'n')));

$q = fn(array $extra) => admin_url('stats', array_filter(['d' => $days, 'page' => $page] + $extra, fn($v) => $v !== null));
$bar = fn(int $n, int $max) => '<span class="sbar"><i style="width:' . round($n / max(1, $max) * 100, 1) . '%"></i></span>';
$niceTarget = function (string $t): string {
    if ($t === '') return '–';
    if (preg_match('~^(mailto|tel):(.*)$~i', $t, $m)) return (strtolower($m[1]) === 'tel' ? '☎ ' : '✉ ') . $m[2];
    return $t;
};
?>
<div class="filters stats-filters">
  <?php foreach ($periods as $d => $label): ?>
    <a class="btn btn--sm<?= $d === $days ? ' btn--red' : '' ?>" href="<?= e(admin_url('stats', array_filter(['d' => $d, 'page' => $page]))) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
  <?php if ($page !== null): ?>
    <span class="tag tag--warn">jen stránka <?= e($page) ?></span> <a href="<?= e(admin_url('stats', ['d' => $days])) ?>">zobrazit celý web</a>
  <?php endif; ?>
</div>

<div class="tiles">
  <div class="tile"><b><?= number_format($views, 0, ',', ' ') ?></b><span>zobrazení stránek</span><small><?= e($periods[$days]) ?></small></div>
  <div class="tile"><b><?= number_format($clicks, 0, ',', ' ') ?></b><span>kliknutí</span><small><?= $views ? number_format($clicks / $views, 1, ",", "") : 0 ?> na jedno zobrazení</small></div>
  <?php foreach ($devices as $dv): ?>
    <div class="tile"><b><?= round($dv['n'] / $devTotal * 100) ?> %</b><span><?= e($dv['label'] ?: 'neznámé') ?></span><small><?= (int) $dv['n'] ?> zobrazení</small></div>
  <?php endforeach; ?>
</div>

<?php if ($days > 1): ?>
<section class="card">
  <h2>Po dnech</h2>
  <div class="schart" style="--cols:<?= count($daily) ?>">
    <?php foreach ($daily as $day => $v): ?>
      <div class="schart__col" title="<?= e(date('j. n. Y', strtotime($day))) ?>: <?= $v['view'] ?> zobrazení, <?= $v['click'] ?> kliknutí">
        <i class="schart__v" style="height:<?= round($v['view'] / $maxDay * 100, 1) ?>%"></i>
        <i class="schart__c" style="height:<?= round($v['click'] / $maxDay * 100, 1) ?>%"></i>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="muted schart__legend"><span class="dot dot--v"></span> zobrazení <span class="dot dot--c"></span> kliknutí · <?= e(date('j. n.', strtotime(array_key_first($daily)))) ?> – <?= e(date('j. n.', strtotime(array_key_last($daily)))) ?></p>
</section>
<?php endif; ?>

<?php if (!$views && !$clicks): ?>
  <section class="card"><p class="muted">Za toto období zatím nejsou žádná data. Statistika se začne plnit s prvními návštěvníky (vaše vlastní návštěvy z tohoto prohlížeče se nepočítají).</p></section>
<?php endif; ?>

<div class="grid2">
  <section class="card">
    <h2>Na co se kliká<?= $page !== null ? ' – ' . e($page) : '' ?></h2>
    <div class="tbl-wrap"><table class="tbl">
      <tr><th>Co</th><th>Kde</th><th>Kam vede</th><th>Počet</th></tr>
      <?php $max = (int) ($topClicks[0]['n'] ?? 1); foreach ($topClicks as $r): ?>
        <tr><td><b><?= e($r['label']) ?></b></td><td><?= e($r['section']) ?></td><td class="muted"><?= e($niceTarget($r['target'])) ?></td><td class="num"><?= (int) $r['n'] ?><?= $bar((int) $r['n'], $max) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
  </section>

  <section class="card">
    <h2>Navštěvované stránky</h2>
    <p class="muted">Kliknutím na stránku zobrazíte, na co se klikalo právě na ní.</p>
    <div class="tbl-wrap"><table class="tbl">
      <tr><th>Stránka</th><th>Zobrazení</th></tr>
      <?php $max = (int) ($topPages[0]['n'] ?? 1); foreach ($topPages as $r): ?>
        <tr<?= $r['page'] === $page ? ' class="is-sel"' : '' ?>><td><a href="<?= e($q(['page' => $r['page']])) ?>"><?= e($r['page']) ?></a></td><td class="num"><?= (int) $r['n'] ?><?= $bar((int) $r['n'], $max) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
  </section>

  <section class="card">
    <h2>Části stránek</h2>
    <p class="muted">Kde na stránce lidé klikají nejvíc (menu, patička, sekce podle nadpisu…).</p>
    <table class="tbl">
      <?php $max = (int) ($bySection[0]['n'] ?? 1); foreach ($bySection as $r): ?>
        <tr><td><?= e($r['section'] ?: '–') ?></td><td class="num"><?= (int) $r['n'] ?><?= $bar((int) $r['n'], $max) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </section>

  <section class="card">
    <h2>Odchody na jiné weby</h2>
    <p class="muted">Partneři, sociální sítě, handball.cz, Sportlyzer…</p>
    <table class="tbl">
      <?php $max = (int) ($outbound[0]['n'] ?? 1); foreach ($outbound as $r): ?>
        <tr><td><b><?= e($r['label']) ?></b><br><small class="muted"><?= e($r['target']) ?></small></td><td class="num"><?= (int) $r['n'] ?><?= $bar((int) $r['n'], $max) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </section>

  <section class="card">
    <h2>Odkud lidé přicházejí</h2>
    <p class="muted">Web, ze kterého návštěvník přišel (Google, Facebook…). Přímé návštěvy a záložky se nepočítají.</p>
    <table class="tbl">
      <?php $max = (int) ($referrers[0]['n'] ?? 1); foreach ($referrers as $r): ?>
        <tr><td><?= e($r['target']) ?></td><td class="num"><?= (int) $r['n'] ?><?= $bar((int) $r['n'], $max) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </section>

  <section class="card">
    <h2>Jak statistika funguje</h2>
    <ul class="muted">
      <li><b>Zobrazení</b> = otevření stránky. <b>Kliknutí</b> = klik na odkaz, tlačítko, logo partnera nebo do vloženého widgetu (Sportlyzer, mapa).</li>
      <li>Je <b>anonymní</b>: žádné cookies, žádné IP adresy, nikdo se nedá dohledat – ukládají se jen denní součty. Proto web nepotřebuje cookie lištu.</li>
      <li>Nepočítají se roboti vyhledávačů ani správci (prohlížeč, ve kterém jste se přihlásili do administrace).</li>
      <li>Co se děje <i>uvnitř</i> widgetů Sportlyzeru nebo Facebooku, web vidět nemůže – jen že do nich někdo klikl.</li>
      <li>Data starší 13 měsíců se automaticky mažou.</li>
    </ul>
  </section>
</div>
