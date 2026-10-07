<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$configs = [['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => ''], ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => ''], ['host' => '127.0.0.1', 'port' => 8889, 'user' => 'root', 'pass' => 'root']];
$pdo = null;
foreach ($configs as $cfg) {
    try {
        $pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname=lions_db;charset=utf8mb4", $cfg['user'], $cfg['pass'], [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        break;
    } catch (PDOException $e) { continue; }
}

if (!$pdo) { echo json_encode([]); exit; }

$events = [];
$stmt = $pdo->query("SELECT * FROM gym_bookings ORDER BY booking_date, time_from");
while ($row = $stmt->fetch()) {
    $events[] = [
        'title' => 'Rezervováno (' . $row['category'] . ')',
        'start' => $row['booking_date'] . 'T' . $row['time_from'],
        'end'   => $row['booking_date'] . 'T' . $row['time_to'],
        'color' => '#d9232a'
    ];
}

echo json_encode($events);
?>