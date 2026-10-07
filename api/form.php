<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

try {
    $result = handle_form($_POST);
} catch (Throwable $e) {
    error_log('LIONS form error: ' . $e->getMessage());
    $result = ['ok' => false, 'code' => 'server', 'message' => 'Odeslání se nezdařilo. Zkuste to prosím znovu nebo nám napište na ' . site('email') . '.'];
}

if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// Bez JavaScriptu: vrátit se zpět na stránku se zprávou
$back = (string) strtok((string) ($_POST['_back'] ?? ''), '?#');
// jen relativní cesta tohoto webu (žádné //, \\, protokoly ani řídicí znaky → nelze přesměrovat jinam)
if (!preg_match('#^/(?![/\\\\])[A-Za-z0-9/_.\-%~]*$#', $back)) {
    $back = url();
}
header('Location: ' . $back . '?form=' . ($result['ok'] ? 'ok' : 'error'), true, 303);
