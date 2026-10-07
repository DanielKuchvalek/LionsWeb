<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require ROOT . '/lib/admin.php';

admin_session_start();
header('Cache-Control: no-store');

if (!admin_user()) {
    http_response_code(401);
    exit;
}

$action = (string) ($_GET['action'] ?? '');

/** Ochrana Excelu: hodnoty začínající =,+,-,@ by se jinak spustily jako vzorec */
function csv_safe(array $row): array
{
    return array_map(fn($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : $v, $row);
}

switch ($action) {
    case 'media':
        header('Content-Type: application/json; charset=utf-8');
        $list = media_list((string) ($_GET['type'] ?? ''), trim((string) ($_GET['q'] ?? '')));
        echo json_encode(array_slice($list, 0, 400), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'upload':
        header('Content-Type: application/json; charset=utf-8');
        csrf_check();
        echo json_encode(media_upload($_FILES['file'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'csv':
        $id = preg_replace('/[^a-z0-9\-]/', '', (string) ($_GET['f'] ?? ''));
        $st = db()->prepare('SELECT * FROM submissions WHERE form = ? ORDER BY id');
        $st->execute([$id]);
        $rows = $st->fetchAll();
        $head = [];
        foreach ($rows as $r) foreach (array_keys(json_decode($r['data'], true) ?: []) as $k) $head[$k] = true;
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="lions-' . $id . '-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_merge(['Datum'], array_keys($head), ['E-mail klubu']), ';');
        foreach ($rows as $r) {
            $d = json_decode($r['data'], true) ?: [];
            $line = [date('j. n. Y H:i', strtotime($r['created']))];
            foreach (array_keys($head) as $k) $line[] = $d[$k] ?? '';
            $line[] = $r['mail_status'] === 'sent' ? 'odeslán' : 'NEODESLÁN';
            fputcsv($out, csv_safe($line), ';');
        }
        fclose($out);
        break;

    case 'gym-csv':
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="lions-minigym-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Datum', 'Od', 'Do', 'Jméno', 'E-mail', 'Telefon', 'Poznámka', 'Vytvořeno', 'Zdroj'], ';');
        foreach (minigym_load() as $b) {
            fputcsv($out, csv_safe([$b['date'], $b['time'], $b['end'], $b['name'], $b['email'], $b['phone'], $b['note'] ?? '', $b['created'] ?? '', $b['source'] ?? 'web']), ';');
        }
        fclose($out);
        break;

    case 'gdpr-export':
        $q = trim((string) ($_GET['q'] ?? ''));
        if (mb_strlen($q) < 3) { http_response_code(400); exit; }
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $st = db()->prepare("SELECT form, created, data FROM submissions WHERE data LIKE ? ESCAPE '\\' ORDER BY id");
        $st->execute([$like]);
        $subs = array_map(fn($r) => ['formulář' => $r['form'], 'odesláno' => $r['created'], 'údaje' => json_decode($r['data'], true)], $st->fetchAll());
        $st = db()->prepare("SELECT date, time, end, first, last, email, phone, created FROM bookings WHERE first || ' ' || last LIKE ? ESCAPE '\\' OR email LIKE ? ESCAPE '\\' OR phone LIKE ? ESCAPE '\\' ORDER BY date");
        $st->execute([$like, $like, $like]);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="osobni-udaje-' . date('Y-m-d') . '.json"');
        echo json_encode([
            'správce' => block('kontakt.org_name') . ', ' . block('kontakt.org_address') . ', IČO ' . block('kontakt.org_ico'),
            'vygenerováno' => date('c'),
            'formuláře' => $subs,
            'rezervace_minigym' => $st->fetchAll(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'backup':
        $tmpDb = sys_get_temp_dir() . '/lions-' . bin2hex(random_bytes(4)) . '.sqlite';
        db()->exec('VACUUM INTO ' . db()->quote($tmpDb));
        $zipFile = sys_get_temp_dir() . '/lions-backup-' . bin2hex(random_bytes(4)) . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE);
        $zip->addFile($tmpDb, 'lions.sqlite');
        register_shutdown_function(function () use ($tmpDb, $zipFile) { @unlink($tmpDb); @unlink($zipFile); });
        foreach (glob(STORAGE . '/content/*.json') ?: [] as $f) {
            if (basename($f) === 'site.json') {        // heslo k e-mailu do zálohy nepatří
                $site = json_read($f);
                if (isset($site['smtp']['pass'])) $site['smtp']['pass'] = '';
                foreach ($site['facebook_pages'] ?? [] as $fk => $_) unset($site['facebook_pages'][$fk]['token']);
                $zip->addFromString('content/site.json', json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $zip->addFile($f, 'content/' . basename($f));
            }
        }
        foreach (glob(STORAGE . '/admin/*.json') ?: [] as $f) $zip->addFile($f, 'admin/' . basename($f));
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="lions-zaloha-' . date('Y-m-d') . '.zip"');
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);
        @unlink($zipFile);
        @unlink($tmpDb);
        break;

    default:
        http_response_code(400);
}
