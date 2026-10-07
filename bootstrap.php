<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Prague');
ini_set('display_errors', '0');   // chyby jen do logu, nikdy návštěvníkům
ini_set('log_errors', '1');
mb_internal_encoding('UTF-8');

define('ROOT', __DIR__);
define('STORAGE', ROOT . '/storage');

require ROOT . '/lib/content.php';

// Konfigurace + úpravy uložené z administrace
$GLOBALS['SITE']  = load_site();
$GLOBALS['TEAMS'] = load_teams();

require ROOT . '/lib/helpers.php';
require ROOT . '/lib/db.php';
require ROOT . '/lib/mailer.php';
require ROOT . '/lib/csh.php';
require ROOT . '/lib/consent.php';
require ROOT . '/lib/widgets.php';
require ROOT . '/lib/forms.php';
require ROOT . '/lib/minigym.php';
require ROOT . '/lib/seo.php';

define('BASE_URL', detect_base_url());
send_security_headers();

// Jednou denně údržba: smazání údajů po uplynutí lhůt (GDPR) + záloha databáze.
// Běží po odeslání stránky, takže návštěvníka nezdržuje.
register_shutdown_function(static function (): void {
    if (is_file(STORAGE . '/lions.sqlite') && !is_file(STORAGE . '/backups/.maintenance-' . date('Y-m-d'))) {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        db_daily_maintenance();
    }
});
