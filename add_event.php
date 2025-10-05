<?php
include 'db.php';
session_start();
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$venue = $_POST['venue'] ?? '';
$created_by = $_SESSION['user_id'] ?? 0;
$image_name = "";
if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
    $image_name = uniqid('event_',true) . '_' . basename($_FILES['image']['name']);
    move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image_name);
}
$start = $start_date . ' ' . $start_time;
$end   = $end_date . ' ' . $end_time;
$stmt = $pdo->prepare("INSERT INTO events (title, description, start_date, end_date, venue, created_by, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
$ok = $stmt->execute([$title, $description, $start, $end, $venue, $created_by, $image_name]);
echo $ok ? "Event added successfully!" : "Failed to add event.";
?>
