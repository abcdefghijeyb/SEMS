<?php
include 'db.php';
if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    echo json_encode($user ?: []);
}
?>
