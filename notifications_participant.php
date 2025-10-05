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
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'participant') {
    header("Location: access_denied.php");
    exit;
}
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? '';
$userRow = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $userRow = $stmt->fetch();
}
// Fetch notifications (latest 30)
$stmt = $pdo->prepare("SELECT n.*, e.title AS event_title FROM notifications n LEFT JOIN events e ON n.event_id = e.event_id WHERE n.user_id=? ORDER BY n.date_sent DESC LIMIT 30");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();
// Mark all as read
$pdo->prepare("UPDATE notifications SET status='read' WHERE user_id=?")->execute([$user_id]);
$current_time = date('g:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications | School Event Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        html, body {
            margin:0; 
            padding:0;
             background:#f3f6fb; 
             min-height:100vh; 
             font-family:'Roboto', Arial, sans-serif;
            }
        body {
            display: flex;
             flex-direction: column; 
             min-height:100vh;
            }
        .navbar {
            display:flex;
            align-items:center;
            justify-content:space-between;
            background:#fff;
            box-shadow:0 2px 14px #e6eaf3;
            height:70px;
            padding:0 36px;
        }
        .logo-link {
            color: inherit; 
            text-decoration: none;
        }
        .logo {
            font-size:1.6em;
            font-weight:900;
            letter-spacing:0.08em;
            color:#234488;
        }
        .nav-icons {
            display: flex; 
            align-items:center; 
            gap:28px;
        }
        .time-now {
            font-size:1.11em;
            font-weight:600;
            color:#4264be;
            margin-right:11px;
            letter-spacing:.02em;
        }
        .nav-icon {
            font-size:1.45em; 
            color:#204076; 
            padding:8px; 
            transition:.1s; 
            position:relative;
        }
        .nav-icon:hover{
            color:#4066b3;
            background:#eef4fa;
            border-radius:50%;
        }
        .profile-box {
            display:inline-flex;
            align-items:center;
            gap:9px;
        }
        .profile-pic {
            width:39px;
            height:39px;
            border-radius:50%;
            object-fit:cover;
            margin-left:0;
            border:2px solid #e6eaf3;
        }
        .profile-name {
            font-size:1em;
            font-weight:600;
            color:#273659;
        }
        .main-content {
            max-width: 780px; 
            margin:40px auto 30px auto;
            width:96vw;background:#fff;
            padding:40px 40px 30px 40px;
            border-radius:17px;
            box-shadow:0 3px 22px #0d186a19;
        }
        .section-title {
            font-size:1.6em;
            color:#204076;
            font-weight:700;
            margin:0 0 25px 0;
            letter-spacing:.03em;
        }
        .notif-list {
            margin:0; 
            padding:0;
            list-style:none;
        }
        .notif-card {
            padding:23px 20px 18px 20px;
            border-bottom:1px solid #ebf0fa;
        }
        .notif-card:last-child {border-bottom: none;}
        .notif-event {
            font-weight:700;
            color:#247ED3;
            letter-spacing:.02em;
        }
        .notif-message {
            font-size:1.11em;
            margin-top:2px; 
            color:#182052;
        }
        .notif-date {
            color:#6b7a93;
            margin-top:6px;
            font-size:.98em;
        }
        .empty-notif {
            text-align:center;
            color:#a8a8a8;
            font-size:1.21em;
            margin:38px auto 20px auto;
        }
        footer {
            background: #20284a; 
            color: #f1f4fa; 
            text-align: center; 
            padding: 22px 0 14px 0; 
            font-size:1.09em; 
            margin-top: auto; 
            font-weight:600;
        }
        @media (max-width:600px){.main-content{padding:13px 4vw 20px 4vw;}
    }
    </style>
</head>
<body>
<div class="navbar">
    <a href="participant_dashboard.php" class="logo-link"><span class="logo">SEMS</span></a>
    <div class="nav-icons">
        <span class="time-now"><?= $current_time ?></span>
        <a href="notifications_participant.php" class="nav-icon" title="Notifications">
            <i class="fa fa-bell"></i>
        </a>
        <a href="profile_participant_dashboard.php" class="profile-box" title="Profile">
            <img class="profile-pic" src="<?= isset($userRow['profile_pic']) && $userRow['profile_pic'] ? 'uploads/'.htmlspecialchars($userRow['profile_pic']) : 'default_avatar.png' ?>" alt="Profile"/>
            <span class="profile-name"><?= htmlspecialchars($user_name) ?></span>
        </a>
        <a href="logout.php" class="nav-icon" title="Logout"><i class="fa fa-sign-out"></i></a>
    </div>
</div>
<div class="main-content">
    <div class="section-title"><i class="fa fa-bell"></i> Notifications</div>
    <?php if(!$notifications): ?>
        <div class="empty-notif">You have no notifications yet.</div>
    <?php else: ?>
    <ul class="notif-list">
    <?php foreach($notifications as $notif): ?>
        <li class="notif-card">
            <?php if($notif['event_title']): ?>
                <span class="notif-event"><?= htmlspecialchars($notif['event_title']) ?></span>
            <?php endif; ?>
            <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
            <script>
                // Real-time clock for the navbar
                function updateTime() {
                    const now = new Date();
                    let hours = now.getHours();
                    const minutes = now.getMinutes();
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    hours = hours % 12;
                    hours = hours ? hours : 12; // 0 should be 12
                    const mins = minutes < 10 ? '0' + minutes : minutes;
                    const timeString = hours + ':' + mins + ' ' + ampm;
                    document.querySelectorAll('.time-now').forEach(el => el.textContent = timeString);
                }
                setInterval(updateTime, 1000);
                updateTime();
            </script>
            <div class="notif-date"><i class="fa fa-clock"></i> <?= date('F d, Y g:i A', strtotime($notif['date_sent'])) ?></div>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<footer>
    School Event Management System &copy; <?= date("Y") ?>
</footer>
</body>
</html>
