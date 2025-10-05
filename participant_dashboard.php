<?php
session_start();
$host = 'localhost';
$db   = 'sems_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
date_default_timezone_set('Asia/Manila');
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
$current_time = new DateTime("now", new DateTimeZone("Asia/Manila"));
$events = $pdo->query("SELECT * FROM events WHERE start_date >= CURDATE() ORDER BY start_date ASC")->fetchAll();
$notif_count = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id='$user_id' AND status='unread'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Participant Dashboard | School Event Management System</title>
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
            min-height:100vh;}
        .navbar {
            display:flex;
            align-items:center;
            justify-content:space-between;
            background:#fff;
            box-shadow:0 2px 14px #e6eaf3;
            height:70px;
            padding:0 36px;
            position: relative;
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
            min-width: 75px;
            text-align: center;
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
        .notif-badge {
            color:#fff;
            background:#e53e3e;
            border-radius:9px;
            padding:0 7px;
            position:absolute;
            top:-2px;right:-7px;
            font-size:.68em;
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
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            min-height: calc(90vh - 90px);
            padding: 1.7vw 0 3vw 0;
        }
        .event-card {
            width: 550px; 
            max-width:98vw;
            background:#fff;
            border-radius:20px;
            box-shadow:0 7px 26px #22293c18;
            padding:38px 43px 30px 43px;
            display:flex;
            flex-direction:row;
            align-items:center;
            gap:42px;
            height:390px;
            margin: 0 auto 36px auto;
        }
        .event-info {
            flex:45;
        }
        .event-title {
            font-size:2.25em;
            font-weight:900;
            color:#223368;
            margin-bottom:18px;
        }
        .event-desc {
            font-size:1.18em;
            color:#49557d;
            margin-bottom:16px; 
            line-height: 1.42;
        }
        .event-img-box {
            flex:1;
            display:flex;
            align-items:center;
            justify-content:center;
            flex-direction:column;
        }
        .event-img{
            width:210px;
            max-height:170px;
            object-fit:cover;
            border-radius:13px;
            background:#efefef;
            box-shadow:0 3px 18px #10296010;
        }
        .event-time-meta {
            margin-top:17px;
            font-size:1.13em;
            color:#28353f;
            background:#f2f6fb;
            border-radius:8px;
            padding:5px 13px;
        }
        .participate-btn{
            background:#204076;
            color:#fff;
            padding:14px 41px;
            border:none;
            border-radius:11px;
            font-weight:800;
            font-size:1.16em;
            cursor:pointer;
            transition:.12s;
            margin-top:13px;
            letter-spacing:.03em;
        }
        .participate-btn:hover{
            background:#2d4fc3;
        }
        .status{
            font-weight:600;
            font-size:.99em;
            margin-top:15px;
            display:block;
        }
        .countdown-timer{
            font-size:1.09em;
            font-family:monospace;
            color:#174;
        }
        .empty-msg{
            text-align:center;
            color:#999;
            font-size:1.22em;
            margin:55px auto 15px auto;
        }
        #timeInModal {
            display:none;
            position:fixed;
            top:0;left:0;
            width:100vw;
            height:100vh;
            background:#0009;
            z-index:999;
            align-items:center;
            justify-content:center;
        }
        #timeInModal .modal-inner{
            background:#fff;
            padding:32px 28px 19px 28px;
            border-radius:15px;
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
        @media (max-width:680px){.event-card{width:96vw;max-width:98vw;padding:18px 3vw 18px 3vw;gap:17px;}}
    </style>
</head>
<body>
<div class="navbar">
    <a href="participant_dashboard.php" class="logo-link"><span class="logo">SEMS</span></a>
    <div class="nav-icons">
        <span class="time-now" id="realtime-clock"></span>
        <a href="notifications_participant.php" class="nav-icon" title="Notifications">
            <i class="fa fa-bell"></i>
            <?php if($notif_count): ?>
                <span class="notif-badge"><?= $notif_count ?></span>
            <?php endif; ?>
        </a>
        <a href="profile_participant_dashboard.php" class="profile-box" title="Profile">
            <img class="profile-pic" src="<?= isset($userRow['profile_pic']) && $userRow['profile_pic'] ? 'uploads/'.htmlspecialchars($userRow['profile_pic']) : 'default_avatar.png' ?>" alt="Profile"/>
            <span class="profile-name"><?= htmlspecialchars($user_name) ?></span>
        </a>
        <a href="logout.php" class="nav-icon" title="Logout"><i class="fa fa-sign-out"></i></a>
    </div>
</div>
<div class="main-content">
    <?php if(!$events): ?>
        <div class="empty-msg">No current events to join right now.</div>
    <?php else: ?>
        <?php foreach($events as $event):
            $event_start = new DateTime($event['start_date']);
            $event_end = new DateTime($event['end_date']);
            $q_part = $pdo->prepare("SELECT * FROM event_participants WHERE event_id=? AND user_id=?");
            $q_part->execute([$event['event_id'], $user_id]);
            $is_participating = $q_part->fetch();
            $q_att = $pdo->prepare("SELECT * FROM attendance WHERE event_id=? AND user_id=?");
            $q_att->execute([$event['event_id'], $user_id]);
            $attendance = $q_att->fetch();
        ?>
        <div class="event-card">
            <div class="event-info">
                <div class="event-title"><?= htmlspecialchars($event['title']) ?></div>
                <div class="event-desc"><?= htmlspecialchars($event['description']) ?></div>
                <?php if (!$is_participating): ?>
                    <form method="post" action="participate.php">
                        <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                        <button type="submit" class="participate-btn"><i class="fa fa-check-circle" style="margin-right:8px;"></i> Participate</button>
                    </form>
                <?php else: ?>
                    <?php if ($current_time < $event_start): ?>
                        <div class="countdown-timer" data-start="<?= $event_start->format('Y-m-d H:i:s') ?>">Event starts in ...</div>
                    <?php elseif ($current_time >= $event_start && $current_time < $event_end): ?>
                        <?php if (!$attendance || !$attendance['check_in_time']): ?>
                            <!-- TIME IN MODAL BUTTON -->
                            <button type="button" class="participate-btn" style="background:#18af6b;" onclick="showTimeInModal(<?= $event['event_id'] ?>)"> <i class="fa fa-clock"></i> Time In</button>
                        <?php elseif (!$attendance['check_out_time']): ?>
                            <span class="status">TIME IN : <?= date('g:i A', strtotime($attendance['check_in_time'])) ?></span>
                            <?php
                                $event_end_ts = $event_end->getTimestamp();
                                $now_ts = $current_time->getTimestamp();
                                // Only show Time Out in last 10 min
                                if ($event_end_ts - $now_ts <= 600 && $event_end_ts - $now_ts > 0):
                            ?>
                            <form method="post" action="time_out.php" style="display:inline;margin-left:10px;">
                                <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                <button type="submit" class="participate-btn" style="background:#c89d1b;"><i class="fa fa-clock"></i> Time Out</button>
                            </form>
                            <?php else: ?>
                                <span class="status" style="margin-left:12px;color:#bb2222;">Time Out will be available in the last 10 min of the event</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status" style="color:#234488;">Attendance complete</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="status" style="color:#777;background:#f5f6f7;border-radius:5px;">Event is done</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="event-img-box">
                <?php if($event['image']): ?>
                    <img class="event-img" src="uploads/<?= htmlspecialchars($event['image']) ?>" alt="Event">
                <?php else: ?>
                    <div class="event-img" style="display:flex;align-items:center;justify-content:center;font-size:1.19em;color:#9aa;">No Image</div>
                <?php endif; ?>
                <div class="event-time-meta">
                    <?= date('F d, Y', strtotime($event['start_date'])) ?>
                    <span style="color:#4078dc;">
                    <?= date('g:i A', strtotime($event['start_date'])) ?>
                    </span>
                    -
                    <span style="color:#b67820;">
                    <?= date('g:i A', strtotime($event['end_date'])) ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<!-- Time In Modal -->
<div id="timeInModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:#0009;z-index:999;align-items:center;justify-content:center;">
    <div class="modal-inner">
        <form id="timeInForm" method="post">
            <input type="hidden" id="modal_event_id" name="event_id">
            <div style="margin-bottom:23px;">Are you sure you want to time in?</div>
            <button type="submit" class="participate-btn" style="background:#18af6b;width:100%;">Yes, Time In</button>
            <button type="button" onclick="hideTimeInModal()" style="margin-left:6px;margin-top:12px;width:100%;background:#ddd;color:#222;" class="participate-btn">Cancel</button>
        </form>
    </div>
</div>
<footer>
    School Event Management System &copy; <?= date("Y") ?>
</footer>
<script>
function updateTime() {
    const offset = 8 * 60;
    const now = new Date();
    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
    const manilaNow = new Date(utc + (offset * 60000));
    let hour = manilaNow.getHours();
    let minute = manilaNow.getMinutes();
    let ampm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12; hour = hour ? hour : 12;
    minute = minute < 10 ? '0'+minute : minute;
    let timeStr = hour + ':' + minute + ' ' + ampm;
    document.getElementById('realtime-clock').textContent = timeStr;
}
updateTime(); setInterval(updateTime, 1000);

document.querySelectorAll('.countdown-timer').forEach(function(el) {
    var start = new Date(el.dataset.start.replace(/-/g, '/')).getTime();
    function update() {
        var now = new Date().getTime();
        var dist = start - now;
        if (dist < 0) { el.textContent = "Starting soon..."; return; }
        var d = Math.floor(dist / (1000*60*60*24));
        var h = Math.floor((dist % (1000*60*60*24)) / (1000*60*60));
        var m = Math.floor((dist % (1000*60*60)) / (1000*60));
        var s = Math.floor((dist % (1000*60)) / 1000);
        el.textContent = (d? d+"d ":"")+(h? h+"h ":"")+(m? m+"m ":"")+s+"s";
    }
    update();
    setInterval(update, 1000);
});

function showTimeInModal(eid){
    document.getElementById('modal_event_id').value = eid;
    document.getElementById('timeInModal').style.display = 'flex';
}
function hideTimeInModal(){
    document.getElementById('timeInModal').style.display = 'none';
}
document.getElementById('timeInForm').onsubmit = async function(e){
    e.preventDefault();
    let eid = document.getElementById('modal_event_id').value;
    await fetch('time_in.php', {
        method:"POST",
        body: new URLSearchParams({event_id:eid})
    });
    window.location.reload();
};
</script>
</body>
</html>
