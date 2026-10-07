<?php
declare(strict_types=1);

/**
 * LIONS miniGYM booking – otevřeno denně 10:00–22:00, rezervace trvá 60 minut
 * a začít může každých 30 minut (10:00, 10:30, 11:00 …). Rezervace se tak mohou
 * časově překrývat, proto se obsazenost počítá podle PŘEKRYVU časů.
 * Otevírací dobu, délku, krok a kapacitu lze změnit v administraci (Stránky → LIONS miniGYM).
 *
 * Rezervace jsou v SQLite (tabulka bookings). Kontrola překryvu a zápis probíhají
 * v jedné zamčené transakci (BEGIN IMMEDIATE), takže dva lidé nemohou získat
 * překrývající se termín, ani když kliknou ve stejnou chvíli.
 */

function minigym_open(): int     { return max(0, min(23, (int) block('minigym.open'))); }
function minigym_close(): int    { return max(minigym_open() + 1, min(24, (int) block('minigym.close'))); }
function minigym_capacity(): int { return max(1, (int) block('minigym.capacity')); }
function minigym_service(): string { return block('minigym.service'); }
function minigym_duration(): int { return max(15, min(240, (int) block('minigym.duration') ?: 60)); }
function minigym_step(): int     { return max(15, min(120, (int) block('minigym.step') ?: 30)); }

/** '11:30' → 690 minut od půlnoci */
function hm_to_min(string $hm): int
{
    [$h, $m] = array_map('intval', explode(':', $hm) + [1 => 0]);
    return $h * 60 + $m;
}

function min_to_hm(int $m): string
{
    return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
}

/** Překrývají se intervaly [a1, a2) a [b1, b2)? (v minutách) */
function minigym_overlaps(int $a1, int $a2, int $b1, int $b2): bool
{
    return $a1 < $b2 && $b1 < $a2;
}

/** Rezervace daného dne (bez upravované) – pro výpočet překryvů */
function minigym_day_bookings(string $date, ?string $ignoreId = null): array
{
    $st = db()->prepare('SELECT id, time, end, seat, first, last FROM bookings WHERE date = ? AND id <> ?');
    $st->execute([$date, (string) $ignoreId]);
    return $st->fetchAll();
}

function minigym_row(array $r): array
{
    $r['name'] = trim($r['first'] . ' ' . $r['last']);
    return $r;
}

/** Rezervace (volitelně jen od/do data), seřazené podle termínu */
function minigym_load(?string $from = null, ?string $to = null): array
{
    $sql = 'SELECT * FROM bookings WHERE 1=1';
    $args = [];
    if ($from !== null) { $sql .= ' AND date >= ?'; $args[] = $from; }
    if ($to !== null)   { $sql .= ' AND date <= ?'; $args[] = $to; }
    $st = db()->prepare($sql . ' ORDER BY date, time, seat');
    $st->execute($args);
    return array_map('minigym_row', $st->fetchAll());
}

function minigym_find(string $id): ?array
{
    $st = db()->prepare('SELECT * FROM bookings WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ? minigym_row($r) : null;
}

/** Všechny možné začátky v jednom dni: ['10:00' => '11:00', '10:30' => '11:30', ...] */
function minigym_slot_times(): array
{
    $out = [];
    $dur = minigym_duration();
    for ($m = minigym_open() * 60; $m + $dur <= minigym_close() * 60; $m += minigym_step()) {
        $out[min_to_hm($m)] = min_to_hm($m + $dur);
    }
    return $out;
}

/** Obsazenost začátků daného dne ($ignoreId = rezervace, kterou právě upravujeme) */
function minigym_slots(string $date, ?string $ignoreId = null): array
{
    $day = minigym_day_bookings($date, $ignoreId);
    $now = new DateTimeImmutable('now');
    $out = [];
    foreach (minigym_slot_times() as $time => $end) {
        $s1 = hm_to_min($time);
        $s2 = hm_to_min($end);
        $n = count(array_filter($day, fn($b) => minigym_overlaps($s1, $s2, hm_to_min($b['time']), hm_to_min($b['end']))));
        $start = new DateTimeImmutable("$date $time");
        $out[] = [
            'time'  => $time,
            'end'   => $end,
            'taken' => $n,
            'free'  => $n < minigym_capacity() && $start > $now,
            'past'  => $start <= $now,
        ];
    }
    return $out;
}

function minigym_valid_date(string $date, bool $allowPast = false): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return false;
    }
    if ($d > new DateTimeImmutable('+1 year')) {
        return false;
    }
    return $allowPast || $d >= new DateTimeImmutable('today');
}

/** Validace údajů rezervace (společná pro web i administraci) */
function minigym_validate(array $in, bool $admin): array
{
    $t = fn($k, $max = 120) => mb_substr(trim((string) ($in[$k] ?? '')), 0, $max);
    $data = [
        'date'  => $t('date', 10),
        'time'  => $t('time', 5),
        'first' => $t('first'),
        'last'  => $t('last'),
        'email' => mb_strtolower($t('email', 190)),
        'phone' => $t('phone', 30),
        'note'  => $t('note', 1000),
    ];
    $errors = [];
    if (!minigym_valid_date($data['date'], $admin)) $errors['date'] = 'Vyberte platné datum.';
    if (!isset(minigym_slot_times()[$data['time']])) $errors['time'] = 'Vyberte čas.';
    if ($data['first'] === '') $errors['first'] = 'Vyplňte křestní jméno.';
    if (!$admin && $data['last'] === '') $errors['last'] = 'Vyplňte příjmení.';
    if (($data['email'] !== '' || !$admin) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Zadejte platný e-mail.';
    $phone = norm_phone($data['phone']);
    if ($phone === null || ($phone === '' && !$admin)) {
        $errors['phone'] = 'Zadejte 9místné telefonní číslo (za +420), např. 777 123 456.';
    } else {
        $data['phone'] = $phone;
    }
    return [$data, $errors];
}

/** Rezervace z veřejného webu */
function minigym_book(array $post): array
{
    if ($err = form_guard('minigym', $post)) {
        return $err;
    }
    [$data, $errors] = minigym_validate($post, false);
    if ($g = gdpr_error($post)) {
        $errors['_gdpr'] = $g;
    }
    if ($errors) {
        return ['ok' => false, 'message' => 'Zkontrolujte prosím zvýrazněná pole.', 'errors' => $errors];
    }
    if ($err = form_rate_error('minigym', 6, 600)) {
        return $err;
    }
    // max. 3 nadcházející rezervace na jeden e-mail nebo telefon (nejde zablokovat celý kalendář)
    $st = db()->prepare("SELECT COUNT(*) FROM bookings WHERE date >= ? AND (email = ? OR REPLACE(phone, ' ', '') = ?)");
    $st->execute([date('Y-m-d'), $data['email'], str_replace(' ', '', $data['phone'])]);
    $mine = (int) $st->fetchColumn();
    $st->closeCursor();   // uvolnit čtení, jinak by následný zápis v souběhu hned selhal na „database is locked“
    if ($mine >= 3) {
        return ['ok' => false, 'message' => 'Na tento e-mail nebo telefon jsou už 3 nadcházející rezervace. Další si můžete udělat, až některá proběhne nebo ji zrušíte.'];
    }
    $result = minigym_save($data, null, false);
    if ($result['ok']) {
        $b = $result['booking'];
        $when = minigym_when($b);
        send_mail_many(form_recipients('minigym'), 'LIONS miniGYM rezervace ' . $when,
            "Služba:\n" . minigym_service() . "\n\nTermín:\n$when\n\nJméno:\n{$b['name']}\n\nE-mail:\n{$b['email']}\n\nTelefon:\n{$b['phone']}\n\n--\nVšechny rezervace najdete v administraci webu.",
            $b['email']);
        $conf = minigym_send_confirmation($b);
        $result = ['ok' => true, 'message' => "Rezervace potvrzena: $when. " . ($conf['ok'] ? 'Potvrzení jsme vám poslali e-mailem.' : 'Děkujeme!')];
    }
    return $result;
}

/**
 * Uloží novou ($id = null) nebo upravenou rezervaci.
 * $admin = true povolí minulé termíny; $force = true povolí i obsazený slot.
 */
function minigym_save(array $data, ?string $id, bool $admin, bool $force = false): array
{
    $pdo = db();
    $end = minigym_slot_times()[$data['time']] ?? null;
    if ($end === null) {
        return ['ok' => false, 'message' => 'Neplatný čas.', 'errors' => ['time' => 'Vyberte čas.']];
    }
    if (!$admin && new DateTimeImmutable($data['date'] . ' ' . $data['time']) <= new DateTimeImmutable('now')) {
        return ['ok' => false, 'message' => 'Tento termín už proběhl.', 'errors' => ['time' => 'Termín proběhl']];
    }

    for ($attempt = 0; $attempt < 3; $attempt++) {
        try {
            $pdo->exec('BEGIN IMMEDIATE'); // zamkne zápis – souběžné rezervace se seřadí za sebou
            // místa obsazená rezervacemi, které se s tímto časem překrývají
            $s1 = hm_to_min($data['time']);
            $s2 = hm_to_min($end);
            $st = $pdo->prepare('SELECT time, end, seat FROM bookings WHERE date = ? AND id <> ?');
            $st->execute([$data['date'], (string) $id]);
            $used = [];
            foreach ($st->fetchAll() as $o) {
                if (minigym_overlaps($s1, $s2, hm_to_min($o['time']), hm_to_min($o['end']))) {
                    $used[] = (int) $o['seat'];
                }
            }

            // stejné místo, pokud upravujeme rezervaci a nekoliduje
            $seat = null;
            if ($id !== null) {
                $cur = $pdo->prepare('SELECT seat FROM bookings WHERE id = ?');
                $cur->execute([$id]);
                $curSeat = $cur->fetchColumn();
                if ($curSeat === false) {
                    $pdo->exec('ROLLBACK');
                    return ['ok' => false, 'message' => 'Rezervace nebyla nalezena.'];
                }
                if ((int) $curSeat <= minigym_capacity() && !in_array((int) $curSeat, $used, true)) {
                    $seat = (int) $curSeat;
                }
            }
            if ($seat === null) {
                for ($x = 1; $x <= minigym_capacity(); $x++) {
                    if (!in_array($x, $used, true)) { $seat = $x; break; }
                }
            }
            if ($seat === null) {
                if (!$force) {
                    $pdo->exec('ROLLBACK');
                    return ['ok' => false, 'message' => 'Tento čas se překrývá s jinou rezervací. Vyberte prosím jiný.', 'errors' => ['time' => 'Obsazeno']];
                }
                // vědomé přebookování z administrace – místo, které v tomto začátku ještě není
                $seat = minigym_capacity();
                do { $seat++; $chk = $pdo->prepare('SELECT 1 FROM bookings WHERE date = ? AND time = ? AND seat = ? AND id <> ?'); $chk->execute([$data['date'], $data['time'], $seat, (string) $id]); } while ($chk->fetchColumn());
            }

            $now = date('c');
            if ($id === null) {
                $newId = bin2hex(random_bytes(6));
                $pdo->prepare('INSERT INTO bookings (id,date,time,end,seat,first,last,email,phone,note,source,ip,created) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$newId, $data['date'], $data['time'], $end, $seat, $data['first'], $data['last'], $data['email'], $data['phone'], $data['note'], $admin ? 'admin' : 'web', client_ip(), $now]);
                $id = $newId;
                $msg = 'Rezervace byla přidána.';
            } else {
                $pdo->prepare('UPDATE bookings SET date=?, time=?, end=?, seat=?, first=?, last=?, email=?, phone=?, note=?, updated=? WHERE id=?')
                    ->execute([$data['date'], $data['time'], $end, $seat, $data['first'], $data['last'], $data['email'], $data['phone'], $data['note'], $now, $id]);
                $msg = 'Rezervace byla upravena.';
            }
            $pdo->exec('COMMIT');
            return ['ok' => true, 'booking' => minigym_find($id), 'message' => $msg];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->exec('ROLLBACK');
            // 23000 = porušení UNIQUE (někdo byl o zlomek sekundy rychlejší) → zkusit znovu
            if ($e->getCode() !== '23000' || $attempt === 2) {
                error_log('LIONS booking failed: ' . $e->getMessage());
                return ['ok' => false, 'message' => $e->getCode() === '23000' ? 'Tento termín už je obsazený. Vyberte prosím jiný.' : 'Rezervaci se nepodařilo uložit, zkuste to prosím znovu.', 'errors' => ['time' => 'Obsazeno']];
            }
        }
    }
    return ['ok' => false, 'message' => 'Rezervaci se nepodařilo uložit.'];
}

function minigym_delete(string $id): array
{
    $st = db()->prepare('DELETE FROM bookings WHERE id = ?');
    $st->execute([$id]);
    return $st->rowCount()
        ? ['ok' => true, 'message' => 'Rezervace byla smazána.']
        : ['ok' => false, 'message' => 'Rezervace nebyla nalezena.'];
}

function minigym_when(array $b): string
{
    return (new DateTimeImmutable($b['date']))->format('j. n. Y') . " {$b['time']}–{$b['end']}";
}

/* =====================================================================
 * Potvrzení rezervace e-mailem + zrušení odkazem
 * ===================================================================== */

/** Podpis odkazu na zrušení – bez něj nejde zrušit cizí rezervaci */
function minigym_cancel_token(array $b): string
{
    return substr(hash_hmac('sha256', 'cancel|' . $b['id'] . '|' . $b['email'] . '|' . $b['created'], app_secret()), 0, 32);
}

function minigym_cancel_url(array $b): string
{
    return abs_url(url('mini-gym/zrusit/')) . '?r=' . rawurlencode($b['id']) . '&t=' . minigym_cancel_token($b);
}

/** Najde rezervaci podle odkazu ze e-mailu (null = neplatný odkaz) */
function minigym_find_by_token(string $id, string $token): ?array
{
    $b = minigym_find($id);
    return $b && hash_equals(minigym_cancel_token($b), $token) ? $b : null;
}

function minigym_is_future(array $b): bool
{
    return new DateTimeImmutable($b['date'] . ' ' . $b['time']) > new DateTimeImmutable('now');
}

function minigym_day_label(array $b): string
{
    $d = new DateTimeImmutable($b['date']);
    return cz_date($d, 'day') . ' ' . cz_date($d, 'long');
}

/** Potvrzovací e-mail pro člověka, který rezervoval */
function minigym_send_confirmation(array $b): array
{
    if (!filter_var($b['email'], FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Rezervace nemá e-mail.'];
    }
    $day = minigym_day_label($b);
    $time = $b['time'] . '–' . $b['end'];
    $place = block('kontakt.address');
    $rules = abs_url(media(block('minigym.rules')));
    $cancel = minigym_cancel_url($b);

    $html = email_template(
        'Rezervace potvrzena',
        'Dobrý den, ' . e($b['first']) . ',<br>vaše rezervace <b>LIONS miniGYM</b> je potvrzená. Těšíme se na vás!',
        [
            ['Termín', e(ucfirst($day)) . '<br><span style="color:#be1e3e;font-size:18px">' . e($time) . '</span>'],
            ['Místo', e($place)],
            ['Jméno', e($b['name'])],
        ],
        ['Pravidla LIONS miniGYM', $rules, '#11244a'],
        '<b>Rezervací souhlasíte s pravidly LIONS miniGYM.</b> Prosíme o čistou sportovní obuv, ručník a úklid náčiní po cvičení.<br><br>'
        . 'Nemůžete přijít? Uvolněte prosím termín pro ostatní: <a href="' . e($cancel) . '" style="color:#be1e3e;font-weight:700">zrušit rezervaci</a>.'
    );
    $text = "Dobrý den, {$b['first']},\n\nvaše rezervace LIONS miniGYM je potvrzená.\n\n"
        . "Termín: " . ucfirst($day) . ", $time\nMísto: $place\nJméno: {$b['name']}\n\n"
        . "Rezervací souhlasíte s pravidly LIONS miniGYM: $rules\n\n"
        . "Nemůžete přijít? Zrušte prosím rezervaci zde:\n$cancel\n\n"
        . "LIONS Handball\n" . site('email');

    return send_mail($b['email'], "Potvrzení rezervace LIONS miniGYM – " . (new DateTimeImmutable($b['date']))->format('j. n.') . " $time",
        $text, (string) site('email'), $html);
}

/** Zrušení z odkazu v e-mailu */
function minigym_cancel_by_token(string $id, string $token): array
{
    $b = minigym_find_by_token($id, $token);
    if (!$b) {
        return ['ok' => false, 'message' => 'Rezervace nebyla nalezena – možná už byla zrušena.'];
    }
    if (!minigym_is_future($b)) {
        return ['ok' => false, 'message' => 'Tento termín už proběhl, zrušit ho nelze.'];
    }
    $r = minigym_delete($b['id']);
    if ($r['ok']) {
        send_mail_many(form_recipients('minigym'), 'LIONS miniGYM – zrušená rezervace ' . minigym_when($b),
            "Rezervace byla zrušena odkazem z potvrzovacího e-mailu.\n\nTermín:\n" . minigym_when($b) . "\n\nJméno:\n{$b['name']}\n\nE-mail:\n{$b['email']}\n\nTelefon:\n{$b['phone']}\n\n--\nTermín je znovu volný.");
        $r['message'] = 'Rezervace ' . minigym_when($b) . ' byla zrušena. Děkujeme, že jste termín uvolnili.';
    }
    return $r;
}
