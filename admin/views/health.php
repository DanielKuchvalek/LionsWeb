<?php
/** Kontrola, že vše potřebné na hostingu funguje – projděte po nasazení. */
$checks = [];
$add = function (string $state, string $title, string $detail = '') use (&$checks) { $checks[] = [$state, $title, $detail]; };

// PHP a rozšíření
$add(version_compare(PHP_VERSION, '8.1.0', '>=') ? 'ok' : 'err', 'PHP ' . PHP_VERSION, 'Potřeba verze 8.1 nebo novější.');
foreach (['pdo_sqlite' => 'databáze (formuláře, rezervace)', 'curl' => 'načítání dat z handball.cz', 'openssl' => 'šifrované spojení', 'mbstring' => 'čeština', 'fileinfo' => 'kontrola nahraných souborů', 'gd' => 'zmenšování nahraných fotek', 'zip' => 'stažení zálohy'] as $ext => $why) {
    $add(extension_loaded($ext) ? 'ok' : (in_array($ext, ['gd', 'zip'], true) ? 'warn' : 'err'), "Rozšíření $ext", $why);
}

// Zápis do složek
foreach (['storage', 'storage/cache', 'storage/content', 'storage/backups', 'assets/media'] as $d) {
    $path = ROOT . '/' . $d;
    if (!is_dir($path)) @mkdir($path, 0775, true);
    $test = $path . '/.write-test-' . getmypid();
    $ok = @file_put_contents($test, 'x') !== false;
    @unlink($test);
    $add($ok ? 'ok' : 'err', "Zápis do $d/", $ok ? '' : 'Nastavte práva k zápisu pro PHP (např. 775 nebo 777).');
}

// Databáze
try {
    $pdo = db();
    $qc = $pdo->query('PRAGMA quick_check')->fetchColumn();
    $mode = $pdo->query('PRAGMA journal_mode')->fetchColumn();
    $sub = $pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn();
    $bk = $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
    $add($qc === 'ok' ? 'ok' : 'err', 'Databáze', "kontrola: $qc · režim: $mode · formulářů: $sub · rezervací: $bk · velikost: " . human_size((int) @filesize(STORAGE . '/lions.sqlite')));
} catch (Throwable $e) {
    $add('err', 'Databáze', $e->getMessage());
}
$backups = glob(STORAGE . '/backups/lions-*.sqlite') ?: [];
$add($backups ? 'ok' : 'warn', 'Automatické zálohy', $backups ? count($backups) . ' záloh, poslední ' . basename(end($backups)) : 'Zatím žádná – vytvoří se automaticky při první návštěvě dne.');

// E-maily
$smtp = site('smtp') ?? [];
$add(!empty($smtp['host']) ? 'ok' : 'warn', 'Odesílání e-mailů', !empty($smtp['host']) ? 'SMTP: ' . $smtp['host'] : 'Používá se mail() hostingu – doporučujeme nastavit SMTP v Nastavení.');
try {
    $failed = (int) db()->query("SELECT COUNT(*) FROM submissions WHERE mail_status <> 'sent' AND created > datetime('now', '-30 days')")->fetchColumn();
    $lastErr = db()->query("SELECT mail_error FROM submissions WHERE mail_status = 'failed' ORDER BY id DESC LIMIT 1")->fetchColumn();
    $add($failed ? 'warn' : 'ok', 'Doručení e-mailů (30 dní)', $failed ? "$failed neodeslaných – data jsou uložená v administraci. Poslední chyba: $lastErr" : 'Vše odesláno.');
} catch (Throwable $e) {
}

// handball.cz
$t = microtime(true);
$probe = csh_http_get(csh_cfg('base') . '/api/public/competition/' . rawurlencode((string) ($GLOBALS['TEAMS'][array_key_first(array_filter($GLOBALS['TEAMS'], fn($x) => !empty($x['csh'])))]['csh'][0]['slug'] ?? '1-liga-zeny')));
$add($probe ? 'ok' : 'err', 'Spojení s handball.cz', $probe ? 'odpověď za ' . round((microtime(true) - $t) * 1000) . ' ms' : 'Server se nepodařilo kontaktovat – web zobrazuje poslední uložená data.');
$cache = glob(STORAGE . '/cache/csh_*.json') ?: [];
$add($cache ? 'ok' : 'warn', 'Cache výsledků', $cache ? count($cache) . ' souborů, nejnovější před ' . max(0, (int) round((time() - max(array_map('filemtime', $cache))) / 60)) . ' min' : 'prázdná');

// Soutěže týmů
foreach ($GLOBALS['TEAMS'] as $k => $tm) {
    foreach ($tm['csh'] as $c) {
        $ent = csh_competition($c + ['team_key' => $k]);
        $add($ent ? (empty($ent['competitionParts']) ? 'warn' : 'ok') : 'err', 'Soutěž „' . $c['slug'] . '“ – ' . $tm['title'], $ent ? ($ent['name'] . (empty($ent['competitionParts']) ? ' · zatím bez rozpisu (na webu se skryje)' : '')) : 'Soutěž na handball.cz neexistuje – opravte v Týmech.');
    }
}

// Zabezpečení a adresy (dotaz serveru sám na sebe)
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$add($https ? 'ok' : 'warn', 'HTTPS', $https ? 'zapnuto' : 'Web neběží přes HTTPS – na hostingu zapněte certifikát (Let\'s Encrypt).');
$self = ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$probeUrl = function (string $path) use ($self): int {
    $ch = curl_init($self . url($path));
    curl_setopt_array($ch, [CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_SSL_VERIFYPEER => false]);
    curl_exec($ch);
    $c = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $c;
};
$code = $probeUrl('storage/lions.sqlite');
$add(in_array($code, [403, 404], true) ? 'ok' : ($code === 0 ? 'warn' : 'err'), 'Databáze není veřejně stažitelná', $code === 0 ? 'Nelze ověřit (server se nedovolá sám na sebe).' : "HTTP $code" . (in_array($code, [403, 404], true) ? '' : ' – POZOR: nastavte ochranu složky storage/ (.htaccess / nginx)!'));
$first = array_key_first($GLOBALS['TEAMS']);
$code = $probeUrl($first . '/');
$add($code === 200 ? 'ok' : ($code === 0 ? 'warn' : 'err'), 'Hezké adresy (mod_rewrite)', $code === 0 ? 'Nelze ověřit.' : "/$first/ → HTTP $code" . ($code === 200 ? '' : ' – zapněte mod_rewrite / AllowOverride All'));

$free = @disk_free_space(ROOT);
if ($free !== false) $add($free > 200 * 1048576 ? 'ok' : 'warn', 'Volné místo na disku', human_size((int) $free));

$counts = ['ok' => 0, 'warn' => 0, 'err' => 0];
foreach ($checks as $c) $counts[$c[0]]++;
?>
<section class="card">
  <p><?= $counts['err'] ? '<b class="mail-bad">Něco je potřeba opravit (' . $counts['err'] . ').</b>' : '<b style="color:var(--ok)">Vše podstatné funguje.</b>' ?>
     <span class="muted"><?= $counts['ok'] ?> v pořádku · <?= $counts['warn'] ?> upozornění · <?= $counts['err'] ?> chyb</span></p>
  <div class="checks">
    <?php foreach ($checks as [$state, $checkTitle, $detail]): ?>
      <div class="check check--<?= $state ?>"><span class="check__icon"><?= $state === 'ok' ? '✓' : ($state === 'warn' ? '!' : '×') ?></span><div><b><?= e($checkTitle) ?></b><?php if ($detail): ?><small><?= e($detail) ?></small><?php endif; ?></div></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="card">
  <h2>Záloha</h2>
  <p class="muted">Databáze (formuláře, rezervace) se zálohuje automaticky každý den, uchovává se posledních 30 záloh ve <code>storage/backups/</code>.
     Kompletní zálohu obsahu (databáze + texty, týmy, nastavení z administrace) si můžete stáhnout:</p>
  <a class="btn btn--sm" href="<?= e(url('admin/api.php?action=backup')) ?>">Stáhnout zálohu (.zip)</a>
</section>
