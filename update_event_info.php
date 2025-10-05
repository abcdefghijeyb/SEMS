<?php
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id']);
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $venue = $_POST['venue'] ?? '';
    $start = $start_date . ' ' . $start_time;
    $end = $end_date . ' ' . $end_time;
    $stmt = $pdo->prepare("SELECT image FROM events WHERE event_id=?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    $image_name = $event['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK && $_FILES['image']['size']>0) {
        $image_name = uniqid('event_',true) . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image_name);
    }
    $stmt = $pdo->prepare('UPDATE events SET title=?, description=?, start_date=?, end_date=?, venue=?, image=? WHERE event_id=?');
    $success = $stmt->execute([$title, $description, $start, $end, $venue, $image_name, $event_id]);
    echo $success ? "Event updated successfully" : "Failed to update event";
}
?>
