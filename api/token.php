<?php
declare(strict_types=1);

/** Čerstvý ochranný token pro formulář (když stránka byla otevřená moc dlouho nebo z cache). */
require dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$form = (string) ($_GET['form'] ?? '');
if ($form !== 'minigym' && !isset(forms_config()[$form])) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}
// obnovený token je „starší“, aby automatické opětovné odeslání neneslo jako příliš rychlé
$token = form_token($form, FORM_MIN_SECONDS + 1);
echo json_encode(['ok' => true, 'token' => $token, 'hc' => form_human_key($token)]);
