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

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Email already registered.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $password_hash, $role])) {
                $success = true;
                // Get new user_id from database and set in session
                $new_user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $new_user_id;
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account | School Event Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
    <style>
        html, body {
            margin:0; 
            padding:0; 
            min-height:100vh; 
            width:100%; 
            font-family:'Roboto', Arial, sans-serif; 
            background:linear-gradient(135deg, #eef4fa 0%, #e2ebf6 100%);
        }
        .main {
            min-height:100vh; 
            width:100vw; 
            display:flex; 
            flex-direction:row; 
            align-items:center; 
            justify-content:center; 
            gap:8vw;
        }
        .title-area {
            display:flex; 
            flex-direction:column; 
            align-items:flex-start;
        }
        .sys-title {
            font-size:2.7em; 
            font-weight:700; 
            letter-spacing:0.03em; 
            color:#234488; 
            margin-bottom:18px; 
            line-height:1.15;
        }
        .sys-sub {
            font-size:1.1em; 
            font-weight:500; 
            color:#204076; 
            letter-spacing:0.10em; 
            text-transform:uppercase; 
            opacity:.72;
        }
        .register-card {
            background:#fff; 
            border-radius:18px; 
            box-shadow:0 10px 32px rgba(34,68,136,0.09),0 1.5px 8px rgba(0,0,0,0.08); 
            padding:42px 52px 28px 52px; 
            width:340px; 
            display:flex; 
            flex-direction:column; 
            align-items:center; 
            border:1px solid #edf2f8;
        }
        .register-card h2 {
            font-size:1.32em; 
            font-weight:500; 
            color:#234488; 
            margin-bottom:26px; 
            letter-spacing:0.06em;
        }
        .register-card input, .register-card select {
            width:100%; padding:12px 14px; 
            margin-bottom:18px; 
            font-size:1.05em; 
            border-radius:6px; 
            border:1px solid #c6daf6; 
            background:#f8fbfe; 
            box-sizing:border-box; 
            outline:none; 
            transition:border-color 0.2s;
        }
        .register-card input:focus, .register-card select:focus {
            border-color:#234488; 
            background:#eef4fa;
        }
        .register-card button {
            width:100%; 
            padding:13px 0; 
            background:linear-gradient(90deg, #234488 60%, #4286f5 100%); 
            color:#fff; 
            font-size:1.09em; 
            font-weight:600; 
            border:none; 
            border-radius:6px; 
            letter-spacing:0.07em; 
            box-shadow:0 2px 12px #21418044; 
            margin-bottom:15px; 
            cursor:pointer; 
            transition:background 0.18s, transform 0.1s;
        }
        .register-card button:hover {
            background:linear-gradient(90deg, #4286f5 20%, #234488 100%); 
            transform:scale(1.02);
        }
        .error-message {
            color:#c0392b; 
            margin-bottom:14px; 
            font-size:1em;
        }
        .success-message {
            color:#28a745; 
            margin-bottom:14px; font-size:1em;
        }
        .back-to-login {
            text-align:center; 
            margin-top:18px; 
            font-size:1em; 
            color:#183355; font-weight:500; 
            letter-spacing:0.03em;
        }
        .back-to-login a {
            color:#4286f5; 
            text-decoration:none; 
            font-weight:700; 
            transition:color 0.2s;
        }
        .back-to-login a:hover {
            color:#234488; 
            text-decoration:underline;
        }
        @media (max-width:900px) {.main{flex-direction:column; gap:3vh; padding-top:12vh;} .title-area{align-items:center;}}
    </style>
</head>
<body>
<div class="main">
    <div class="title-area">
        <div class="sys-title">School Event<br>Management System</div>
        <div class="sys-sub">Organize, Manage, Succeed</div>
    </div>
    <form class="register-card" autocomplete="off" action="" method="POST">
        <h2>Create Account</h2>
        <?php
        if (!empty($errors)) echo '<div class="error-message">'.implode('<br>', $errors).'</div>';
        if ($success) echo '<div class="success-message">Account created successfully! <a href="login.php">Login here</a></div>';
        ?>
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Create Password" required>
        <select name="role" required>
            <option value="" disabled selected>Select Role</option>
            <option value="admin">Admin</option>
            <option value="organizer">Organizer</option>
            <option value="participant">Participant</option>
        </select>
        <button type="submit">CREATE ACCOUNT</button>
        <div class="back-to-login">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </form>
</div>
</body>
</html>
