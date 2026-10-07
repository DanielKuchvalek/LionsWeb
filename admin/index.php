<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require ROOT . '/lib/admin.php';
require ROOT . '/lib/stats.php';

admin_session_start();
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');

$p = (string) ($_GET['p'] ?? 'dashboard');
$usersState = admin_users_state();
if ($usersState === 'broken') {
    // Soubor se správci existuje, ale je poškozený – NEnabízet založení nového správce
    http_response_code(503);
    render_admin('broken', ['title' => 'Administrace nedostupná'], false);
    exit;
}
$hasUsers = $usersState === 'ok';

/* ------------------------------------------------------------------
 * Přihlášení / první nastavení
 * ------------------------------------------------------------------ */
if (!$hasUsers) {
    // Web ještě nemá žádného správce → vytvoření prvního účtu
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $user = trim((string) post('user'));
        $pass = (string) post('pass');
        if ($user === '' || mb_strlen($pass) < 8 || $pass !== post('pass2')) {
            flash('Zadejte jméno a heslo (min. 8 znaků) a heslo zopakujte stejně.', 'error');
        } else {
            admin_save_users([['user' => $user, 'hash' => password_hash($pass, PASSWORD_DEFAULT), 'created' => date('c')]]);
            admin_login($user, $pass);
            flash('Účet správce byl vytvořen. Vítejte v administraci.');
            redirect(admin_url());
        }
        redirect(admin_url());
    }
    render_admin('setup', ['title' => 'Vytvoření správce'], false);
    exit;
}

if (!admin_user()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'login') {
        csrf_check();
        if (admin_is_locked()) {
            flash('Příliš mnoho neúspěšných pokusů. Zkuste to znovu za 10 minut.', 'error');
        } elseif (admin_login(trim((string) post('user')), (string) post('pass'))) {
            redirect(admin_url());
        } else {
            flash('Nesprávné jméno nebo heslo.', 'error');
        }
        redirect(admin_url());
    }
    render_admin('login', ['title' => 'Přihlášení'], false);
    exit;
}

if ($p === 'logout') {
    admin_logout();
    admin_session_start();
    flash('Byli jste odhlášeni.');
    redirect(admin_url());
}

/* ------------------------------------------------------------------
 * Akce (POST)
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) post('action');
    $back = (string) post('_back', admin_url($p));

    switch ($action) {
        /* ---------- miniGYM ---------- */
        case 'gym-save':
            [$data, $errors] = minigym_validate($_POST, true);
            $id = post('id') ?: null;
            if ($errors) {
                flash(implode(' ', $errors), 'error');
                $_SESSION['old'] = $_POST;
                redirect(admin_url('gym-edit', array_filter(['id' => $id, 'date' => $data['date'], 'time' => $data['time']])));
            }
            $r = minigym_save($data, $id, true, (bool) post('force'));
            flash($r['message'], $r['ok'] ? 'ok' : 'error');
            if (!$r['ok']) {
                $_SESSION['old'] = $_POST;
                redirect(admin_url('gym-edit', array_filter(['id' => $id, 'date' => $data['date'], 'time' => $data['time']])));
            }
            redirect(admin_url('gym', ['week' => $data['date']]));

        case 'gym-delete':
            $r = minigym_delete((string) post('id'));
            flash($r['message'], $r['ok'] ? 'ok' : 'error');
            redirect($back);

        /* ---------- Týmy ---------- */
        case 'team-save':
            $teams = $GLOBALS['TEAMS'];
            $old = (string) post('old_key');
            $key = slugify((string) post('key')) ?: $old;
            if ($key === '' || ($key !== $old && isset($teams[$key]))) {
                flash('Adresa stránky týmu musí být vyplněná a jedinečná.', 'error');
                redirect(admin_url('team-edit', ['t' => $old]));
            }
            $team = [
                'in_menu'        => (bool) post('in_menu'),
                'menu_label'     => trim((string) post('menu_label')),
                'title'          => trim((string) post('title')),
                'label'          => trim((string) post('label')),
                'home_tag'       => trim((string) post('home_tag')),
                'photo'          => trim((string) post('photo')) ?: null,
                'heading'        => trim((string) post('heading')) ?: null,
                'info'           => lines_of(post('info')),
                'training_title' => trim((string) post('training_title')),
                'training'       => lines_of(post('training')),
                'notes'          => lines_of(post('notes')),
                'cta'            => (bool) post('cta'),
                'register'       => (bool) post('register'),
                'sportlyzer'     => ctype_digit((string) post('sportlyzer')) ? (int) post('sportlyzer') : null,
                'jersey'         => null,   // sekce dresů byla z webu odstraněna
                'partners'       => [],
                'csh'            => [],
                'widgets'        => [],
            ];
            foreach ((array) post('tpartners', []) as $row) {
                if (!is_array($row)) continue;
                $row = array_map(fn($v) => trim((string) $v), $row);
                if (($row['logo'] ?? '') !== '') $team['partners'][] = [$row['url'] ?? '', $row['logo'], $row['name'] ?? ''];
            }
            foreach ((array) post('csh', []) as $c) {
                $slug = trim((string) ($c['slug'] ?? ''));
                if (preg_match('~handball\.cz/souteze/(muzi|zeny)/([a-z0-9\-]+)~', $slug, $m)) {
                    $c['sex'] = $m[1];
                    $slug = $m[2];
                }
                if ($slug !== '') $team['csh'][] = ['slug' => $slug, 'sex' => ($c['sex'] ?? '') === 'muzi' ? 'muzi' : 'zeny'];
            }
            foreach ((array) post('widgets', []) as $w) {
                $w = array_map(fn($v) => trim((string) $v), $w);
                if (($w['iframe'] ?? '') === '' && ($w['html'] ?? '') === '') continue;
                $team['widgets'][] = ['title' => $w['title'] ?? '', 'iframe' => $w['iframe'] ?? '', 'height' => (int) ($w['height'] ?? 600) ?: 600, 'html' => $w['html'] ?? ''];
            }
            if ($team['title'] === '') {
                flash('Vyplňte název týmu.', 'error');
                redirect(admin_url('team-edit', ['t' => $old]));
            }
            // zachovat pořadí; přejmenování klíče
            $new = [];
            if ($old === '' || !isset($teams[$old])) {
                $teams[$key] = $team;
                $new = $teams;
            } else {
                foreach ($teams as $k => $t) {
                    $new[$k === $old ? $key : $k] = $k === $old ? $team : $t;
                }
            }
            save_teams($new) ? flash('Tým „' . $team['title'] . '“ byl uložen.') : flash('Uložení se nezdařilo (práva k zápisu do storage/).', 'error');
            redirect(admin_url('team-edit', ['t' => $key]));

        case 'team-delete':
            $teams = $GLOBALS['TEAMS'];
            $k = (string) post('t');
            unset($teams[$k]);
            save_teams($teams);
            flash('Tým byl odstraněn.');
            redirect(admin_url('teams'));

        case 'team-move':
            $teams = $GLOBALS['TEAMS'];
            $keys = array_keys($teams);
            $i = array_search((string) post('t'), $keys, true);
            $j = $i === false ? false : $i + (post('dir') === 'up' ? -1 : 1);
            if ($i !== false && isset($keys[$j])) {
                [$keys[$i], $keys[$j]] = [$keys[$j], $keys[$i]];
                save_teams(array_combine($keys, array_map(fn($k) => $teams[$k], $keys)));
            }
            redirect(admin_url('teams'));

        /* ---------- Stránky (bloky) ---------- */
        case 'blocks-save':
            $g = (string) post('group');
            save_blocks($g, (array) post('f', [])) ? flash('Změny byly uloženy.') : flash('Uložení se nezdařilo.', 'error');
            redirect(admin_url('page-edit', ['g' => $g]));

        /* ---------- Nastavení ---------- */
        case 'form-recipients-save':
            $rec = [];
            $bad = [];
            foreach ((array) post('rec', []) as $fid => $raw) {
                $fid = (string) $fid;
                if (!preg_match('/^[a-z0-9-]{1,40}$/', $fid)) continue;
                $ok = [];
                foreach (preg_split('/[\s,;]+/', (string) $raw) ?: [] as $a) {
                    if ($a === '') continue;
                    filter_var($a, FILTER_VALIDATE_EMAIL) ? $ok[] = $a : $bad[] = $a;
                }
                if ($ok) $rec[$fid] = implode(', ', array_unique($ok));
            }
            if ($bad) {
                flash('Neplatná adresa: ' . implode(', ', array_slice($bad, 0, 3)) . '. Nic nebylo uloženo.', 'error');
                redirect(admin_url('forms'));
            }
            save_site_overrides(['form_recipients' => $rec])
                ? flash('Adresy pro upozornění byly uloženy.') : flash('Uložení se nezdařilo.', 'error');
            redirect(admin_url('forms'));

        case 'settings-save':
            $partners = [];
            foreach ((array) post('partners', []) as $row) {
                $row = array_map(fn($v) => trim((string) $v), $row);
                if (($row['logo'] ?? '') !== '') $partners[] = [$row['url'] ?? '', $row['logo'], $row['name'] ?? ''];
            }
            $links = [];
            foreach ((array) post('links', []) as $group => $rows) {
                $gname = trim((string) ($rows['_name'] ?? $group));
                unset($rows['_name']);
                foreach ($rows as $row) {
                    if (!is_array($row)) continue;
                    $row = array_map(fn($v) => trim((string) $v), $row);
                    if (($row['label'] ?? '') !== '' && ($row['url'] ?? '') !== '') $links[$gname][] = [$row['label'], $row['url']];
                }
                $links[$gname] ??= [];
            }
            $fb = [];
            $fbOld = site('facebook_pages') ?? [];
            foreach ((array) post('fb', []) as $k => $row) {
                if (!is_array($row)) continue;
                $token = preg_replace('/[^A-Za-z0-9_\-|]/', '', (string) ($row['token'] ?? ''));   // klíč bez mezer a jiných znaků
                if ($token === '') $token = empty($row['token_clear']) ? (string) ($fbOld[$k]['token'] ?? '') : '';
                $fb[$k] = ['name' => trim((string) ($row['name'] ?? '')), 'url' => trim((string) ($row['url'] ?? '')), 'token' => $token];
            }
            $smtpOld = site('smtp') ?? [];
            foreach (['email' => 'E-mail klubu', 'mail_from' => 'Odesílatel e-mailů', 'smtp_from' => 'Odesílatel (From)'] as $fk => $fl) {
                $fv = trim((string) post($fk));
                if ($fv !== '' && !filter_var($fv, FILTER_VALIDATE_EMAIL)) {
                    flash("$fl není platná e-mailová adresa.", 'error');
                    redirect(admin_url('settings'));
                }
            }
            if (preg_match('/[\r\n]/', (string) post('smtp_host') . post('smtp_user'))) {
                flash('Neplatné údaje SMTP.', 'error');
                redirect(admin_url('settings'));
            }
            $defaults = require ROOT . '/config/site.php';
            $ok = save_site_overrides([
                'smtp'      => [
                    'host'   => trim((string) post('smtp_host')),
                    'port'   => (int) post('smtp_port') ?: 587,
                    'secure' => in_array(post('smtp_secure'), ['ssl', 'tls', 'none'], true) ? post('smtp_secure') : 'tls',
                    'user'   => trim((string) post('smtp_user')),
                    'pass'   => (string) post('smtp_pass') !== '' ? (string) post('smtp_pass') : ($smtpOld['pass'] ?? ''),
                    'from'   => trim((string) post('smtp_from')),
                ],
                'retention' => ['forms' => max(1, (int) post('ret_forms') ?: 24), 'minigym' => max(1, (int) post('ret_gym') ?: 12)],
                'email'     => trim((string) post('email')),
                'mail_from' => trim((string) post('mail_from')),
                'address'   => trim((string) post('address')),
                'social'    => ['facebook' => trim((string) post('social_facebook')), 'instagram' => trim((string) post('social_instagram')), 'youtube' => trim((string) post('social_youtube'))],
                'sportlyzer'=> ['seed' => trim((string) post('sl_seed')), 'lang' => 'cze', 'club_url' => trim((string) post('sl_club'))],
                'csh'       => array_replace(site('csh'), ['team_name' => trim((string) post('csh_team')), 'home_venue' => trim((string) post('csh_venue'))]),
                'facebook_pages' => $fb,
                'cookie_banner' => post('cookie_banner') === '1',
                // neupravené seznamy se neukládají, aby se projevily nové výchozí hodnoty z config/site.php
                'partners'  => $partners === $defaults['partners'] ? null : $partners,
                'results_links' => $links === $defaults['results_links'] ? null : $links,
            ]);
            $ok ? flash('Nastavení bylo uloženo.') : flash('Uložení se nezdařilo.', 'error');
            redirect(admin_url('settings'));

        /* ---------- Média ---------- */
        case 'media-upload':
            $files = $_FILES['files'] ?? null;
            $n = 0;
            if ($files && is_array($files['name'])) {
                foreach ($files['name'] as $i => $_) {
                    $r = media_upload(['name' => $files['name'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]]);
                    $r['ok'] ? $n++ : flash($files['name'][$i] . ': ' . $r['message'], 'error');
                }
            }
            if ($n) flash("Nahráno souborů: $n");
            redirect(admin_url('media'));

        case 'media-delete':
            media_delete((string) post('path')) ? flash('Soubor byl smazán.') : flash('Tento soubor nelze smazat (původní média webu jsou chráněná).', 'error');
            redirect(admin_url('media'));

        /* ---------- Formuláře ---------- */
        case 'submission-delete':
            db()->prepare('DELETE FROM submissions WHERE id = ?')->execute([(int) post('id')]);
            flash('Záznam byl smazán.');
            redirect($back);

        case 'submission-resend':
            $st = db()->prepare('SELECT * FROM submissions WHERE id = ?');
            $st->execute([(int) post('id')]);
            if ($row = $st->fetch()) {
                $values = json_decode($row['data'], true) ?: [];
                $reply = null;
                foreach ($values as $v) if (filter_var($v, FILTER_VALIDATE_EMAIL)) { $reply = $v; break; }
                $cfg = forms_config()[$row['form']] ?? [];
                $mail = form_mail($row['form'], ($cfg['subject'] ?? 'Formulář z webu') . ' (znovu odesláno)', form_mask_sensitive($row['form'], $values), $reply);
                form_mark_mail((int) $row['id'], $mail);
                $mail['ok'] ? flash('E-mail byl odeslán.') : flash('E-mail se nepodařilo odeslat: ' . $mail['error'], 'error');
            }
            redirect($back);

        /* ---------- Systém ---------- */
        case 'cache-clear':
            flash('Data z handball.cz se obnoví při příštím zobrazení (smazáno ' . clear_csh_cache() . ' souborů cache).');
            redirect(admin_url());

        /* ---------- GDPR ---------- */
        case 'gdpr-delete':
            $sub = array_map('intval', (array) post('sub', []));
            $bk = array_map('strval', (array) post('bk', []));
            $n = 0;
            if ($sub) {
                $st = db()->prepare('DELETE FROM submissions WHERE id IN (' . implode(',', array_fill(0, count($sub), '?')) . ')');
                $st->execute($sub);
                $n += $st->rowCount();
            }
            if ($bk) {
                $st = db()->prepare('DELETE FROM bookings WHERE id IN (' . implode(',', array_fill(0, count($bk), '?')) . ')');
                $st->execute($bk);
                $n += $st->rowCount();
            }
            flash("Smazáno záznamů: $n. Ze záloh zmizí nejpozději do 30 dní.");
            redirect(admin_url('gdpr', ['q' => (string) post('q')]));

        case 'gdpr-cleanup':
            $r = gdpr_cleanup();
            flash("Hotovo – smazáno formulářů: {$r['forms']}, rezervací: {$r['bookings']}, anonymizováno IP adres: {$r['ips']}.");
            redirect(admin_url('gdpr'));

        case 'mail-test':
            $r = send_mail((string) site('email'), 'LIONS web – testovací e-mail', "Tento e-mail potvrzuje, že odesílání z webu funguje.\n\n" . date('j. n. Y H:i'));
            $r['ok'] ? flash('Testovací e-mail byl odeslán na ' . site('email') . '. Zkontrolujte schránku (i složku spam).') : flash('E-mail se nepodařilo odeslat: ' . $r['error'], 'error');
            redirect($back);

        case 'csh-discover':
            $_SESSION['discover'] = csh_discover_competitions();
            redirect(admin_url('', ['discover' => 1]));

        /* ---------- Účet ---------- */
        case 'password':
            $users = admin_users();
            foreach ($users as &$u) {
                if ($u['user'] === admin_user()) {
                    if (!password_verify((string) post('old'), $u['hash'])) {
                        flash('Současné heslo nesouhlasí.', 'error');
                    } elseif (mb_strlen((string) post('new')) < 8 || post('new') !== post('new2')) {
                        flash('Nové heslo musí mít alespoň 8 znaků a obě pole se musí shodovat.', 'error');
                    } else {
                        $u['hash'] = password_hash((string) post('new'), PASSWORD_DEFAULT);
                        admin_save_users($users);
                        $_SESSION['fp'] = admin_fingerprint($u);   // ostatní přihlášení tohoto účtu přestanou platit
                        flash('Heslo bylo změněno.');
                    }
                }
            }
            unset($u);
            redirect(admin_url('account'));

        case 'user-add':
            $users = admin_users();
            $user = trim((string) post('user'));
            if ($user === '' || mb_strlen((string) post('pass')) < 8) {
                flash('Zadejte jméno a heslo (min. 8 znaků).', 'error');
            } elseif (array_filter($users, fn($u) => mb_strtolower($u['user']) === mb_strtolower($user))) {
                flash('Uživatel s tímto jménem už existuje.', 'error');
            } else {
                $users[] = ['user' => $user, 'hash' => password_hash((string) post('pass'), PASSWORD_DEFAULT), 'created' => date('c')];
                admin_save_users($users);
                flash("Správce „{$user}“ byl přidán.");
            }
            redirect(admin_url('account'));

        case 'user-delete':
            $users = admin_users();
            $del = (string) post('user');
            if (admin_is_owner($del)) {
                flash('Hlavního správce webu nelze odebrat.', 'error');
            } elseif (!admin_can_delete($del)) {
                flash('Ostatní správce může odebrat jen hlavní správce webu.', 'error');
            } else {
                $self = $del === admin_user();
                admin_save_users(array_filter($users, fn($u) => $u['user'] !== $del));
                if ($self) {
                    admin_logout();
                    redirect(admin_url());
                }
                flash("Správce „{$del}“ byl odebrán.");
            }
            redirect(admin_url('account'));
    }
    redirect($back);
}

/* ------------------------------------------------------------------
 * Zobrazení
 * ------------------------------------------------------------------ */
$pages = [
    'dashboard' => 'Přehled', 'gym' => 'miniGYM rezervace', 'gym-edit' => 'Rezervace',
    'teams' => 'Týmy', 'team-edit' => 'Úprava týmu', 'pages' => 'Stránky a texty', 'page-edit' => 'Úprava stránky',
    'media' => 'Obrázky a videa', 'forms' => 'Přijaté formuláře', 'form-view' => 'Přijaté formuláře',
    'settings' => 'Nastavení webu', 'account' => 'Účet a správci', 'health' => 'Stav systému', 'gdpr' => 'Osobní údaje (GDPR)', 'stats' => 'Návštěvnost a prokliky',
];
if (!isset($pages[$p])) {
    $p = 'dashboard';
}
render_admin($p, ['title' => $pages[$p], 'p' => $p]);

/* ------------------------------------------------------------------ */

function render_admin(string $view, array $vars = [], bool $layout = true): void
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require ROOT . '/admin/views/' . $view . '.php';
    $body = ob_get_clean();
    if (!$layout) {
        require ROOT . '/admin/views/_bare.php';
        return;
    }
    require ROOT . '/admin/views/_layout.php';
}
