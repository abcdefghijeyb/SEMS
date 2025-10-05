<?php
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    try {
        // Delete from all related tables FIRST
        $pdo->prepare("DELETE FROM attendance WHERE event_id=?")->execute([$event_id]);
        $pdo->prepare("DELETE FROM event_participants WHERE event_id=?")->execute([$event_id]);
        $pdo->prepare("DELETE FROM financial_records WHERE event_id=?")->execute([$event_id]);
        $pdo->prepare("DELETE FROM notifications WHERE event_id=?")->execute([$event_id]);
        // Now delete from events
        $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
        $success = $stmt->execute([$event_id]);
        echo $success ? "Event deleted successfully." : "Failed to delete event.";
    } catch (Exception $e) {
        echo "Failed to delete event: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>
