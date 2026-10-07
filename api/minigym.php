<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(minigym_book($_POST), JSON_UNESCAPED_UNICODE);
        exit;
    }
    $date = (string) ($_GET['date'] ?? '');
    if (!minigym_valid_date($date)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Neplatné datum.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['ok' => true, 'slots' => minigym_slots($date)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('LIONS minigym error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Rezervační systém je dočasně nedostupný. Zkuste to prosím za chvíli.'], JSON_UNESCAPED_UNICODE);
}
