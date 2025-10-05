<?php
include 'db.php'; 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $success = $stmt->execute([$user_id]);
    echo $success ? "User deleted successfully." : "Failed to delete user.";
} else {
    echo "Invalid request.";
}
?>
