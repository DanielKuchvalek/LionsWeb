<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$configs = [
    ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'port' => 8889, 'user' => 'root', 'pass' => 'root']
];
$pdo = null;
foreach ($configs as $cfg) {
    try {
        $pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname=lions_db;charset=utf8mb4", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        break;
    } catch (PDOException $e) { continue; }
}
if (!$pdo) { echo json_encode(['error' => 'Chyba databáze.']); exit; }

try {
    $stmt = $pdo->query("SELECT * FROM upcoming_matches WHERE is_active = 1 ORDER BY match_date ASC");
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) { echo json_encode(['error' => $e->getMessage()]); }
?>