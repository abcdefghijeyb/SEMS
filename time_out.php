<?php
session_start();
$host = 'localhost';
$db = 'sems_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
$pdo = new PDO($dsn, $user, $pass, $options);
$user_id = $_SESSION['user_id'] ?? null;
if (isset($_POST['event_id']) && $user_id) {
    $event_id = $_POST['event_id'];
    $pdo->prepare("UPDATE attendance SET check_out_time=NOW() WHERE event_id=? AND user_id=? AND check_in_time IS NOT NULL AND check_out_time IS NULL")
        ->execute([$event_id, $user_id]);
}
header("Location: participant_dashboard.php");
exit;
