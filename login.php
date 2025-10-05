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

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = strtolower($user['role']);

        // Redirect based on user role
        if ($_SESSION['role'] === 'admin') {
            header('Location: admin_dashboard.php');
            exit();
        } elseif ($_SESSION['role'] === 'organizer') {
            header('Location: organizer_dashboard.php');
            exit();
        } elseif ($_SESSION['role'] === 'participant') {
            header('Location: participant_dashboard.php');
            exit();
        } else {
            header('Location: dashboard.php'); // fallback
            exit();
        }
    } else {
        $login_error = 'Invalid email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Event Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
    <style>
        html, body {
            margin:0; 
            padding:0; 
            height:100%; 
            width:100%; 
            box-sizing:border-box; 
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
        .login-card {
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
        .login-card h2 {
            font-size:1.32em; 
            font-weight:500; 
            color:#234488; 
            margin-bottom:26px; 
            letter-spacing:0.06em;
        }
        .login-card input {
            width:100%; 
            padding:12px 14px; 
            margin-bottom:18px; 
            font-size:1.05em; 
            border-radius:6px; 
            border:1px solid #c6daf6; 
            background:#f8fbfe; 
            box-sizing:border-box; 
            outline:none; 
            transition:border-color 0.2s;
        }
        .login-card input:focus {
            border-color:#234488; 
            background:#eef4fa;
        }
        .login-card button {
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
        .login-card button:hover {
            background:linear-gradient(90deg, #4286f5 20%, #234488 100%); 
            transform:scale(1.02);
        }
        .timestamp {
            text-align:center; 
            font-size:1em; 
            color:#234488; 
            font-weight:500; 
            margin-top:18px; 
            letter-spacing:0.02em; 
            background:#f4f8fc; 
            padding:7px 0; 
            border-radius:4px;}
        .error-message {
            color:#c0392b; 
            margin-bottom:14px; 
            font-size:1em;
        }
        .create-account-link {
            text-align:center; 
            margin-top:18px; 
            font-size:1em; 
            color:#183355; 
            font-weight:500; 
            letter-spacing:0.03em;
        }
        .create-account-link a {
            color:#4286f5; 
            text-decoration:none; 
            font-weight:700; 
            transition:color 0.2s;
        }
        .create-account-link a:hover {
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
        <div>
            <form class="login-card" autocomplete="off" action="" method="POST">
                <h2>Please Login</h2>
                <?php
                if (!empty($login_error)) {
                    echo '<div class="error-message">' . $login_error . '</div>';
                }
                ?>
                <input type="email" name="email" placeholder="Email" autocomplete="username" required>
                <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
                <button type="submit">LOG IN</button>
                <div class="timestamp" id="timestamp"></div>
            </form>
            <div class="create-account-link">
                Don't have an account? <a href="register.php">Create one</a>
            </div>
        </div>
    </div>
    <script>
        function updateTimestamp() {
            const options = {
                timeZone: 'Asia/Manila',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const now = new Date();
            const dateTime = now.toLocaleString('en-US', options);
            const parts = dateTime.split(", ");
            document.getElementById("timestamp").textContent =
                `${parts[1]} | ${parts[0].toUpperCase()}, ${parts[2].toUpperCase()}`;
        }
        setInterval(updateTimestamp, 1000);
        updateTimestamp();
    </script>
</body>
</html>
