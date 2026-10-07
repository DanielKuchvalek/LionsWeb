<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Pouze POST.']);
    exit;
}

$configs = [['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => ''], ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => ''], ['host' => '127.0.0.1', 'port' => 8889, 'user' => 'root', 'pass' => 'root']];
$pdo = null;
foreach ($configs as $cfg) {
    try {
        $pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname=lions_db;charset=utf8mb4", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        break;
    } catch (PDOException $e) { continue; }
}
if (!$pdo) { echo json_encode(['success' => false, 'message' => 'DB error.']); exit; }

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$category = trim($_POST['category'] ?? '');
$date = trim($_POST['booking_date'] ?? '');
$timeFrom = trim($_POST['time_from'] ?? '');
$timeTo = trim($_POST['time_to'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$rules = isset($_POST['rules_agreed']) ? 1 : 0;

if (!$rules || empty($fullName) || empty($date) || empty($timeFrom) || empty($timeTo)) {
    echo json_encode(['success' => false, 'message' => 'Vyplňte vše a odsouhlaste pravidla.']);
    exit;
}

if ($timeFrom >= $timeTo) {
    echo json_encode(['success' => false, 'message' => 'Čas KONCE musí být po čase ZAČÁTKU.']);
    exit;
}

// KONTROLA KOLIZE (Zabránění dvojí rezervaci na stejný čas)
try {
    $stmt_check = $pdo->prepare("SELECT id FROM gym_bookings WHERE booking_date = ? AND ((time_from < ? AND time_to > ?) OR (time_from < ? AND time_to > ?) OR (time_from >= ? AND time_to <= ?))");
    $stmt_check->execute([$date, $timeTo, $timeFrom, $timeTo, $timeFrom, $timeFrom, $timeTo]);
    if ($stmt_check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Tento termín se překrývá s již existující rezervací. Podívejte se do kalendáře nahoře a zvolte jiný čas.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO gym_bookings (full_name, email, phone, category, booking_date, time_from, time_to, notes, rules_agreed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fullName, $email, $phone, $category, $date, $timeFrom, $timeTo, $notes, $rules]);
    echo json_encode(['success' => true, 'message' => 'Vaše rezervace proběhla úspěšně!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Chyba: ' . $e->getMessage()]);
}
?>