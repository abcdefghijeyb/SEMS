<?php
session_start();
$host = 'localhost';
$db   = 'sems_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit('Database connection failed: ' . $e->getMessage());
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'participant') {
    header("Location: access_denied.php");
    exit;
}
$user_id = $_SESSION['user_id'] ?? null;
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : null;
if (!$user_id || !$event_id) {
    header("Location: participant_dashboard.php?error=invalid");
    exit;
}

// Check event exists
$stmt = $pdo->prepare("SELECT event_id FROM events WHERE event_id=?");
$stmt->execute([$event_id]);
if (!$stmt->fetch()) {
    header("Location: participant_dashboard.php?error=notfound");
    exit;
}

// Prevent duplicate participation
$stmt = $pdo->prepare("SELECT * FROM event_participants WHERE user_id=? AND event_id=?");
$stmt->execute([$user_id, $event_id]);
if ($stmt->fetch()) {
    header("Location: participant_dashboard.php?error=alreadyjoined");
    exit;
}

$stmt = $pdo->prepare("INSERT INTO event_participants (user_id, event_id, registered_at) VALUES (?, ?, NOW())");
$stmt->execute([$user_id, $event_id]);

header("Location: participant_dashboard.php?success=joined");
exit;
?>
