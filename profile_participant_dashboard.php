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
$userRow = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $userRow = $stmt->fetch();
}
$editMode = isset($_GET['edit']);
$pass_error = $pass_success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateProfile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'] ?? null;
    $birthday = $_POST['birthday'] ?? null;
    $department = $_POST['department'] ?? null;
    $profile_pic = $userRow['profile_pic'] ?? null;
    if (!empty($_FILES['profile_pic']['name'])) {
        $picname = 'profile_' . $userRow['user_id'] . '_' . time() . '.' . pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'uploads/'.$picname);
        $profile_pic = $picname;
    }
    // Password logic
    if (!empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
        if (empty($_POST['current_password'])) {
            $pass_error = "Enter your current password!";
        } elseif (!password_verify($_POST['current_password'], $userRow['password_hash'])) {
            $pass_error = "Current password is incorrect.";
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $pass_error = "New passwords do not match.";
        } elseif (strlen($_POST['new_password']) < 6) {
            $pass_error = "New password must be at least 6 characters.";
        } else {
            $newPasswordHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?")->execute([$newPasswordHash, $userRow['user_id']]);
            $pass_success = "Password updated successfully!";
        }
    }
    // Only update basic profile if not changing password or password is ok
    if(!$pass_error && (empty($_POST['new_password']) && empty($_POST['confirm_password']))) {
        $update = $pdo->prepare("UPDATE users SET name=?, email=?, gender=?, birthday=?, department=?, profile_pic=? WHERE user_id=?");
        $update->execute([
            $name, $email, $gender, $birthday, $department, $profile_pic, $userRow['user_id']
        ]);
        header("Location: profile_participant_dashboard.php");
        exit();
    }
}
$notif_count = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id='$user_id' AND status='unread'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | School Event Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        html, body {margin:0; padding:0; background:#f6f8fa; min-height:100vh; font-family:'Roboto', Arial, sans-serif;}
        body {display: flex; flex-direction: column; min-height: 100vh;}
        .navbar {display: flex; align-items: center; justify-content: space-between; background: #fff; box-shadow: 0 2px 14px #e6eaf3; height: 70px; padding: 0 36px;}
        .logo {font-size: 1.32em; font-weight: 700; letter-spacing: 0.06em; color: #204076;}
        .nav-icons {display: flex; align-items:center; gap:22px;}
        .nav-icon {font-size:1.42em;color:#204076;padding:7px;transition:.1s;position:relative;}
        .nav-icon:hover{color:#4066b3;background:#eef4fa;border-radius:50%;}
        .notif-badge {color:#fff;background:#e53e3e;border-radius:9px;padding:0 7px;position:absolute;top:-2px;right:-7px;font-size:.68em;}
        .profile-box {display:inline-flex;align-items:center;gap:8px;}
        .profile-pic {width:37px;height:37px;border-radius:50%;object-fit:cover;margin-left:0;border:2px solid #eee;}
        .time-now {font-size:1.18em;font-weight:600;color:#4264be;margin-right:7px;letter-spacing:.03em;min-width: 75px;}
        .profile-area {max-width:600px; margin:56px auto 0 auto; padding:0 2vw;}
        .profile-card {background:#fff; border-radius:18px; box-shadow:0 8px 40px #1820522a; padding:48px 56px 36px 56px;}
        .profile-img-box{text-align:center;margin-bottom:24px;}
        .profile-img{width:126px;height:126px;object-fit:cover;border-radius:50%;box-shadow:0 4px 24px #4066b350;border:5px solid #eaf2fc;}
        .profile-heading {font-size:1.38em; font-weight:800; margin-bottom:32px; color:#204076; text-align:center;}
        .profile-info {display:flex; flex-direction:column; gap:17px; margin-bottom:12px;}
        .profile-row {display:flex; align-items:center;}
        .icon-box {width:33px;text-align:center;opacity:.68;color:#4670bb;}
        .plabel {font-weight:600; color:#355; letter-spacing:0.04em; margin-bottom:3px;}
        .p-field {flex:1;background:#fbfbfb; border-radius:7px; padding:10px 20px; font-size:1.13em; color:#2a3550; border:1px solid #e4e7ef;}
        .edit-row {margin-bottom:17px;}
        .input-edit {width:100%;padding:11px 13px;font-size:1.10em;border-radius:7px;border:1px solid #d2d7e9;}
        .profile-actions {text-align:center; margin-top:18px;}
        .edit-btn,.save-btn {padding:10px 40px; background:#4066b3; color:#fff; border:none; border-radius:9px; font-weight:700; font-size:1.08em; cursor:pointer;}
        .edit-btn:hover, .save-btn:hover {background:#234488;}
        .cancel-btn{padding:10px 28px;background:#bbb;color:#fff;border:none;border-radius:9px;font-weight:600;font-size:1.07em;cursor:pointer;margin-left:14px;}
        .cancel-btn:hover{background:#555;}
        .divider{height:2px;background:#f0f3fb;border:none;margin:21px 0 18px 0;width:100%;border-radius:2px;}
        .highlight{color:#234488;font-weight:700;}
        .profile-img-box input[type=file]{margin-top:9px;}
        @media (max-width:800px) {.profile-card{padding:2vw 3vw;}}
        footer {background: #20284a; color: #f1f4fa; text-align: center; padding: 24px 0 18px 0; font-size:1.08em; margin-top: auto; font-weight:600;}
    </style>
</head>
<body>
<div class="navbar">
    <a href="participant_dashboard.php" class="logo-link"><span class="logo">SEMS</span></a>
    <div class="nav-icons">
        <span class="time-now" id="realtime-clock"></span>
        <a href="notifications_participant.php" class="nav-icon" title="Notifications"><i class="fa fa-bell"></i><?php if($notif_count): ?><span class="notif-badge"><?= $notif_count ?></span><?php endif; ?></a>
        <a href="profile_participant_dashboard.php" class="profile-box" title="Profile">
            <img class="profile-pic" src="<?= isset($userRow['profile_pic']) && $userRow['profile_pic'] ? 'uploads/'.htmlspecialchars($userRow['profile_pic']) : 'default_avatar.png' ?>" alt="Profile"/>
            <span style="font-size:1em;font-weight:600;color:#273659;"><?= htmlspecialchars($userRow['name'] ?? '') ?></span>
        </a>
        <a href="logout.php" class="nav-icon" title="Logout"><i class="fa fa-sign-out"></i></a>
    </div>
</div>
<div class="profile-area">
    <div class="profile-card">
        <div class="profile-heading"><i class="fa fa-id-badge" style="margin-right:6px"></i>My Profile</div>
        <?php if(!$userRow): ?>
            <div style="text-align:center;color:#c44;font-size:1.1em;">User not found.</div>
        <?php elseif(!$editMode): ?>
            <div class="profile-img-box">
             <img class="profile-img" src="<?= $userRow['profile_pic'] ? 'uploads/'.htmlspecialchars($userRow['profile_pic']) : 'default_avatar.png' ?>" alt="Profile">
            </div>
            <div class="profile-info">
                <div class="profile-row">
                    <div class="icon-box"><i class="fa fa-user"></i></div>
                    <div>
                        <div class="plabel">Full Name</div>
                        <div class="p-field"><?= htmlspecialchars($userRow['name']) ?></div>
                    </div>
                </div>
                <div class="profile-row">
                    <div class="icon-box"><i class="fa fa-envelope"></i></div>
                    <div>
                        <div class="plabel">Email Address</div>
                        <div class="p-field"><?= htmlspecialchars($userRow['email']) ?></div>
                    </div>
                </div>
                <div class="profile-row">
                    <div class="icon-box"><i class="fa fa-venus-mars"></i></div>
                    <div>
                        <div class="plabel">Gender</div>
                        <div class="p-field"><?= htmlspecialchars(ucfirst($userRow['gender'] ?? '')) ?></div>
                    </div>
                </div>
                <div class="profile-row">
                    <div class="icon-box"><i class="fa fa-cake-candles"></i></div>
                    <div>
                        <div class="plabel">Birthday</div>
                        <div class="p-field"><?= $userRow['birthday'] ? date("F d, Y", strtotime($userRow['birthday'])) : '-' ?></div>
                    </div>
                </div>
                <div class="profile-row">
                    <div class="icon-box"><i class="fa fa-briefcase"></i></div>
                    <div>
                        <div class="plabel">User Role</div>
                        <div class="p-field highlight" style="text-transform:capitalize;"><?= htmlspecialchars($userRow['role']) ?></div>
                    </div>
                </div>
                <div class="profile-row">
                    <div class="icon-box"><i class="fa fa-id-card"></i></div>
                    <div>
                        <div class="plabel">User ID</div>
                        <div class="p-field"><?= htmlspecialchars($userRow['user_id']) ?></div>
                    </div>
                </div>
                <div class="profile-row">
                    <div class="icon-box"><i class="fa fa-building-columns"></i></div>
                    <div>
                        <div class="plabel">Department</div>
                        <div class="p-field"><?= htmlspecialchars($userRow['department']) ?></div>
                    </div>
                </div>
            </div>
            <hr class="divider">
            <div class="profile-actions">
                <a href="?edit=1"><button class="edit-btn" type="button">Edit My Profile</button></a>
            </div>
        <?php else: ?>
        <form method="post" enctype="multipart/form-data" autocomplete="off">
            <div class="profile-img-box">
             <img class="profile-img" src="<?= $userRow['profile_pic'] ? 'uploads/'.htmlspecialchars($userRow['profile_pic']) : 'default_avatar.png' ?>" alt="Profile">
             <br>
             <input type="file" name="profile_pic" accept="image/*">
            </div>
            <div class="edit-row"><div class="plabel">Full Name</div>
             <input class="input-edit" name="name" type="text" required value="<?= htmlspecialchars($userRow['name']) ?>"></div>
            <div class="edit-row"><div class="plabel">Email Address</div>
             <input class="input-edit" name="email" type="email" required value="<?= htmlspecialchars($userRow['email']) ?>"></div>
            <div class="edit-row"><div class="plabel">Gender</div>
                <select class="input-edit" name="gender">
                    <option value="">--Choose--</option>
                    <option value="male" <?= ($userRow['gender'] ?? '')=='male'?'selected':'' ?>>Male</option>
                    <option value="female" <?= ($userRow['gender'] ?? '')=='female'?'selected':'' ?>>Female</option>
                    <option value="other" <?= ($userRow['gender'] ?? '')=='other'?'selected':'' ?>>Other</option>
                </select>
            </div>
            <div class="edit-row"><div class="plabel">Birthday</div>
                <input class="input-edit" name="birthday" type="date" value="<?= htmlspecialchars($userRow['birthday'] ?? '') ?>">
            </div>
            <div class="edit-row"><div class="plabel">Department</div>
                <input class="input-edit" name="department" type="text" value="<?= htmlspecialchars($userRow['department']) ?>">
            </div>
            <hr style="margin:24px 0 14px 0;border:0;border-top:1.5px solid #e9eef6;">
            <div style="font-weight:600;color:#204076;padding-bottom:8px;">Change Password</div>
            <?php if ($pass_error): ?>
                <div style="color:#c44;font-size:.98em;"><?= htmlspecialchars($pass_error) ?></div>
            <?php elseif ($pass_success): ?>
                <div style="color:#085;font-size:.98em;"><?= htmlspecialchars($pass_success) ?></div>
            <?php endif; ?>
            <div class="edit-row">
                <div class="plabel">Current Password</div>
                <input class="input-edit" type="password" name="current_password" autocomplete="off">
            </div>
            <div class="edit-row">
                <div class="plabel">New Password</div>
                <input class="input-edit" type="password" name="new_password" autocomplete="off">
            </div>
            <div class="edit-row">
                <div class="plabel">Confirm New Password</div>
                <input class="input-edit" type="password" name="confirm_password" autocomplete="off">
            </div>
            <button class="save-btn" type="submit" name="updateProfile"><i class="fa fa-save"></i> Save Changes</button>
            <a href="profile_participant_dashboard.php" class="cancel-btn">Cancel</a>
        </form>
        <?php endif; ?>
    </div>
</div>
<footer>
    School Event Management System &copy; <?= date("Y") ?>
</footer>
<script>
function updateTime() {
    const offset = 8 * 60; // Manila UTC+8
    const now = new Date();
    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
    const manilaNow = new Date(utc + (offset * 60000));
    let hour = manilaNow.getHours();
    let minute = manilaNow.getMinutes();
    let ampm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12;
    hour = hour ? hour : 12;
    minute = minute < 10 ? '0'+minute : minute;
    let timeStr = hour + ':' + minute + ' ' + ampm;
    document.getElementById('realtime-clock').textContent = timeStr;
}
updateTime();
setInterval(updateTime, 1000);
</script>
</body>
</html>
