<?php
declare(strict_types=1);

/**
 * Anonymní statistika návštěvnosti a prokliků (administrace → Návštěvnost).
 *
 * Bez cookies, bez IP adresy a bez jakéhokoli identifikátoru návštěvníka – ukládají se
 * jen denní součty („na stránce /muzi/ se dnes 12× kliklo na TABULKA v podmenu týmu“).
 * Proto k tomu není potřeba souhlas ani cookie lišta. Data se mažou po 13 měsících.
 *
 * Měří se: zobrazení stránky (+ odkud návštěvník přišel a jestli je na mobilu/počítači)
 * a kliknutí na odkazy, tlačítka a vložené widgety (Sportlyzer, mapa…).
 */

const STATS_DAILY_ROWS = 20000;   // pojistka proti zahlcení databáze nesmyslnými záznamy

function stats_clean(string $s, int $max): string
{
    $s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s) ?? '';
    return mb_substr(trim(preg_replace('/\s+/u', ' ', $s) ?? ''), 0, $max);
}

function stats_record(string $kind, string $page, string $target = '', string $label = '', string $section = ''): void
{
    if (!in_array($kind, ['view', 'click'], true)) {
        return;
    }
    $row = [date('Y-m-d'), $kind, stats_clean($page, 150) ?: '/', stats_clean($target, 200), stats_clean($label, 80), stats_clean($section, 60)];
    $pdo = db();
    $st = $pdo->prepare('UPDATE stats SET n = n + 1 WHERE day = ? AND kind = ? AND page = ? AND target = ? AND label = ? AND section = ?');
    $st->execute($row);
    if ($st->rowCount() > 0) {
        return;
    }
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM stats WHERE day = ?');
    $cnt->execute([$row[0]]);
    $full = (int) $cnt->fetchColumn() >= STATS_DAILY_ROWS;
    $cnt->closeCursor();
    if ($full) {
        return;
    }
    $pdo->prepare('INSERT INTO stats (day, kind, page, target, label, section, n) VALUES (?,?,?,?,?,?,1)
                   ON CONFLICT (day, kind, page, target, label, section) DO UPDATE SET n = n + 1')->execute($row);
}

/* ---------------- dotazy pro administraci ---------------- */

/** Podmínka období: posledních $days dní včetně dneška */
function stats_since(int $days): string
{
    return date('Y-m-d', strtotime('-' . max(0, $days - 1) . ' days'));
}

function stats_total(string $kind, int $days, ?string $page = null): int
{
    $sql = 'SELECT COALESCE(SUM(n), 0) FROM stats WHERE kind = ? AND day >= ?' . ($page !== null ? ' AND page = ?' : '');
    $st = db()->prepare($sql);
    $st->execute(array_merge([$kind, stats_since($days)], $page !== null ? [$page] : []));
    return (int) $st->fetchColumn();
}

/** Součty po dnech (chybějící dny = 0) → ['2026-10-01' => ['view' => 12, 'click' => 30], …] */
function stats_daily(int $days, ?string $page = null): array
{
    $out = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $out[date('Y-m-d', strtotime("-$i days"))] = ['view' => 0, 'click' => 0];
    }
    $sql = 'SELECT day, kind, SUM(n) AS n FROM stats WHERE day >= ?' . ($page !== null ? ' AND page = ?' : '') . ' GROUP BY day, kind';
    $st = db()->prepare($sql);
    $st->execute(array_merge([stats_since($days)], $page !== null ? [$page] : []));
    foreach ($st as $r) {
        if (isset($out[$r['day']])) $out[$r['day']][$r['kind']] = (int) $r['n'];
    }
    return $out;
}

/** Žebříček: $by = sloupce, podle kterých se seskupuje */
function stats_top(string $kind, array $by, int $days, int $limit = 20, array $where = []): array
{
    $cols = implode(', ', array_intersect($by, ['page', 'target', 'label', 'section']));
    $sql = "SELECT $cols, SUM(n) AS n FROM stats WHERE kind = ? AND day >= ?";
    $args = [$kind, stats_since($days)];
    foreach ($where as $col => $val) {
        if (!in_array($col, ['page', 'target', 'label', 'section'], true)) continue;
        if ($val === '!empty') {
            $sql .= " AND $col <> ''";
        } elseif (is_string($val) && str_ends_with($val, '%')) {
            $sql .= " AND $col LIKE ?";
            $args[] = $val;
        } else {
            $sql .= " AND $col = ?";
            $args[] = $val;
        }
    }
    $st = db()->prepare("$sql GROUP BY $cols ORDER BY n DESC LIMIT " . max(1, $limit));
    $st->execute($args);
    return $st->fetchAll();
}
