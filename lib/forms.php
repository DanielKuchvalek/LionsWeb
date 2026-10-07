<?php
declare(strict_types=1);

function forms_config(): array
{
    static $forms = null;
    return $forms ??= require ROOT . '/config/forms.php';
}

function app_secret(): string
{
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }
    $file = STORAGE . '/secret.key';
    $val = is_file($file) ? trim((string) @file_get_contents($file)) : '';
    if (strlen($val) < 32) {
        $val = bin2hex(random_bytes(32));
        $tmp = $file . '.' . getmypid();
        if (@file_put_contents($tmp, $val) !== false) {
            @rename($tmp, $file);
        }
        // při souběhu mohl klíč zapsat jiný požadavek – použijeme ten uložený
        $saved = is_file($file) ? trim((string) @file_get_contents($file)) : '';
        $val = strlen($saved) >= 32 ? $saved : $val;
    }
    return $secret = $val;
}

/** Název skrytého pole proti robotům (prohlížeče ho automaticky nevyplňují) */
const FORM_HONEYPOT = 'lf_hp_nevyplnovat';
const FORM_TOKEN_MAX_AGE = 14 * 86400;

const FORM_MIN_SECONDS = 3;   // rychlejší odeslání po načtení stránky = robot

/** Token proti spamu: podepsaný čas vykreslení formuláře ($backdate: obnovený token smí jít odeslat hned) */
function form_token(string $formId, int $backdate = 0): string
{
    $t = (string) (time() - $backdate);
    return $t . '.' . hash_hmac('sha256', $formId . '|' . $t, app_secret());
}

/**
 * Kontrola „vyplnil člověk v prohlížeči“: hodnotu do skrytého pole _hc doplní až JavaScript
 * po skutečném kliknutí / psaní ve formuláři. Roboti, kteří jen odešlou HTML formulář, ji nemají.
 */
function form_human_key(string $token): string
{
    return substr(hash_hmac('sha256', 'hc|' . $token, app_secret()), 0, 24);
}

/** Atributy pro <form>: klíč pro kontrolu člověka (JS ho po interakci zapíše obráceně do _hc) */
function form_human_attr(string $token): string
{
    return ' data-hc="' . e(form_human_key($token)) . '"';
}

/** Nesmyslný text od robota: dlouhé „slovo“ s mnoha přechody malé→VELKÉ písmeno (RGpNLejLSpdEbaVCV) */
function form_is_gibberish(string $text): bool
{
    foreach (preg_split('/[^\p{L}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $w) {
        if (mb_strlen($w) >= 10 && preg_match_all('/\p{Ll}\p{Lu}/u', $w) >= 3) {
            return true;
        }
    }
    return false;
}

/** Typická robotí adresa: Gmail s mnoha tečkami (wi.ll.i.am.t.ho.m.a@gmail.com) */
function form_is_spam_email(string $email): bool
{
    $email = mb_strtolower(trim($email));
    if (!preg_match('/^([^@]+)@(gmail|googlemail)\.com$/', $email, $m)) {
        return false;
    }
    return substr_count($m[1], '.') >= 4;
}

/** Projde všechny textové hodnoty odeslání (i vnořené – jméno/příjmení) */
function form_post_strings(array $post): array
{
    $out = [];
    array_walk_recursive($post, function ($v, $k) use (&$out) {
        if (is_string($v) && !str_starts_with((string) $k, '_')) {
            $out[(string) $k] = $v;
        }
    });
    return $out;
}

/** Vrací null (v pořádku) nebo 'token' (neplatný / vypršelý) */
function form_token_problem(string $formId, string $token): ?string
{
    [$t, $sig] = array_pad(explode('.', $token, 2), 2, '');
    if (!ctype_digit($t) || !hash_equals(hash_hmac('sha256', $formId . '|' . $t, app_secret()), $sig)) {
        return 'token';
    }
    return time() - (int) $t > FORM_TOKEN_MAX_AGE ? 'token' : null;
}

function form_token_valid(string $formId, string $token): bool
{
    return form_token_problem($formId, $token) === null;
}

/** Povinné potvrzení o zpracování osobních údajů (s odkazem na zásady) */
function gdpr_checkbox(bool $guardian = false): string
{
    $link = '<a href="' . e(url('ochrana-osobnich-udaju/')) . '" target="_blank">zásadami ochrany osobních údajů</a>';
    $text = $guardian
        ? 'Jako zákonný zástupce účastníka potvrzuji, že jsem se seznámil(a) se ' . $link . ' a že údaje odesílám k přihlášení dítěte.'
        : 'Potvrzuji, že jsem se seznámil(a) se ' . $link . ' a údaje odesílám za účelem vyřízení tohoto formuláře.';
    return '<label class="field gdpr-check"><input type="checkbox" name="_gdpr" value="1" required><span>' . $text . ' <span class="req">*</span></span></label>';
}

/** Kontrola potvrzení GDPR – vrací chybu pro pole nebo null */
function gdpr_error(array $post): ?string
{
    return empty($post['_gdpr']) ? 'Pro odeslání je potřeba potvrdit seznámení se zásadami ochrany osobních údajů.' : null;
}

/** Popisky citlivých polí formuláře (neposílají se e-mailem, v administraci skryté) */
function form_sensitive_labels(string $formId): array
{
    $out = [];
    foreach (forms_config()[$formId]['fields'] ?? [] as $f) {
        if (!empty($f['sensitive'])) $out[] = $f['label'];
    }
    return $out;
}

/** Skryté pole proti robotům – display:none, takže ho nevyplní automatické doplňování prohlížeče */
function honeypot_field(): string
{
    return '<div hidden style="display:none"><label>Nevyplňujte <input type="text" name="' . FORM_HONEYPOT . '" value="" tabindex="-1" autocomplete="off"></label></div>';
}

/**
 * Společná kontrola odeslání (robot / token / limit).
 * @return array|null chybová odpověď nebo null, když je vše v pořádku
 */
function form_guard(string $formId, array $post): ?array
{
    $spam = ['ok' => false, 'code' => 'spam', 'message' => 'Odeslání bylo zablokováno jako spam. Pokud jste člověk, napište nám prosím přímo na ' . site('email') . '.'];
    if (trim((string) ($post[FORM_HONEYPOT] ?? '')) !== '') {
        return form_blocked('honeypot', $formId, $spam);
    }
    $token = (string) ($post['_token'] ?? '');
    $problem = form_token_problem($formId, $token);
    if ($problem === 'token') {
        return ['ok' => false, 'code' => 'token', 'message' => 'Platnost formuláře vypršela. Obnovte prosím stránku a odešlete ho znovu.'];
    }
    // kontrola „člověk v prohlížeči“ (JavaScript + skutečná interakce s formulářem)
    if (!hash_equals(strrev(form_human_key($token)), (string) ($post['_hc'] ?? ''))) {
        return form_blocked('no-js', $formId, ['ok' => false, 'code' => 'spam',
            'message' => 'Formulář se nepodařilo ověřit. Zkontrolujte, že máte zapnutý JavaScript, obnovte stránku a zkuste to znovu – případně nám napište na ' . site('email') . '.']);
    }
    // příliš rychlé odeslání po načtení stránky
    if (time() - (int) strtok($token, '.') < FORM_MIN_SECONDS) {
        return form_blocked('too-fast', $formId, $spam);
    }
    // nesmyslné texty a robotí e-maily
    foreach (form_post_strings($post) as $k => $v) {
        if (form_is_gibberish($v) || (str_contains($v, '@') && form_is_spam_email($v))) {
            return form_blocked('content:' . $k, $formId, $spam);
        }
    }
    return null;
}

/** Zablokované odeslání – jen záznam do logu (bez osobních údajů) */
function form_blocked(string $reason, string $formId, array $response): array
{
    error_log('LIONS form blocked (' . $formId . '): ' . $reason);
    return $response;
}

/** Limit úspěšných odeslání z jedné IP (ochrana proti zahlcení) */
function form_rate_error(string $action, int $limit = 10, int $window = 600): ?array
{
    try {
        if (!rate_limit($action, $limit, $window)) {
            return ['ok' => false, 'code' => 'rate', 'message' => 'Z vašeho připojení bylo odesláno příliš mnoho formulářů. Zkuste to prosím za pár minut.'];
        }
    } catch (Throwable $e) {
        error_log('LIONS rate limit failed: ' . $e->getMessage()); // nefunkční limit nesmí zablokovat odeslání
    }
    return null;
}

function render_form(string $id, array $prefill = [], array $opts = []): string
{
    $form = forms_config()[$id] ?? null;
    if (!$form) {
        return '';
    }
    ob_start(); ?>
    <?php $token = form_token($id); ?>
    <form class="lions-form" method="post" action="<?= e(url('api/form.php')) ?>" data-ajax data-form-id="<?= e($id) ?>"<?= form_human_attr($token) ?> novalidate>
      <?php $title = $opts['title'] ?? $form['title'] ?? null; if ($title): ?><h3 class="lions-form__title"><?= e($title) ?></h3><?php endif; ?>
      <?php if (!empty($form['description'])): ?><p class="lions-form__desc"><?= e($form['description']) ?></p><?php endif; ?>
      <input type="hidden" name="_form" value="<?= e($id) ?>">
      <input type="hidden" name="_token" value="<?= e($token) ?>">
      <input type="hidden" name="_hc" value="">
      <input type="hidden" name="_back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
      <?= honeypot_field() ?>
      <div class="lions-form__grid">
        <?php foreach ($form['fields'] as $f) echo render_field($f, $prefill[$f['name']] ?? null); ?>
      </div>
      <?= gdpr_checkbox(!empty($form['guardian'])) ?>
      <div class="lions-form__status<?= ($_GET['form'] ?? '') === 'ok' ? ' is-ok' : (($_GET['form'] ?? '') === 'error' ? ' is-error' : '') ?>" role="status" aria-live="polite"><?php
        if (($_GET['form'] ?? '') === 'ok') echo 'Děkujeme, formulář byl úspěšně odeslán.';
        elseif (($_GET['form'] ?? '') === 'error') echo 'Formulář se nepodařilo odeslat. Zkontrolujte prosím vyplněné údaje.';
      ?></div>
      <button type="submit" class="btn btn--red btn--lg"><?= e($form['submit'] ?? 'Odeslat') ?></button>
    </form>
    <?php return (string) ob_get_clean();
}

/* ---------- Číselné údaje: jednotný tvar + kontrola (stejná pravidla hlídá i app.js při psaní) ---------- */

/** Telefon jen české číslo: 9 číslic za +420 → „+420 123 456 789“. '' = prázdné, null = neplatné */
function norm_phone(string $v): ?string
{
    $d = preg_replace('/\D+/', '', $v) ?? '';
    if ($d === '') return '';
    if (strlen($d) === 14 && str_starts_with($d, '00420')) $d = substr($d, 5);
    if (strlen($d) === 12 && str_starts_with($d, '420')) $d = substr($d, 3);
    return preg_match('/^[1-9]\d{8}$/', $d) ? '+420 ' . substr($d, 0, 3) . ' ' . substr($d, 3, 3) . ' ' . substr($d, 6) : null;
}

/** PSČ: 5 číslic → „123 45“ */
function norm_psc(string $v): ?string
{
    $d = preg_replace('/\s+/', '', $v) ?? '';
    if ($d === '') return '';
    return preg_match('/^[1-9]\d{4}$/', $d) ? substr($d, 0, 3) . ' ' . substr($d, 3) : null;
}

/** Rodné číslo „123456/7890“ (10 číslic – narození po roce 1954): platný měsíc a den, dělitelnost 11 */
function norm_rc(string $v): ?string
{
    $d = preg_replace('/[\s\/]+/', '', $v) ?? '';
    if ($d === '') return '';
    if (!preg_match('/^\d{10}$/', $d)) return null;
    $m = (int) substr($d, 2, 2) % 50;
    $m = $m > 20 ? $m - 20 : $m;              // +50 ženy, +20 náhradní řada
    $day = (int) substr($d, 4, 2);
    if ($m < 1 || $m > 12 || $day < 1 || $day > 31) return null;
    if (strlen($d) === 10) {
        $mod = (int) substr($d, 0, 9) % 11;
        if (!((int) $d % 11 === 0 || ($mod === 10 && $d[9] === '0'))) return null;
    }
    return substr($d, 0, 6) . '/' . substr($d, 6);
}

/** Rok narození: 4 číslice, 1900 až letošek */
function norm_year(string $v): ?string
{
    $v = trim($v);
    if ($v === '') return '';
    return preg_match('/^\d{4}$/', $v) && (int) $v >= 1900 && (int) $v <= (int) date('Y') ? $v : null;
}

/** Pole pro telefon s pevnou předvolbou +420 */
function phone_input(string $id, string $name, bool $required): string
{
    return "<div class=\"tel\"><span class=\"tel__pre\" aria-hidden=\"true\">+420</span>"
        . "<input id=\"$id\" type=\"tel\" name=\"$name\" inputmode=\"numeric\" autocomplete=\"tel-national\" placeholder=\"123 456 789\" maxlength=\"11\""
        . " pattern=\"[1-9][0-9]{2} [0-9]{3} [0-9]{3}\" title=\"9 číslic, např. 777 123 456\" data-mask=\"phone\" aria-label=\"Telefon (+420)\"" . ($required ? ' required' : '') . "></div>";
}

function render_field(array $f, ?string $value = null): string
{
    $name = $f['name'];
    $req = !empty($f['required']);
    $star = $req ? ' <span class="req">*</span>' : '';
    $r = $req ? ' required' : '';
    $label = e($f['label']) . $star;
    $help = !empty($f['help']) ? '<small class="field__help">' . e($f['help']) . '</small>' : '';
    $id = 'f_' . $name . '_' . substr(md5((string) mt_rand()), 0, 4);
    $val = $value !== null ? ' value="' . e($value) . '"' : '';
    $half = in_array($f['type'], ['phone', 'text', 'select', 'rc', 'year'], true) && empty($f['full']) ? '' : ' field--full';

    switch ($f['type']) {
        case 'hidden':
            return "<input type=\"hidden\" name=\"$name\"$val>";
        case 'name':
            return "<fieldset class=\"field$half\"><legend>$label</legend><div class=\"field__row field__row--name\">"
                . "<input type=\"text\" name=\"{$name}[first]\" placeholder=\"Křestní jméno\" aria-label=\"" . e($f['label']) . " – křestní jméno\" autocomplete=\"given-name\"$r>"
                . "<input type=\"text\" name=\"{$name}[last]\" placeholder=\"Příjmení\" aria-label=\"" . e($f['label']) . " – příjmení\" autocomplete=\"family-name\"$r>"
                . "</div>$help</fieldset>";
        case 'email':
            $confirm = !empty($f['confirm'])
                ? "<input type=\"email\" name=\"{$name}_confirm\" placeholder=\"Potvrďte e-mail\" aria-label=\"Potvrďte e-mail\" autocomplete=\"email\"$r>" : '';
            $cls = $confirm ? 'field__row' : 'field__row field__row--1';
            return "<fieldset class=\"field" . ($confirm ? ' field--full' : '') . "\"><legend>$label</legend><div class=\"$cls\">"
                . "<input type=\"email\" name=\"$name\" placeholder=\"E-mail\" aria-label=\"" . e($f['label']) . "\" autocomplete=\"email\"$r>$confirm"
                . "</div>$help</fieldset>";
        case 'phone':
            return "<div class=\"field$half\"><label for=\"$id\">$label</label>" . phone_input($id, $name, $req) . "$help</div>";
        case 'rc':
            return "<div class=\"field$half\"><label for=\"$id\">$label</label><input id=\"$id\" type=\"text\" name=\"$name\" inputmode=\"numeric\" autocomplete=\"off\""
                . " placeholder=\"123456/7890\" maxlength=\"11\" pattern=\"[0-9]{6}/[0-9]{4}\" title=\"Rodné číslo ve tvaru 123456/7890\" data-mask=\"rc\"$r>$help</div>";
        case 'year':
            return "<div class=\"field$half\"><label for=\"$id\">$label</label><input id=\"$id\" type=\"text\" name=\"$name\" inputmode=\"numeric\""
                . " placeholder=\"např. 2016\" maxlength=\"4\" pattern=\"[0-9]{4}\" title=\"Rok narození, 4 číslice\" data-mask=\"year\"$val$r>$help</div>";
        case 'textarea':
            $tw = !empty($f['half']) ? '' : ' field--full';
            return "<div class=\"field$tw\"><label for=\"$id\">$label</label><textarea id=\"$id\" name=\"$name\" rows=\"2\"$r></textarea>$help</div>";
        case 'select':
            $o = '<option value="">— vyberte —</option>';
            foreach ($f['options'] as $opt) {
                $o .= '<option' . ($value === $opt ? ' selected' : '') . '>' . e($opt) . '</option>';
            }
            return "<div class=\"field$half\"><label for=\"$id\">$label</label><select id=\"$id\" name=\"$name\"$r>$o</select>$help</div>";
        case 'radio':
        case 'checkbox':
            $type = $f['type'];
            $n = $type === 'checkbox' ? $name . '[]' : $name;
            $cls = 'choices' . (!empty($f['inline']) ? ' choices--inline' : ' choices--cols');
            $o = '';
            foreach ($f['options'] as $i => $opt) {
                $rr = ($type === 'radio' && $req && $i === 0) ? ' required' : '';
                $chk = $value === $opt ? ' checked' : '';
                $o .= "<label class=\"choice\"><input type=\"$type\" name=\"$n\" value=\"" . e($opt) . "\"$rr$chk><span>" . e($opt) . '</span></label>';
            }
            $full = !empty($f['inline']) && empty($f['full']) ? '' : ' field--full';   // krátká volba (ANO/NE) zabere půl řádku
            return "<fieldset class=\"field$full\"><legend>$label</legend><div class=\"$cls\">$o</div>$help</fieldset>";
        case 'address':
            return "<fieldset class=\"field field--full\"><legend>$label</legend>"
                . "<div class=\"field__row field__row--addr\">"
                . "<input type=\"text\" name=\"{$name}[line1]\" placeholder=\"Ulice a číslo popisné\" aria-label=\"Ulice a číslo popisné\" autocomplete=\"address-line1\">"
                . "<input type=\"text\" name=\"{$name}[city]\" placeholder=\"Město\" aria-label=\"Město\" autocomplete=\"address-level2\">"
                . "<input type=\"text\" name=\"{$name}[zip]\" placeholder=\"PSČ\" aria-label=\"PSČ\" autocomplete=\"postal-code\" inputmode=\"numeric\" maxlength=\"6\" pattern=\"[1-9][0-9]{2} ?[0-9]{2}\" title=\"PSČ – 5 číslic, např. 253 01\" data-mask=\"psc\">"
                . "</div>$help</fieldset>";
        default:
            return "<div class=\"field$half\"><label for=\"$id\">$label</label><input id=\"$id\" type=\"text\" name=\"$name\"$val$r>$help</div>";
    }
}

/**
 * Zpracování odeslaného formuláře.
 * @return array{ok:bool, message:string, errors?:array}
 */
function handle_form(array $post): array
{
    $id = (string) ($post['_form'] ?? '');
    $form = forms_config()[$id] ?? null;
    if (!$form) {
        return ['ok' => false, 'message' => 'Neznámý formulář.'];
    }
    if ($err = form_guard($id, $post)) {
        return $err;
    }

    $clean = static fn($v) => trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $v) ?? '');
    $values = [];
    $errors = [];
    $replyTo = null;

    foreach ($form['fields'] as $f) {
        $n = $f['name'];
        $raw = $post[$n] ?? null;
        $req = !empty($f['required']);
        switch ($f['type']) {
            case 'name':
                $v = trim($clean($raw['first'] ?? '') . ' ' . $clean($raw['last'] ?? ''));
                if ($req && ($clean($raw['first'] ?? '') === '' || $clean($raw['last'] ?? '') === '')) {
                    $errors[$n] = 'Vyplňte jméno i příjmení.';
                }
                break;
            case 'address':
                $zip = norm_psc($clean($raw['zip'] ?? ''));
                if ($zip === null) {
                    $errors[$n] = 'PSČ musí mít 5 číslic, např. 253 01.';
                    $zip = '';
                }
                $v = implode(', ', array_filter(array_map($clean, [$raw['line1'] ?? '', $zip, $raw['city'] ?? ''])));
                break;
            case 'rc':
                $v = norm_rc($clean($raw));
                if ($v === null) {
                    $errors[$n] = 'Zadejte platné rodné číslo ve tvaru 123456/7890.';
                    $v = '';
                }
                break;
            case 'year':
                $v = norm_year($clean($raw));
                if ($v === null) {
                    $errors[$n] = 'Zadejte rok narození čtyřmi číslicemi, např. 2016.';
                    $v = '';
                }
                break;
            case 'checkbox':
                $allowed = $f['options'];
                $v = implode('; ', array_filter(array_map($clean, (array) $raw), fn($x) => in_array($x, $allowed, true)));
                break;
            case 'radio':
            case 'select':
                $v = $clean($raw);
                if ($v !== '' && !in_array($v, $f['options'], true)) {
                    $v = '';
                }
                break;
            case 'email':
                $v = mb_strtolower($clean($raw));
                if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    $errors[$n] = 'Zadejte platný e-mail.';
                } elseif (!empty($f['confirm']) && $v !== mb_strtolower($clean($post[$n . '_confirm'] ?? ''))) {
                    $errors[$n] = 'E-mailové adresy se neshodují.';
                }
                if ($v !== '' && !isset($errors[$n])) {
                    $replyTo ??= $v;
                }
                break;
            case 'phone':
                $v = norm_phone($clean($raw));
                if ($v === null) {
                    $errors[$n] = 'Zadejte 9místné telefonní číslo (za +420), např. 777 123 456.';
                    $v = '';
                }
                break;
            case 'hidden':
                $v = mb_substr($clean($raw), 0, 200);
                break;
            default:
                $v = mb_substr($clean($raw), 0, 5000);
        }
        if ($req && $v === '' && !isset($errors[$n])) {
            $errors[$n] = 'Toto pole je povinné.';
        }
        $values[$f['label']] = $v;
    }

    if ($g = gdpr_error($post)) {
        $errors['_gdpr'] = $g;
    }
    if ($errors) {
        return ['ok' => false, 'message' => 'Zkontrolujte prosím zvýrazněná pole.', 'errors' => $errors];
    }
    $values['Zpracování osobních údajů'] = 'seznámen(a) se zásadami ' . date('j. n. Y H:i') . (!empty($form['guardian']) ? ' (zákonný zástupce)' : '');
    if ($err = form_rate_error('form')) {
        return $err;
    }

    $subject = ($form['subject'] ?? 'Formulář z webu') . (!empty($form['subject_field']) && ($values[$form['subject_field']] ?? '') !== '' ? ' – ' . $values[$form['subject_field']] : '');

    // 1) Nejdřív bezpečně uložit (nic se neztratí, i kdyby e-mail neprošel)
    try {
        $sid = form_store($id, $values);
    } catch (Throwable $e) {
        error_log('LIONS form store failed: ' . $e->getMessage());
        $sid = null;
    }
    // 2) Poslat e-mail klubu a výsledek zapsat k záznamu
    $mail = form_mail($id, $subject, form_mask_sensitive($id, $values), $replyTo);
    if ($sid) {
        form_mark_mail($sid, $mail);
    }
    // 3) Potvrzení odesílateli (když selže, přihláška je i tak uložená a klub ji má)
    if ($replyTo && !empty($form['confirm'])) {
        $conf = form_send_confirmation($id, $form, $values, $post, $replyTo);
        if (!$conf['ok']) {
            error_log('LIONS: potvrzení formuláře ' . $id . ' se nepodařilo odeslat: ' . $conf['error']);
        }
    }

    if (!$sid && !$mail['ok']) {
        return ['ok' => false, 'code' => 'server', 'message' => 'Odeslání se nezdařilo. Napište nám prosím na ' . site('email') . '.'];
    }
    return ['ok' => true, 'message' => 'Děkujeme, formulář byl úspěšně odeslán.'];
}

/** Uloží odeslaný formulář do databáze, vrací ID záznamu */
function form_store(string $id, array $values): int
{
    $st = db()->prepare("INSERT INTO submissions (form, created, data, ip) VALUES (?, ?, ?, ?)");
    $st->execute([$id, date('c'), json_encode($values, JSON_UNESCAPED_UNICODE), client_ip()]);
    return (int) db()->lastInsertId();
}

function form_mark_mail(int $sid, array $mail): void
{
    try {
        db()->prepare('UPDATE submissions SET mail_status = ?, mail_error = ? WHERE id = ?')
            ->execute([$mail['ok'] ? 'sent' : 'failed', $mail['error'] ?? null, $sid]);
    } catch (Throwable $e) {
        error_log('LIONS mark mail failed: ' . $e->getMessage());
    }
}

/** Citlivé údaje (rodné číslo) se e-mailem neposílají – jen informace, kde je najít */
function form_mask_sensitive(string $formId, array $values): array
{
    foreach (form_sensitive_labels($formId) as $label) {
        if (($values[$label] ?? '') !== '') {
            $values[$label] = '(z bezpečnostních důvodů neposíláno e-mailem – najdete v administraci webu)';
        }
    }
    return $values;
}

/**
 * Komu chodí upozornění na vyplněný formulář: adresy nastavené u formuláře v administraci
 * (Formuláře → Kam chodí upozornění), jinak e-mail klubu z Nastavení.
 */
function form_recipients(string $formId): array
{
    $raw = (string) ((site('form_recipients') ?? [])[$formId] ?? '');
    $list = array_values(array_unique(array_filter(
        array_map('trim', preg_split('/[\s,;]+/', $raw) ?: []),
        fn($a) => (bool) filter_var($a, FILTER_VALIDATE_EMAIL)
    )));
    return $list ?: [(string) site('email')];
}

/** Stejný e-mail na více adres; ok jen když odešel všem */
function send_mail_many(array $to, string $subject, string $body, ?string $replyTo = null): array
{
    $errors = [];
    foreach ($to as $addr) {
        $r = send_mail($addr, $subject, $body, $replyTo);
        if (!$r['ok']) $errors[] = $addr . ': ' . ($r['error'] ?? 'chyba');
    }
    return $errors ? ['ok' => false, 'error' => implode('; ', $errors)] : ['ok' => true];
}

/** E-mail klubu s obsahem formuláře */
function form_mail(string $formId, string $subject, array $values, ?string $replyTo): array
{
    $body = '';
    foreach ($values as $k => $v) {
        $body .= $k . ":\n" . ($v === '' ? '—' : $v) . "\n\n";
    }
    $body .= "--\nOdesláno z webu " . ($_SERVER['HTTP_HOST'] ?? '') . ' ' . date('j. n. Y H:i') . "\nVšechny záznamy najdete v administraci webu.";
    return send_mail_many(form_recipients($formId), $subject, $body, $replyTo);
}

/** Tým vybraný ve formuláři (přihláška: název týmu, nábor: volba ze seznamu) */
function form_selected_team(array $form, array $post): ?array
{
    $field = $form['team_field'] ?? null;
    $value = trim((string) ($post[$field] ?? ''));
    if (!$field || $value === '') {
        return null;
    }
    if (isset($form['option_teams'][$value])) {
        return team($form['option_teams'][$value]);
    }
    foreach ($GLOBALS['TEAMS'] as $key => $t) {
        if (mb_strtolower($t['title']) === mb_strtolower($value)) {
            return team($key);
        }
    }
    return null;
}

/**
 * Potvrzovací e-mail pro toho, kdo formulář odeslal.
 * U přihlášek do týmu obsahuje tréninky, trenéra a co si vzít s sebou.
 */
function form_send_confirmation(string $id, array $form, array $values, array $post, string $to): array
{
    [$subject, $text, $html] = form_confirmation_message($id, $form, $values, $post);
    return send_mail($to, $subject, $text, (string) site('email'), $html);
}

/** Sestaví potvrzovací e-mail: [předmět, text, HTML] */
function form_confirmation_message(string $id, array $form, array $values, array $post): array
{
    $c = $form['confirm'];
    // oslovení křestním jménem z prvního pole typu „jméno“
    $first = '';
    foreach ($form['fields'] as $f) {
        if ($f['type'] !== 'name' || (!empty($form['greet_field']) && $f['name'] !== $form['greet_field'])) continue;
        $first = mb_substr(trim(preg_replace('~https?://\S+|www\.\S+~i', '', (string) ($post[$f['name']]['first'] ?? '')) ?? ''), 0, 30);
        break;
    }
    $intro = ($first !== '' ? 'Dobrý den, ' . e($first) . ',<br>' : 'Dobrý den,<br>') . e($c['intro']);
    $text = ($first !== '' ? "Dobrý den, $first,\n\n" : "Dobrý den,\n\n") . $c['intro'] . "\n\n";

    $rows = [];
    $button = null;
    $footer = '';
    $team = form_selected_team($form, $post);

    if ($team) {
        $teamHtml = '<span style="color:#be1e3e">' . e($team['label'] ?: $team['title']) . '</span>';
        $text .= "Tým: " . ($team['label'] ?: $team['title']) . "\n";
        foreach ($team['info'] as $line) {
            $plain = str_replace('**', '', $line);
            $teamHtml .= '<br><span style="color:#64708a;font-weight:400;font-size:13px">' . e($plain) . '</span>';
            $text .= $plain . "\n";
        }
        $rows[] = ['Tým', $teamHtml];
        if ($team['training']) {
            $list = '';
            $text .= "\n" . ($team['training_title'] ?: 'Tréninky') . "\n";
            foreach ($team['training'] as $tr) {
                $p = array_map('trim', explode('…', $tr));
                $list .= '<div style="margin:0 0 6px"><b style="color:#be1e3e">' . e($p[0]) . '</b> ' . e($p[1] ?? '')
                    . (isset($p[2]) ? '<br><span style="color:#64708a;font-weight:400;font-size:13px">' . e($p[2]) . '</span>' : '') . '</div>';
                $text .= '- ' . implode(', ', $p) . "\n";
            }
            $rows[] = [rtrim($team['training_title'] ?: 'Tréninky', ':'), $list];
        }
        $rows[] = ['S sebou', 'sálovou obuv, sportovní oblečení a pití'];
        $text .= "\nS sebou: sálovou obuv, sportovní oblečení a pití\n";
        $button = ['Stránka týmu ' . ($team['label'] ?: $team['title']), abs_url(url($team['key'] . '/'))];
        $text .= 'Stránka týmu: ' . abs_url(url($team['key'] . '/')) . "\n";
        $footer = 'Rodiče mohou sledovat tréninky z tribuny, posečkat v restauraci či se pro své dítko vrátit na konci tréninku. Těšíme se na Vás, LIONS.';
    } else {
        // shrnutí toho, co člověk poslal (bez citlivých údajů a bez technických řádků)
        $skip = array_merge(form_sensitive_labels($id), ['Zpracování osobních údajů']);
        // volné texty (zpráva, poznámka…) do potvrzení nedáváme – formulář by šel zneužít k rozesílání spamu
        foreach ($form['fields'] as $f) {
            if (in_array($f['type'], ['textarea', 'text', 'address'], true)) $skip[] = $f['label'];
        }
        $text .= "Shrnutí:\n";
        foreach ($values as $label => $v) {
            if ($v === '' || in_array($label, $skip, true)) continue;
            $rows[] = [rtrim($label, ':'), nl2br(e(mb_strimwidth($v, 0, 600, '…')))];
            $text .= rtrim($label, ':') . ': ' . $v . "\n";
        }
    }

    $footer .= ($footer ? '<br><br>' : '') . 'Máte dotaz? Stačí odpovědět na tento e-mail.';
    $text .= "\nMáte dotaz? Stačí odpovědět na tento e-mail.\n\nLIONS Handball\n" . site('email');

    return [$c['subject'], $text, email_template($c['heading'], $intro, $rows, $button, $footer)];
}
