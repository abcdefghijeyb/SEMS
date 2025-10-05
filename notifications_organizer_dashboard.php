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
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'organizer') {
    header("Location: access_denied.php"); // Or redirect to appropriate page
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
if (isset($_POST['sendNotif'])) {
    $event_id = $_POST['event_id'];
    $message = $_POST['message'];
    if ($event_id && $message) {
        $participants = $pdo->prepare("SELECT user_id FROM event_participants WHERE event_id=?");
        $participants->execute([$event_id]);
        $count = 0;
        foreach ($participants as $p) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, event_id, message, date_sent, status) VALUES (?, ?, ?, NOW(), 'unread')");
            $stmt->execute([$p['user_id'], $event_id, $message]);
            $count++;
        }
        $notifResult = $count ? "Notification sent to $count participant(s)!" : "No participants for this event!";
    } else {
        $notifResult = "All fields required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Notifications | School Event Management System</title>
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
            transition: all 0.14s;
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
            background:#eaeffe;
        }
        .table-area {
            width:100%; 
            max-width:600px; 
            margin:36px auto 0 auto; 
            background:#fff; 
            border-radius:14px; 
            box-shadow:0 2px 10px #20407605; 
            padding:40px 32px 40px 32px;
        }
        .heading {
            font-size:1.25em; 
            font-weight:700; 
            margin-bottom:27px; 
            color:#204076; 
            text-align:center;
        }
        .form-field {
            margin-bottom:22px;
        }
        label {
            font-weight:500; 
            color:#234488; 
            margin-bottom:5px; 
            
            display:block;}
        select, textarea {
            padding:10px 11px; 
            font-size:1.08em; 
            border-radius:7px; 
            border:1px solid #d2d7e9;
            width:100%; 
            box-sizing:border-box;
        }
        textarea {
            resize:vertical;
        }
        .send-btn {
            margin-top:6px; 
            padding:10px 32px; 
            background:#4066b3;
             color:#fff; 
             font-size:1em; 
             border:none; 
             border-radius:8px;
              font-weight:700; 
              cursor:pointer;
            }
        .send-btn:hover {
            background:#234488;
        }
        .notif-result {
            color:#094; 
            text-align:center; 
            margin-top:18px;
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
        @media (max-width:600px) {.table-area{padding:20px 6vw 30px 6vw;}}
    </style>
</head>
<body>
<div class="navbar">
    <a href="organizer_dashboard.php" class="logo-link"><div class="logo">SEMS</div></a>
    <div class="menu">
        <a href="events_organizer_dashboard.php">EVENTS</a>
        <a href="attendance_organizer_dashboard.php">ATTENDANCE</a>
        <a href="notifications_organizer_dashboard.php" class="active">NOTIFICATIONS</a>
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
    <div class="heading">Send Notification to Participants</div>
    <form method="post" autocomplete="off" style="max-width:480px;margin:18px auto 0 auto;">
        <div class="form-field">
            <label>Event:</label>
            <select name="event_id" required>
                <option value="">-- Select event --</option>
                <?php
                $event_opts = $pdo->query("SELECT event_id, title FROM events ORDER BY event_id DESC");
                foreach ($event_opts as $ev) {
                    echo '<option value="'.$ev['event_id'].'">'.htmlspecialchars($ev['title']).'</option>';
                }
                ?>
            </select>
        </div>
        <div class="form-field">
            <label>Message:</label>
            <textarea name="message" rows="4" required></textarea>
        </div>
        <button type="submit" name="sendNotif" class="send-btn">Send</button>
        <?php if(isset($notifResult)) echo '<div class="notif-result">'.$notifResult.'</div>'; ?>
    </form>
</div>
<footer>
    School Event Management System &copy; <?= date("Y") ?>
</footer>
<script>
document.addEventListener("DOMContentLoaded",function(){
    var ud = document.querySelector('.user-dropdown');
    var uname = document.querySelector('.user-name');
    if(uname&&ud){
        uname.addEventListener('click',function(e){
            ud.classList.toggle('open');e.stopPropagation();
        });
        document.body.addEventListener('click',function(){
            ud.classList.remove('open');
        });
        var drop=ud.querySelector('.dropdown-content-user');
        if(drop){drop.addEventListener('click',function(e){e.stopPropagation();});}
    }
});
</script>
</body>
</html>
