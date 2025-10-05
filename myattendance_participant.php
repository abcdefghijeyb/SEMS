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
$pdo = new PDO($dsn, $user, $pass, $options);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'participant') {
    header("Location: access_denied.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$user_row = $pdo->prepare("SELECT name, email FROM users WHERE user_id=?");
$user_row->execute([$user_id]);
$user_data = $user_row->fetch();
$user_display = $user_data['name'] ?? $user_data['email'] ?? 'User';

$stmt = $pdo->prepare("SELECT e.title, e.start_date, e.end_date, a.checkintime, a.checkouttime
    FROM attendance a JOIN events e ON a.event_id = e.event_id
    WHERE a.user_id = ?
    ORDER BY e.start_date DESC");
$stmt->execute([$user_id]);
$attendances = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attendance | SchoolEMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {background:#f6f8fa;font-family:sans-serif;margin:0;min-height:100vh;}
        .navbar {
            display:flex;align-items:center;justify-content:space-between;
            background:#fff;box-shadow:0 2px 14px #e6eaf3;padding:0 36px;height:70px;position:sticky;top:0;z-index:100;
        }
        .logo-link {color:inherit;text-decoration:none;}
        .logo {font-size:1.32em;font-weight:700;letter-spacing:0.06em;color:#204076;}
        .menu {flex: 1;display: flex;justify-content: center;gap:38px;}
        .menu a {color:#273659;text-decoration:none;font-weight:500;font-size:1.11em;padding:9px 13px;border-radius:7px;}
        .menu a.active, .menu a:focus, .menu a:active {color:#fff;background:#4066b3;}
        .menu a:hover:not(.active) {color:#3b82f6;background:#e6ebfa;}
        .user-dropdown {display:flex;align-items:center;gap:10px;position:relative;}
        .user-name {color: #fff; background:#234488; padding:9px 19px 9px 15px; border-radius:22px; font-weight:600; font-size:1.07em; cursor:pointer;}
        .dropdown-content-user {display:none;position:absolute;right:0;top:120%;background:#fff;min-width:150px;box-shadow:0 4px 14px #23448815;border-radius:8px;z-index:10;}
        .dropdown-content-user a {display:block;padding:12px 20px;color:#204076;text-decoration:none;font-size:1em;}
        .dropdown-content-user a:hover {background:#eaeffe;}
        .table-area {width:100%;max-width:1100px;margin:36px auto 0 auto;background:#fff;border-radius:14px;box-shadow:0 2px 10px #20407605;padding:24px 22px 30px 22px;}
        .attendance-table {width:100%;border-collapse:collapse;}
        .attendance-table th, .attendance-table td {padding:13px 5px;text-align:center;border-bottom:1px solid #e6eaf3;}
        .attendance-table th {background:#f2f6fb;font-weight:600;color:#234488;}
        .attendance-table tr:last-child td {border-bottom: none;}
        .status-chip {border-radius:7px;padding:7px 13px;color:#fff;font-weight:600;}
        .att-in  {background:#2cb978;}
        .att-out {background:#d2ae1a;}
        .att-done {background:#444;background:linear-gradient(90deg,#444,#265);}
        footer {background: #22283d; color: #f1f4fa; text-align: center; padding: 18px 0 12px 0; font-size:1.09em; margin-top: 36px; font-weight:600;}
        .footer-powered {font-size:0.965em; color:#c6d3f2;font-weight:400;}
    </style>
</head>
<body>
<div class="navbar">
    <a href="participant_dashboard.php" class="logo-link"><div class="logo">SchoolEMS</div></a>
    <div class="menu">
        <a href="participant_dashboard.php">EVENTS</a>
        <a href="myattendance_participant.php" class="active">MY ATTENDANCE</a>
        <a href="notifications_participant.php">NOTIFICATIONS</a>
    </div>
    <div class="user-dropdown">
        <div class="user-name"><?= htmlspecialchars($user_display) ?></div>
        <div class="dropdown-content-user">
            <a href="profile_participant_dashboard.php">My Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>
<div class="table-area">
    <h2 style="color:#204076;font-size:1.2em;margin-bottom:19px;font-weight:700;text-align:center;">
        My Attendance
    </h2>
    <table class="attendance-table">
        <tr>
            <th>Event</th>
            <th>Date</th>
            <th>Time IN</th>
            <th>Time OUT</th>
            <th>Status</th>
        </tr>
        <?php if($attendances): foreach($attendances as $a): ?>
        <tr>
            <td><?= htmlspecialchars($a['title']) ?></td>
            <td><?= date('M d, Y', strtotime($a['start_date'])) ?></td>
            <td><?= $a['checkintime'] ? date('g:i A', strtotime($a['checkintime'])) : '-' ?></td>
            <td><?= $a['checkouttime'] ? date('g:i A', strtotime($a['checkouttime'])) : '-' ?></td>
            <td>
                <?php if (!$a['checkintime']) { ?>
                    <span class="status-chip" style="background:#c54;">Absent</span>
                <?php } elseif (!$a['checkouttime']) { ?>
                    <span class="status-chip att-in">In Progress</span>
                <?php } else { ?>
                    <span class="status-chip att-done">Present</span>
                <?php } ?>
            </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5" style="color:#888;">No attendance records found.</td></tr>
        <?php endif; ?>
    </table>
</div>
<footer>
    <div>School Event Management System &copy; <?= date("Y") ?></div>
    <div class="footer-powered">Powered by SEMS Platform | All rights reserved.</div>
</footer>
<script>
document.querySelectorAll('.user-name').forEach(function(un){
    un.addEventListener('click',function(e){
        var d=this.parentElement.querySelector('.dropdown-content-user');
        if(d){ d.style.display=(d.style.display==="block"? "none":"block"); }
        e.stopPropagation();
    });
});
document.body.addEventListener('click',function(){
    document.querySelectorAll('.dropdown-content-user').forEach(function(d){d.style.display='none';});
});
</script>
</body>
</html>
