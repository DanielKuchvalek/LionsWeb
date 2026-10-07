<?php
declare(strict_types=1);

/**
 * Databáze pro data od návštěvníků (přihlášky, rezervace miniGYM, ochrana proti spamu).
 *
 * SQLite = jeden soubor storage/lions.sqlite, žádný databázový server není potřeba.
 * Režim WAL umožňuje současné čtení a zápis, transakce zaručují, že se nic neztratí
 * ani při mnoha současných odesláních a že jeden termín nejde zarezervovat dvakrát
 * (hlídá to přímo databáze přes UNIQUE index).
 */

const DB_VERSION = 2;

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $file = STORAGE . '/lions.sqlite';
    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 10,
    ]);
    $pdo->exec('PRAGMA busy_timeout = 10000');   // při souběhu počkat, ne selhat
    if (strtolower((string) $pdo->query('PRAGMA journal_mode')->fetchColumn()) !== 'wal') {
        $pdo->exec('PRAGMA journal_mode = WAL'); // trvalé nastavení, stačí jednou
    }
    $pdo->exec('PRAGMA synchronous = NORMAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
    db_migrate($pdo);
    return $pdo;
}

function db_migrate(PDO $pdo): void
{
    $v = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    if ($v >= DB_VERSION) {
        return;
    }
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $v = (int) $pdo->query('PRAGMA user_version')->fetchColumn(); // mohl migrovat jiný proces
        if ($v < 1) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS submissions (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    form        TEXT NOT NULL,
                    created     TEXT NOT NULL,
                    data        TEXT NOT NULL,            -- JSON {popisek pole: hodnota}
                    ip          TEXT,
                    mail_status TEXT NOT NULL DEFAULT 'pending',  -- sent | failed | pending
                    mail_error  TEXT
                );
                CREATE INDEX IF NOT EXISTS submissions_form ON submissions(form, created);

                CREATE TABLE IF NOT EXISTS bookings (
                    id      TEXT PRIMARY KEY,
                    date    TEXT NOT NULL,
                    time    TEXT NOT NULL,
                    end     TEXT NOT NULL,
                    seat    INTEGER NOT NULL DEFAULT 1,  -- pořadí v rámci kapacity slotu
                    first   TEXT NOT NULL,
                    last    TEXT NOT NULL DEFAULT '',
                    email   TEXT NOT NULL DEFAULT '',
                    phone   TEXT NOT NULL DEFAULT '',
                    note    TEXT NOT NULL DEFAULT '',
                    source  TEXT NOT NULL DEFAULT 'web',
                    ip      TEXT,
                    created TEXT NOT NULL,
                    updated TEXT,
                    UNIQUE (date, time, seat)
                );
                CREATE INDEX IF NOT EXISTS bookings_date ON bookings(date, time);

                CREATE TABLE IF NOT EXISTS hits (        -- omezení počtu odeslání z jedné IP
                    ip     TEXT NOT NULL,
                    action TEXT NOT NULL,
                    t      INTEGER NOT NULL
                );
                CREATE INDEX IF NOT EXISTS hits_ip ON hits(ip, action, t);
            ");
            db_import_legacy($pdo);
        }
        if ($v < 2) {
            // anonymní statistika návštěvnosti: jen denní součty, žádná IP ani identifikátor návštěvníka
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS stats (
                    day     TEXT NOT NULL,              -- 2026-10-02
                    kind    TEXT NOT NULL,              -- view | click
                    page    TEXT NOT NULL,              -- stránka, např. /muzi/
                    target  TEXT NOT NULL DEFAULT '',   -- view: odkud přišel (doména) · click: kam vede odkaz
                    label   TEXT NOT NULL DEFAULT '',   -- view: mobil/počítač · click: text odkazu / tlačítka
                    section TEXT NOT NULL DEFAULT '',   -- click: část stránky (menu, patička, nadpis sekce…)
                    n       INTEGER NOT NULL DEFAULT 0,
                    PRIMARY KEY (day, kind, page, target, label, section)
                ) WITHOUT ROWID;
                CREATE INDEX IF NOT EXISTS stats_kind_day ON stats(kind, day);
            ");
        }
        $pdo->exec('PRAGMA user_version = ' . DB_VERSION);
        $pdo->exec('COMMIT');
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}

/** Převezme data ze starších souborů (JSON/CSV), pokud existují */
function db_import_legacy(PDO $pdo): void
{
    $jf = STORAGE . '/minigym/bookings.json';
    if (is_file($jf)) {
        $st = $pdo->prepare('INSERT OR IGNORE INTO bookings (id,date,time,end,seat,first,last,email,phone,note,source,created,updated)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $seats = [];
        foreach (json_decode((string) file_get_contents($jf), true) ?: [] as $b) {
            $k = $b['date'] . $b['time'];
            $seats[$k] = ($seats[$k] ?? 0) + 1;
            $st->execute([$b['id'], $b['date'], $b['time'], $b['end'], $seats[$k], $b['first'] ?? $b['name'], $b['last'] ?? '',
                $b['email'] ?? '', $b['phone'] ?? '', $b['note'] ?? '', $b['source'] ?? 'web', $b['created'] ?? date('c'), $b['updated'] ?? null]);
        }
        @rename($jf, $jf . '.imported');
    }
    foreach (glob(STORAGE . '/forms/*.csv') ?: [] as $csv) {
        $form = basename($csv, '.csv');
        $fh = fopen($csv, 'rb');
        $head = null;
        $st = $pdo->prepare("INSERT INTO submissions (form, created, data, mail_status) VALUES (?,?,?,'sent')");
        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            if ($head === null) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
                $head = $row;
                continue;
            }
            $data = [];
            foreach (array_slice($head, 1, null, true) as $i => $h) $data[$h] = $row[$i] ?? '';
            $st->execute([$form, date('c', strtotime($row[0]) ?: time()), json_encode($data, JSON_UNESCAPED_UNICODE)]);
        }
        fclose($fh);
        @rename($csv, $csv . '.imported');
    }
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');
}

/**
 * Jednoduché omezení: max $limit akcí za $window sekund z jedné IP.
 * Vrací false, když je limit překročen.
 */
function rate_limit(string $action, int $limit, int $window): bool
{
    $pdo = db();
    $now = time();
    $ip = client_ip();
    for ($attempt = 0; ; $attempt++) {
        try {
            $pdo->exec('BEGIN IMMEDIATE'); // počítání a zápis atomicky, souběžné požadavky se seřadí
            if (random_int(1, 50) === 1) {
                $pdo->prepare('DELETE FROM hits WHERE t < ?')->execute([$now - 86400]);
            }
            $st = $pdo->prepare('SELECT COUNT(*) FROM hits WHERE ip = ? AND action = ? AND t > ?');
            $st->execute([$ip, $action, $now - $window]);
            $count = (int) $st->fetchColumn();
            $st->closeCursor();
            if ($count >= $limit) {
                $pdo->exec('COMMIT');
                return false;
            }
            $pdo->prepare('INSERT INTO hits (ip, action, t) VALUES (?,?,?)')->execute([$ip, $action, $now]);
            $pdo->exec('COMMIT');
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->exec('ROLLBACK');
            if ($attempt >= 3) throw $e;
            usleep(100000 * ($attempt + 1));
        }
    }
}

function rate_count(string $action, int $window): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM hits WHERE ip = ? AND action = ? AND t > ?');
    $st->execute([client_ip(), $action, time() - $window]);
    $n = (int) $st->fetchColumn();
    $st->closeCursor();
    return $n;
}

/* =====================================================================
 * Zálohy – každý den automaticky kopie databáze (uchová se 30 posledních)
 * ===================================================================== */

function db_backup_dir(): string
{
    $d = STORAGE . '/backups';
    if (!is_dir($d)) {
        @mkdir($d, 0775, true);
    }
    return $d;
}

/** Vytvoří dnešní zálohu, pokud ještě neexistuje (volá se automaticky) */
function db_daily_backup(): void
{
    $file = db_backup_dir() . '/lions-' . date('Y-m-d') . '.sqlite';
    if (is_file($file)) {
        return;
    }
    $lock = @fopen(db_backup_dir() . '/.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        return; // zálohu právě dělá jiný požadavek
    }
    try {
        if (!is_file($file)) {
            db()->exec('VACUUM INTO ' . db()->quote($file));
            $all = glob(db_backup_dir() . '/lions-*.sqlite') ?: [];
            sort($all);
            foreach (array_slice($all, 0, max(0, count($all) - 30)) as $old) {
                @unlink($old);
            }
        }
    } catch (Throwable $e) {
        error_log('LIONS backup failed: ' . $e->getMessage());
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/* =====================================================================
 * GDPR – automatické mazání po uplynutí lhůt (Nastavení → Uchování údajů)
 * ===================================================================== */

/** @return array{forms:int, bookings:int, ips:int} počty smazaných / anonymizovaných záznamů */
function gdpr_cleanup(): array
{
    $ret = site('retention') ?? [];
    $formsMonths = max(1, (int) ($ret['forms'] ?? 24));
    $gymMonths = max(1, (int) ($ret['minigym'] ?? 12));
    $pdo = db();
    $r = ['forms' => 0, 'bookings' => 0, 'ips' => 0];
    $st = $pdo->prepare('DELETE FROM submissions WHERE created < ?');
    $st->execute([date('c', strtotime("-$formsMonths months"))]);
    $r['forms'] = $st->rowCount();
    $st = $pdo->prepare('DELETE FROM bookings WHERE date < ?');
    $st->execute([date('Y-m-d', strtotime("-$gymMonths months"))]);
    $r['bookings'] = $st->rowCount();
    $st = $pdo->prepare('UPDATE submissions SET ip = NULL WHERE ip IS NOT NULL AND created < ?');
    $st->execute([date('c', strtotime('-30 days'))]);
    $r['ips'] = $st->rowCount();
    $st = $pdo->prepare('UPDATE bookings SET ip = NULL WHERE ip IS NOT NULL AND created < ?');
    $st->execute([date('c', strtotime('-30 days'))]);
    $r['ips'] += $st->rowCount();
    $pdo->prepare('DELETE FROM hits WHERE t < ?')->execute([time() - 86400]);
    $pdo->prepare('DELETE FROM stats WHERE day < ?')->execute([date('Y-m-d', strtotime('-13 months'))]);
    return $r;
}

/** Denní údržba: mazání po lhůtách + záloha (volá se jednou denně po odeslání stránky) */
function db_daily_maintenance(): void
{
    $marker = STORAGE . '/backups/.maintenance-' . date('Y-m-d');
    if (is_file($marker)) {
        return;
    }
    @touch($marker);
    foreach (glob(STORAGE . '/backups/.maintenance-*') ?: [] as $old) {
        if ($old !== $marker) @unlink($old);
    }
    try {
        $r = gdpr_cleanup();
        if (array_sum($r)) {
            error_log('LIONS: denní údržba – smazáno formulářů ' . $r['forms'] . ', rezervací ' . $r['bookings'] . ', anonymizováno IP ' . $r['ips']);
        }
    } catch (Throwable $e) {
        error_log('LIONS cleanup failed: ' . $e->getMessage());
    }
    db_daily_backup();
}
