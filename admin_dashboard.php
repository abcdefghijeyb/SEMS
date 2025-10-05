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
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: access_denied.php"); 
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$userRow = ["name" => "Unknown"];
if ($user_id) {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row) $userRow['name'] = $row['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | School Event Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
    <style>
        html, body {
            margin:0; 
            padding:0; 
            min-height:100vh; 
            background:#f8fafc; 
            font-family:'Roboto', Arial, sans-serif;
        }
        body {
            display: flex; 
            flex-direction: column; 
            min-height: 100vh;
        }
        .navbar {
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            background: #fff; 
            box-shadow: 0 2px 14px #e6eaf3;
            padding: 0 36px; 
            height: 70px; 
            position: sticky; 
            top: 0; 
            z-index: 1000;
        }
        .logo {
            font-size: 1.32em; 
            font-weight: 700; 
            letter-spacing: 0.06em; 
            color: #204076;
            margin-right: 40px;
        }
        .menu {
            flex: 1; 
            display: flex; 
            justify-content: center; 
            gap: 38px; 
            position:relative;
        }
        .menu a, .menu button {
            color: #273659; 
            text-decoration: none; 
            font-weight: 500; 
            font-size: 1.11em;
            padding: 9px 13px; 
            border-radius: 7px; 
            transition: all 0.14s;
            letter-spacing:.02em; 
            position:relative; 
            background:none; 
            border:none; 
            cursor:pointer; 
            display:inline-block;
        }
        .menu a:hover, .menu button:hover {
            color:#3b82f6; 
            background:#edebfa;
        }
        .menu .dropdown {
            position:relative;
        }
        .menu .dropdown-content {
            display:none; 
            position:absolute; 
            top:43px; 
            left:50%; 
            transform:translateX(-50%);
            background:#fff; 
            min-width:170px; 
            box-shadow:0 4px 14px #23448815; 
            border-radius:8px; 
            z-index:20;
        }
        .menu .dropdown:hover .dropdown-content,
        .menu .dropdown:focus-within .dropdown-content {
            display:block;
        }
        .menu .dropdown-content a {
            display:block; 
            padding:11px 18px; 
            color:#204076;
            text-decoration:none; 
            font-size:1em; 
            transition:background 0.15s;
        }
        .menu .dropdown-content a:hover {
            background:#eaeffe;
        }
        .user-dropdown {
            display:flex; 
            align-items:center; 
            gap:10px; 
            position:relative;
        }
        .user-name {
            color: #fff; 
            background:#234488; 
            padding:9px 19px 9px 15px;
             border-radius:22px;
            font-weight:600; 
            font-size:1.07em; 
            letter-spacing:.05em;
            box-shadow:0 2px 12px #20407626; 
            cursor:pointer;
        }
        .dropdown-content-user {
            display:none; 
            position:absolute; 
            right:0; top:120%; 
            background:#fff; 
            min-width:150px;
            box-shadow:0 4px 14px #23448815; 
            border-radius:8px; 
            z-index:10; 
            overflow:hidden;
        }
        .user-dropdown.open .dropdown-content-user {
            display:block;
        }
        .dropdown-content-user a {
            display:block; 
            padding:12px 20px; 
            color:#204076; 
            text-decoration:none; 
            font-size:1em; 
            transition:background 0.15s;
        }
        .dropdown-content-user a:hover {
            background:#eaeffe;
        }
        .main-content {
            display: flex; 
            flex-direction: row; 
            align-items: center; 
            justify-content: center;
            min-height: calc(100vh - 140px); /* account for navbar+footer */
            margin-top: 25px; flex: 1;
        }
        .welcome-area {
            margin-right:48px; 
            text-align:left;
        }
        .welcome-area h1 {
            font-size:2.2em; 
            color:#204076; 
            font-weight:700; 
            line-height:1.13; 
            margin-bottom:16px;
        }
        .welcome-area p {
            color: #4066b3; 
            font-size: 1.19em; 
            font-weight: 500; 
            margin-bottom: 10px;
        }
        .img-area {
            width: 320px; 
            height: 210px; 
            background:#eaf0fa; 
            border-radius:18px;
            display:flex; 
            align-items:center; 
            justify-content:center;
            box-shadow:0 6px 20px #a2bbdb22;
        }
        .img-area img {
            width:115px; 
            height:115px; 
            object-fit:contain; 
            opacity:.58;
        }
        footer {
            background: #22283d;
            color: #f1f4fa;
            text-align: center;
            padding: 24px 0 18px 0;
            font-size:1.07em;
            letter-spacing:.01em;
            margin-top: auto;
            font-weight:600;
        }
        @media (max-width:900px) {
            .navbar{padding:0 9px;}
            .main-content {flex-direction:column;}
            .welcome-area{margin-right:0;margin-bottom:40px;}
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function(){
            var ud = document.querySelector('.user-dropdown');
            var uname = document.querySelector('.user-name');
            if(uname && ud){
                uname.addEventListener('click', function(e){
                    ud.classList.toggle('open');
                    e.stopPropagation();
                });
                document.body.addEventListener('click', function(){
                    ud.classList.remove('open');
                });
                ud.querySelector('.dropdown-content-user').addEventListener('click', function(e){
                    e.stopPropagation();
                });
            }
        });
    </script>
</head>
<body>
    <div class="navbar">
        <div class="logo">SEMS</div>
        <div class="menu">
            <a href="students_admin_dashboard.php">USERS</a>
            <a href="events_admin_dashboard.php">EVENTS</a>
            <a href="attendance_admin_dashboard.php">ATTENDANCE</a>
            <a href="notifications_admin_dashboard.php">NOTIFICATIONS</a>
            <a href="resources_admin_dashboard.php">RESOURCES</a>
            <a href="financialrecords_admin_dashboard.php">FINANCIAL RECORDS</a>
        </div>
        <div class="user-dropdown">
            <div class="user-name"><?php echo htmlspecialchars($userRow['name']); ?></div>
            <div class="dropdown-content-user">
                <a href="profile_admin_dashboard.php">My Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
    <div class="main-content">
        <div class="welcome-area">
            <h1>WELCOME TO<br>SCHOOL EVENT<br>MANAGEMENT SYSTEM</h1>
            <p>
                Efficiently manage students, events, attendance, notifications, resources, and finances.
            </p>
        </div>
        <div class="img-area">
            <img src="https://img.icons8.com/ios/100/4071f4/organization.png" alt="Admin Dashboard" />
        </div>
    </div>
    <footer>
        School Event Management System &copy; <?php echo date("Y"); ?>
    </footer>
</body>
</html>
