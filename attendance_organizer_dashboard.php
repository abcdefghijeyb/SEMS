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
// Restrict to organizer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'organizer') {
    header("Location: access_denied.php");
    exit;
}
$user_id = $_SESSION['user_id'] ?? null;
$user_name = "Unknown";
if ($user_id) {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row) $user_name = $row['name'];
}
$events = $pdo->query("SELECT event_id, title FROM events ORDER BY event_id DESC")->fetchAll();
$selected_event = $_GET['event_id'] ?? ($events ? $events[0]['event_id'] : null);

$attendance_stmt = $pdo->prepare("
    SELECT
        a.attendance_id,
        u.name AS student,
        DATE(a.check_in_time) AS date,
        TIME(a.check_in_time) AS time_in,
        TIME(a.check_out_time) AS time_out,
        e.title AS event
    FROM attendance a
    JOIN users u ON a.user_id = u.user_id
    LEFT JOIN events e ON a.event_id = e.event_id
    WHERE a.event_id = ?
    ORDER BY a.check_in_time DESC
");
$attendance_stmt->execute([$selected_event]);
$attendance = $attendance_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance | School Event Management System</title>
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
            padding: 0 36px; height: 70px; 
            position: sticky; top: 0; 
            z-index: 1000;
        }
        .logo {
            font-size: 1.32em; font-weight: 700; 
            letter-spacing: 0.06em; 
            color: #204076; 
            margin-right: 40px;
        }
        .logo-link {
            color:inherit; 
            text-decoration:none;
        }
        .menu {
            flex: 1; 
            display: flex; 
            justify-content: center; 
            gap: 38px;
        }
        .menu a {
            color: #273659; 
            text-decoration: none; 
            font-weight: 500; 
            font-size: 1.11em; 
            padding: 9px 13px; 
            border-radius: 7px;
        }
        .menu a.active, .menu a:focus, .menu a:active {
            color: #fff; 
            background: #4066b3;
        }
        .menu a:hover:not(.active) {
            color: #3b82f6; 
            background:#e6ebfa;
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
            right:0; top:100%; 
            background:#fff; 
            min-width:150px; 
            box-shadow:0 4px 14px #23448815; 
            border-radius:8px; 
            z-index:10;
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
        }
        .dropdown-content-user a:hover {
            background:#eaeffe;}

        .table-area {
            width:100%; 
            max-width:1100px; 
            margin:36px auto 0 auto; 
            background:#fff; 
            border-radius:14px; 
            box-shadow:0 2px 10px #20407605; 
            padding:24px 22px 30px 22px;
        }
        table {
            width:100%; 
            border-collapse:collapse;
        }
        th, td {
            padding:13px 8px; 
            border-bottom:1px solid #e6ebee; 
            text-align:center;
        }
        th {
            background:#eef4fa; 
            font-weight:500;
        }
        td {
            font-size:1em;
        }
        .no-records {
            text-align:center; 
            color:#888; 
            font-style:italic; 
            padding:18px 5px;
        }
        .total-attendees {
            text-align:right; 
            margin-top:15px; 
            font-size:1.14em; 
            color:#234488; 
            font-weight:600;
        }
        footer {
            background: #22283d; 
            color: #f1f4fa; 
            text-align: center; 
            padding: 24px 0 18px 0; 
            font-size:1.07em; 
            margin-top: auto; 
            font-weight:600;
        }
        @media (max-width:900px) {.navbar{padding:0 9px;} .table-area{padding:13px 2vw 15px 2vw;}}
    </style>
</head>
<body>
    <div class="navbar">
        <a href="organizer_dashboard.php" class="logo-link"><div class="logo">SEMS</div></a>
        <div class="menu">
            <a href="events_organizer_dashboard.php">EVENTS</a>
            <a href="attendance_organizer_dashboard.php" class="active">ATTENDANCE</a>
            <a href="notifications_organizer_dashboard.php">NOTIFICATIONS</a>
            <a href="resources_organizer_dashboard.php">RESOURCES</a>
        </div>
        <div class="user-dropdown">
            <div class="user-name"><?= htmlspecialchars($user_name) ?></div>
            <div class="dropdown-content-user">
                <a href="profile_organizer_dashboard.php">My Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
    <div class="table-area">
        <h2 style="color:#204076; font-size:1.25em; margin-bottom:19px; font-weight:700; text-align:center;">
            Attendance Records
        </h2>
        <form method="get" style="text-align:center;">
            <label for="event_id" style="font-weight:500; color:#234488; margin-right:7px;">Select Event:</label>
            <select name="event_id" id="event_id" style="padding:8px 11px; border-radius:7px; border:1px solid #d2d7e9; font-size:1.08em;" onchange="this.form.submit()">
                <?php foreach($events as $event): ?>
                    <option value="<?= $event['event_id'] ?>" <?= $selected_event==$event['event_id'] ? "selected":"" ?>>
                        <?= htmlspecialchars($event['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <table>
            <thead>
                <tr>
                    <th>STUDENT</th>
                    <th>DATE</th>
                    <th>TIME IN</th>
                    <th>TIME OUT</th>
                    <th>EVENT</th>
                </tr>
            </thead>
            <tbody>
                <?php
if ($attendance && count($attendance) > 0) {
    foreach ($attendance as $row) {
        echo "<tr>
            <td>".htmlspecialchars($row['student'])."</td>
            <td>".$row['date']."</td>
            <td>".$row['time_in']."</td>
            <td>".($row['time_out'] !== null ? $row['time_out'] : "-")."</td>
            <td>".htmlspecialchars($row['event'] ?? "-")."</td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='5' class='no-records'>No attendees for selected event.</td></tr>";
}
?>

            </tbody>
        </table>
        <div class="total-attendees">
            Total Attendees: <?= count($attendance) ?>
        </div>
    </div>
    <footer>
        School Event Management System &copy; <?= date("Y") ?>
    </footer>
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
            var drop = ud.querySelector('.dropdown-content-user');
            if(drop){drop.addEventListener('click', function(e){e.stopPropagation();});}
        }
    });
    </script>
</body>
</html>
