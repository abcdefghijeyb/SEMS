<?php
include 'db.php';
if (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);
    $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    echo json_encode($event ?: []);
}
?>
