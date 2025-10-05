<?php
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $department = $_POST['department'] ?? '';
    $role = $_POST['role'] ?? '';
    $stmt = $pdo->prepare('UPDATE users SET name=?, email=?, department=?, role=? WHERE user_id=?');
    $success = $stmt->execute([$name, $email, $department, $role, $user_id]);
    echo $success ? "User updated successfully" : "Failed to update user";
}
?>
