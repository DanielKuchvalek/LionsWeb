<?php
declare(strict_types=1);

/* =====================================================================
 * Administrace – přihlášení, zabezpečení, pomocné funkce
 * ===================================================================== */

const ADMIN_IDLE_TIMEOUT = 8 * 3600;
const ADMIN_MAX_ATTEMPTS = 5;
const ADMIN_LOCK_SECONDS = 600;

function admin_dir(): string
{
    $d = STORAGE . '/admin';
    if (!is_dir($d)) {
        @mkdir($d, 0775, true);
    }
    return $d;
}

function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('lions_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (BASE_URL ?: '') . '/admin',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
    if (!empty($_SESSION['user']) && (time() - ($_SESSION['last'] ?? 0)) > ADMIN_IDLE_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last'] = time();
}

const ADMIN_MAX_SESSION = 12 * 3600;   // přihlášení vyprší nejpozději po 12 hodinách

function admin_users(): array
{
    $u = json_read(admin_dir() . '/users.json', null);
    return is_array($u) ? $u : [];
}

/**
 * Stav souboru se správci:
 *  'missing' – soubor neexistuje → lze založit prvního správce,
 *  'ok'      – v pořádku,
 *  'broken'  – soubor existuje, ale nejde přečíst → NIKDY nenabízet založení (útočník by převzal web).
 */
function admin_users_state(): string
{
    $f = admin_dir() . '/users.json';
    if (!file_exists($f)) {
        return 'missing';
    }
    $u = is_readable($f) ? json_decode((string) file_get_contents($f), true) : null;
    return (is_array($u) && $u !== []) ? 'ok' : 'broken';
}

/**
 * Hlavní správce = první účet v seznamu (založený při instalaci webu).
 * Jen on smí odebírat ostatní správce a jeho samotného nemůže odebrat nikdo.
 */
function admin_owner(): ?string
{
    $users = admin_users();
    return isset($users[0]['user']) ? (string) $users[0]['user'] : null;
}

function admin_is_owner(?string $user = null): bool
{
    $user ??= admin_user();
    return $user !== null && $user === admin_owner();
}

/** Smí přihlášený správce odebrat účet $target? Hlavní správce ostatní, ostatní jen sami sebe. */
function admin_can_delete(string $target): bool
{
    $me = admin_user();
    if ($me === null || admin_is_owner($target)) {
        return false;
    }
    return admin_is_owner($me) || $target === $me;
}

function admin_save_users(array $users): bool
{
    return json_write(admin_dir() . '/users.json', array_values($users));
}

/** Otisk hesla – když se heslo změní nebo je účet smazán, stará přihlášení přestanou platit */
function admin_fingerprint(array $u): string
{
    return substr(hash('sha256', $u['user'] . '|' . $u['hash']), 0, 24);
}

function admin_user(): ?string
{
    static $checked = false;
    $name = $_SESSION['user'] ?? null;
    if ($name === null || $checked) {
        return $name;
    }
    $checked = true;
    $valid = false;
    foreach (admin_users() as $u) {
        if ($u['user'] === $name && hash_equals(admin_fingerprint($u), (string) ($_SESSION['fp'] ?? ''))) {
            $valid = true;
            break;
        }
    }
    if (!$valid || time() - (int) ($_SESSION['since'] ?? 0) > ADMIN_MAX_SESSION) {
        admin_logout();
        return null;
    }
    return $name;
}

function admin_is_locked(): bool
{
    try {
        // na jedné IP max. 5 chybných pokusů za 10 min, celkem ze všech IP max. 50 (útok z více adres)
        $st = db()->prepare("SELECT COUNT(*) FROM hits WHERE action = 'login-fail' AND t > ?");
        $st->execute([time() - ADMIN_LOCK_SECONDS]);
        $all = (int) $st->fetchColumn();
        $st->closeCursor();
        return rate_count('login-fail', ADMIN_LOCK_SECONDS) >= ADMIN_MAX_ATTEMPTS || $all >= 50;
    } catch (Throwable $e) {
        error_log('LIONS login lock check failed: ' . $e->getMessage());
        return true; // při chybě databáze raději nepustit
    }
}

function admin_login(string $user, string $pass): bool
{
    // Každý pokus se atomicky započítá PŘED ověřením hesla – souběžné pokusy limit neobejdou
    try {
        if (!rate_limit('login-attempt', 10, ADMIN_LOCK_SECONDS) || admin_is_locked()) {
            usleep(600000);
            return false;
        }
    } catch (Throwable $e) {
        return false;
    }
    $found = null;
    foreach (admin_users() as $u) {
        if (hash_equals(mb_strtolower($u['user']), mb_strtolower($user))) {
            $found = $u;
            break;
        }
    }
    // stejná doba odpovědi, ať jméno existuje nebo ne (nejde zjistit platná jména)
    $okPass = password_verify($pass, $found['hash'] ?? '$2y$10$0000000000000000000000uEaw5G0m1b7uB2sCz1JvZ7hJjVZxS6e');
    if ($found && $okPass) {
        session_regenerate_id(true);
        $_SESSION['user'] = $found['user'];
        $_SESSION['fp'] = admin_fingerprint($found);
        $_SESSION['since'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
        return true;
    }
    try {
        rate_limit('login-fail', PHP_INT_MAX, ADMIN_LOCK_SECONDS); // zaznamenat neúspěšný pokus
    } catch (Throwable $e) {
    }
    usleep(600000); // zpomalení hádání hesla
    return false;
}

function admin_logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '');
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        exit('Neplatný bezpečnostní token. Obnovte stránku a zkuste to znovu.');
    }
}

function flash(?string $msg = null, string $type = 'ok'): array
{
    if ($msg !== null) {
        $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
        return [];
    }
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function admin_url(string $page = '', array $q = []): string
{
    $q = $page !== '' ? ['p' => $page] + $q : $q;
    return url('admin/') . ($q ? '?' . http_build_query($q) : '');
}

function redirect(string $to): never
{
    header('Location: ' . $to, true, 303);
    exit;
}

function post(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

/** Textarea → pole řádků */
function lines_of($text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $text) ?: []), 'strlen'));
}

/* =====================================================================
 * Média
 * ===================================================================== */

const MEDIA_IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
const MEDIA_VIDEO_TYPES = ['video/mp4' => 'mp4'];
const MEDIA_MAX_SIDE = 2000;

function media_root(): string
{
    return ROOT . '/assets/media';
}

function slugify(string $s): string
{
    $s = strtr($s, ['á'=>'a','č'=>'c','ď'=>'d','é'=>'e','ě'=>'e','í'=>'i','ň'=>'n','ó'=>'o','ř'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ů'=>'u','ý'=>'y','ž'=>'z',
        'Á'=>'A','Č'=>'C','Ď'=>'D','É'=>'E','Ě'=>'E','Í'=>'I','Ň'=>'N','Ó'=>'O','Ř'=>'R','Š'=>'S','Ť'=>'T','Ú'=>'U','Ů'=>'U','Ý'=>'Y','Ž'=>'Z']);
    $s = preg_replace('/[^A-Za-z0-9]+/', '-', $s) ?? '';
    return trim(strtolower($s), '-') ?: 'soubor';
}

/**
 * Uloží nahraný soubor do assets/media/uploads/ROK/MĚSÍC/.
 * Obrázky větší než 2000 px se zmenší. Vrací relativní cestu (pro media()).
 */
function media_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $msg = match ($file['error'] ?? 0) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Soubor je příliš velký (max. ' . ini_get('upload_max_filesize') . ').',
            UPLOAD_ERR_NO_FILE => 'Nebyl vybrán žádný soubor.',
            default => 'Nahrání se nezdařilo.',
        };
        return ['ok' => false, 'message' => $msg];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    $ext = MEDIA_IMAGE_TYPES[$mime] ?? MEDIA_VIDEO_TYPES[$mime] ?? null;
    if (!$ext) {
        return ['ok' => false, 'message' => 'Nepodporovaný typ souboru. Povolené jsou obrázky JPG, PNG, GIF, WEBP a video MP4.'];
    }
    if (isset(MEDIA_IMAGE_TYPES[$mime]) && !@getimagesize($file['tmp_name'])) {
        return ['ok' => false, 'message' => 'Soubor není platný obrázek.'];
    }

    $rel = 'uploads/' . date('Y/m') . '/';
    $dir = media_root() . '/' . $rel;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return ['ok' => false, 'message' => 'Nelze vytvořit složku pro nahrání (práva k zápisu do assets/media).'];
    }
    $base = slugify(pathinfo((string) $file['name'], PATHINFO_FILENAME));
    $name = $base . '.' . $ext;
    for ($i = 2; is_file($dir . $name); $i++) {
        $name = "$base-$i.$ext";
    }
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        return ['ok' => false, 'message' => 'Soubor se nepodařilo uložit.'];
    }
    @chmod($dir . $name, 0664);
    if (isset(MEDIA_IMAGE_TYPES[$mime])) {
        media_downscale($dir . $name, $mime);
    }
    return ['ok' => true, 'path' => $rel . $name, 'url' => media($rel . $name)];
}

function media_downscale(string $path, string $mime): void
{
    if (!function_exists('imagecreatetruecolor') || $mime === 'image/gif') {
        return;
    }
    [$w, $h] = @getimagesize($path) ?: [0, 0];
    if (max($w, $h) <= MEDIA_MAX_SIDE) {
        return;
    }
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png'  => @imagecreatefrompng($path),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        default      => false,
    };
    if (!$src) {
        return;
    }
    $ratio = MEDIA_MAX_SIDE / max($w, $h);
    $nw = (int) round($w * $ratio);
    $nh = (int) round($h * $ratio);
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    match ($mime) {
        'image/jpeg' => imagejpeg($dst, $path, 82),
        'image/png'  => imagepng($dst, $path, 8),
        'image/webp' => function_exists('imagewebp') ? imagewebp($dst, $path, 82) : null,
        default      => null,
    };
    imagedestroy($src);
    imagedestroy($dst);
}

/** Seznam všech médií (nejnovější první). $type = image|video|'' */
function media_list(string $type = '', string $search = ''): array
{
    $root = media_root();
    $out = [];
    $manifest = media_manifest();
    $optimizedTargets = array_flip(array_filter($manifest, fn($v, $k) => $v !== $k, ARRAY_FILTER_USE_BOTH));
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        /** @var SplFileInfo $f */
        $ext = strtolower($f->getExtension());
        $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
        $isVid = $ext === 'mp4';
        if (!$isImg && !$isVid) continue;
        if ($type === 'image' && !$isImg) continue;
        if ($type === 'video' && !$isVid) continue;
        $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($root))), '/');
        // Optimalizované kopie (PNG→JPG) zobrazujeme pod původní cestou z manifestu
        $rel = $optimizedTargets[$rel] ?? $rel;
        // WP náhledy (-300x200) nezobrazujeme, pokud existuje velká verze
        if (preg_match('/-\d+x\d+\.\w+$/', $rel) && isset($manifest[preg_replace('/-\d+x\d+(\.\w+)$/', '$1', $rel)])) continue;
        if ($search !== '' && mb_stripos($rel, $search) === false) continue;
        $out[] = [
            'path'  => $rel,
            'url'   => media($rel),
            'name'  => basename($rel),
            'type'  => $isImg ? 'image' : 'video',
            'size'  => $f->getSize(),
            'mtime' => $f->getMTime(),
            'own'   => str_starts_with($rel, 'uploads/'),
        ];
    }
    usort($out, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
    return $out;
}

function media_delete(string $rel): bool
{
    $rel = ltrim($rel, '/');
    if (!str_starts_with($rel, 'uploads/') || str_contains($rel, '..')) {
        return false; // původní média z webu mazat nejde (jsou použitá napříč webem)
    }
    $path = media_root() . '/' . $rel;
    return is_file($path) && @unlink($path);
}

function human_size(int $bytes): string
{
    return $bytes > 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, (int) round($bytes / 1024)) . ' kB';
}

/* =====================================================================
 * Uložení obsahu
 * ===================================================================== */

function save_site_overrides(array $changes): bool
{
    $file = content_dir() . '/site.json';
    $over = json_read($file);
    foreach ($changes as $k => $v) {
        if ($v === null) {
            unset($over[$k]);   // stejné jako výchozí hodnota v config/site.php → neukládat
        } else {
            $over[$k] = $v;
        }
    }
    return json_write($file, $over);
}

function save_teams(array $teams): bool
{
    return json_write(content_dir() . '/teams.json', $teams);
}

function save_blocks(string $group, array $values): bool
{
    $reg = blocks_registry()[$group] ?? null;
    if (!$reg) {
        return false;
    }
    $file = content_dir() . '/blocks.json';
    $over = json_read($file);
    foreach ($reg['fields'] as $field => $def) {
        if (!array_key_exists($field, $values)) continue;
        $key = "$group.$field";
        $v = str_replace("\r\n", "\n", trim((string) $values[$field]));
        if ($v === str_replace("\r\n", "\n", trim((string) $def['default']))) {
            unset($over[$key]); // shodné s originálem – neukládat
        } else {
            $over[$key] = $v;
        }
    }
    return json_write($file, $over);
}

function clear_csh_cache(): int
{
    $n = 0;
    foreach (glob(STORAGE . '/cache/csh_*.json') ?: [] as $f) {
        $n += (int) @unlink($f);
    }
    return $n;
}

/**
 * Najde soutěže na handball.cz, ve kterých hraje LIONS (pro přípravu nové sezóny).
 * Projde odkazy na soutěže z hlavních stránek svazu a zkontroluje přihlášené týmy.
 */
function csh_discover_competitions(): array
{
    @set_time_limit(180);
    $base = csh_cfg('base');
    $slugs = [];
    foreach (['/', '/mladez', '/regiony/stredocesky', '/regiony/praha'] as $p) {
        $ch = curl_init($base . $p);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_USERAGENT => 'LIONS-Handball-Web/1.0']);
        $html = (string) curl_exec($ch);
        curl_close($ch);
        if (preg_match_all('~/souteze/(muzi|zeny)/([a-z0-9\-]+)~', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) $slugs[$x[2]] = $x[1];
        }
    }
    $found = [];
    foreach ($slugs as $slug => $sex) {
        $teams = csh_http_get($base . '/api/public/competition/' . rawurlencode($slug) . '/competition-teams');
        foreach ($teams['data'] ?? [] as $t) {
            if (csh_is_lions($t['data']['fullName'] ?? '')) {
                $comp = csh_http_get($base . '/api/public/competition/' . rawurlencode($slug));
                $found[] = ['slug' => $slug, 'sex' => $sex, 'name' => $comp['data']['name'] ?? $slug,
                            'active' => !empty($comp['data']['competitionParts'])];
                break;
            }
        }
    }
    return $found;
}

/* =====================================================================
 * Prvky formulářů administrace
 * ===================================================================== */

function a_text(string $name, $value, string $label, string $help = '', array $attr = []): string
{
    $type = $attr['type'] ?? 'text';
    unset($attr['type']);
    $a = '';
    foreach ($attr as $k => $v) $a .= ' ' . $k . '="' . e($v) . '"';
    return '<label class="af"><span class="af__label">' . e($label) . '</span><input type="' . e($type) . '" name="'  . e($name) . '" value="' . e($value) . '"' . $a . '>'
        . ($help ? '<small>' . e($help) . '</small>' : '') . '</label>';
}

function a_textarea(string $name, $value, string $label, string $help = '', int $rows = 4, ?string $default = null): string
{
    $reset = $default !== null && trim((string) $value) !== trim($default)
        ? '<button type="button" class="linkbtn" data-reset="' . e($default) . '">Vrátit původní</button>' : '';
    return '<label class="af"><span class="af__label">' . e($label) . $reset . '</span><textarea name="' . e($name) . '" rows="' . $rows . '">' . e($value) . '</textarea>'
        . ($help ? '<small>' . e($help) . '</small>' : '') . '</label>';
}

function a_check(string $name, bool $checked, string $label, string $help = ''): string
{
    return '<label class="af af--check"><input type="checkbox" name="' . e($name) . '" value="1"' . ($checked ? ' checked' : '') . '><span>' . e($label)
        . ($help ? '<small>' . e($help) . '</small>' : '') . '</span></label>';
}

/** Výběr jednoho obrázku/videa z knihovny (s náhledem a nahráním) */
function a_media(string $name, ?string $value, string $label, string $type = 'image'): string
{
    $value = (string) $value;
    $preview = $value === '' ? '<span class="mp__empty">nevybráno</span>'
        : ($type === 'video' ? '<video src="' . e(media($value)) . '#t=0.5" preload="metadata" muted></video>' : '<img src="' . e(media($value)) . '" alt="">');
    return '<div class="af"><span class="af__label">' . e($label) . '</span>'
        . '<div class="mp" data-media-pick="' . e($type) . '">'
        . '<div class="mp__preview">' . $preview . '</div>'
        . '<div class="mp__side"><input type="text" name="' . e($name) . '" value="' . e($value) . '" data-media-input>'
        . '<div class="mp__btns"><button type="button" class="btn btn--sm" data-media-open>Vybrat / nahrát</button>'
        . '<button type="button" class="btn btn--sm btn--ghost" data-media-clear>Odebrat</button></div></div></div></div>';
}

/** Seznam obrázků (jeden na řádek) s náhledy a přidáváním z knihovny */
function a_media_list(string $name, string $value, string $label): string
{
    $thumbs = '';
    foreach (lines_of($value) as $p) {
        $thumbs .= '<figure data-path="' . e($p) . '"><img src="' . e(media($p)) . '" alt=""><button type="button" title="Odebrat" data-ml-remove>×</button></figure>';
    }
    return '<div class="af"><span class="af__label">' . e($label) . '</span>'
        . '<div class="ml" data-media-list><div class="ml__grid">' . $thumbs . '</div>'
        . '<textarea name="' . e($name) . '" hidden data-ml-value>' . e($value) . '</textarea>'
        . '<button type="button" class="btn btn--sm" data-ml-add>+ Přidat obrázek</button> <small>Pořadí změníte přetažením.</small></div></div>';
}

function a_select(string $name, $value, string $label, array $options): string
{
    $o = '';
    foreach ($options as $k => $v) $o .= '<option value="' . e($k) . '"' . ((string) $k === (string) $value ? ' selected' : '') . '>' . e($v) . '</option>';
    return '<label class="af"><span class="af__label">' . e($label) . '</span><select name="' . e($name) . '">' . $o . '</select></label>';
}
