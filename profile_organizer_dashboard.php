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

    // Only update basic profile if not changing password or password is OK
    if(!$pass_error && empty($_POST['new_password'])) {
        $update = $pdo->prepare("UPDATE users SET name=?, email=?, gender=?, birthday=?, profile_pic=? WHERE user_id=?");
        $update->execute([
            $name, $email, $gender, $birthday, $profile_pic, $userRow['user_id']
        ]);
        header("Location: profile_organizer_dashboard.php");
        exit();
    }
}
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
        .navbar {display: flex; align-items: center; justify-content: space-between; background: #fff; box-shadow: 0 2px 14px #e6eaf3; height: 70px; padding: 0 36px;}
        .logo {font-size: 1.32em; font-weight: 700; letter-spacing: 0.06em; color: #204076; margin-right: 40px;}
        .logo-link { color:inherit; text-decoration:none; }
        .menu {flex: 1 1 auto; display: flex; justify-content: center; gap: 38px;}
        .menu a {white-space:nowrap; color: #273659; text-decoration: none; font-weight: 500; font-size: 1.11em; padding: 9px 13px; border-radius: 7px;}
        .menu a.active, .menu a:focus, .menu a:active {color: #fff; background: #4066b3;}
        .menu a:hover:not(.active) {color: #3b82f6; background:#e6ebfa;}
        .user-dropdown {display:flex; align-items:center; gap:10px; position:relative;}
        .user-name {color: #fff; background:#234488; padding:9px 19px 9px 15px; border-radius:22px; font-weight:600; font-size:1.07em;}
        .dropdown-content-user {display:none; position:absolute; right:0; top:100%; background:#fff; min-width:150px; box-shadow:0 4px 14px #23448815; border-radius:8px; z-index:10;}
        .user-dropdown.open .dropdown-content-user {display:block;}
        .dropdown-content-user a {display:block; padding:12px 20px; color:#204076; text-decoration:none;}
        .dropdown-content-user a:hover {background:#eaeffe;}
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
        footer {background: #22283d; color: #f1f4fa; text-align: center; padding: 24px 0 18px 0; font-size:1.08em; margin-top: auto; font-weight:600;}
    </style>
</head>
<body>
<div class="navbar">
    <a href="organizer_dashboard.php" class="logo-link"><div class="logo">SEMS</div></a>
    <div class="menu">
        <a href="events_organizer_dashboard.php">EVENTS</a>
        <a href="attendance_organizer_dashboard.php">ATTENDANCE</a>
        <a href="notifications_organizer_dashboard.php">NOTIFICATIONS</a>
        <a href="resources_organizer_dashboard.php">RESOURCES</a>
    </div>
    <div class="user-dropdown">
        <div class="user-name"><?= htmlspecialchars($userRow['name'] ?? '') ?></div>
        <div class="dropdown-content-user">
            <a href="profile_organizer_dashboard.php" class="active">My Profile</a>
            <a href="logout.php">Logout</a>
        </div>
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
            <a href="profile_organizer_dashboard.php" class="cancel-btn">Cancel</a>
        </form>
        <?php endif; ?>
    </div>
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
