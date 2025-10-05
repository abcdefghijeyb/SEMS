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
$user_id = $_SESSION['user_id'] ?? null;
$userRow = ["name" => "Unknown"];
if ($user_id) {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row) $userRow['name'] = $row['name'];
}

// DELETE resource
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM resources WHERE resource_id=?")->execute([$_GET['delete']]);
    header("Location: resources_admin_dashboard.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: access_denied.php");
    exit;
}
// Add/Edit resource
if (isset($_POST['saveResource'])) {
    $name = trim($_POST['name']);
    $type = trim($_POST['type']);
    $quantity = max(1, intval($_POST['quantity']));
    $availability = $_POST['availability_status'];
    $resource_id = $_POST['resource_id'] ?? '';

    if ($resource_id) {
        $stmt = $pdo->prepare("UPDATE resources SET name=?, type=?, quantity=?, availability_status=? WHERE resource_id=?");
        $stmt->execute([$name, $type ?: null, $quantity, $availability, $resource_id]);
        $msg = "Resource updated!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO resources (name, type, quantity, availability_status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $type ?: null, $quantity, $availability]);
        $msg = "Resource added!";
    }
}

$resources = $pdo->query("SELECT * FROM resources ORDER BY resource_id DESC")->fetchAll();
$editRow = null;
if (isset($_GET['edit'])) {
    $q = $pdo->prepare("SELECT * FROM resources WHERE resource_id=?");
    $q->execute([$_GET['edit']]);
    $editRow = $q->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resources | School Event Management System</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
    <style>
        html, body {
            margin:0; 
            padding:0; 
            background:#f8fafc; 
            min-height:100vh; 
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
        .menu {flex: 1; 
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
            transition: background .18s;
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
        }
        .user-dropdown.open .dropdown-content-user {
            display:block;
        }
        .dropdown-content-user a {
            display:block; 
            padding:12px 20px; 
            color:#204076; 
            text-decoration:none;
        }
        .dropdown-content-user a:hover {
            background:#eaeffe;
        }

        .table-area {
            width:100%; 
            max-width:1100px; 
            margin:36px auto 0 auto; 
            background:#fff; 
            border-radius:14px; 
            box-shadow:0 2px 10px #20407605;
             padding:34px 36px 37px 36px;
            }
        .heading {
            font-size:1.25em; 
            font-weight:700; 
            margin-bottom:26px; 
            color:#204076; text-align:center;}


        table {
            width:100%; 
            border-collapse:collapse; 
            margin-top:36px; 
            font-size:1.09em;
        }
        th, td {
            padding:16px 11px; 
            border-bottom:1px solid #e6ebee; 
            text-align:center;
        }
        th {
            background:#eef4fa; 
            font-weight:500; 
            font-size:1em;
        }
        td {
            font-size:1em;
        }
        .form-card {
            background:#f7fafe; 
            border-radius:13px; 
            max-width:700px; 
            margin:0 auto 34px auto; 
            box-shadow:0 4px 25px #33406612; 
            padding:30px 24px 18px 24px;
        }
        .form-row {
            display:flex; 
            flex-wrap:wrap; 
            gap:29px; 
            margin-bottom:21px;
        }
        .form-row > div {
            flex:1 1 175px;
        }
        .finput {
            padding:11px 13px; 
            font-size:1.08em; 
            border-radius:7px; 
            border:1px solid #d2d7e9; 
            width:100%;
        }
        .send-btn {
            padding:12px 32px; 
            background:#4066b3; 
            color:#fff; 
            font-size:1.07em; 
            border:none; 
            border-radius:8px; 
            font-weight:700; 
            margin-top:7px; 
            cursor:pointer;
        }
        .send-btn:hover {
            background:#234488;}
        .editbtn {
            padding:8px 18px; 
            background:#347edd; 
            color:#fff; 
            border:none; 
            border-radius:8px; 
            font-weight:600; 
            margin-right:2px; 
            cursor:pointer;
        }
        .editbtn:hover {
            background:#234488;
        }
        .delbtn {
            padding:8px 19px; 
            background:#b71c1c; 
            color:#fff; 
            border:none; 
            border-radius:7px; 
            font-weight:600; 
            cursor:pointer;
        }
        .delbtn:hover {
            background:#5f1919;
        }
        .cancelbtn {
            padding:11px 28px; 
            background:#bbb; 
            color:#fff; 
            border:none; 
            border-radius:7px; 
            font-weight:600; 
            margin-left:17px; 
            cursor:pointer;
        }
        .cancelbtn:hover {
            background:#888;
        }
        .result-alert {
            color:#094; 
            text-align:center;
             margin-bottom:16px;
            }
        .label {
            font-weight:500; 
            color:#234488; 
            margin-bottom:4px; 
            display:block;
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
        @media (max-width:900px) {.navbar{padding:0 9px;} .table-area{padding:14px 1.7vw 12px 1.7vw;}}
        @media (max-width:700px) {.form-row>div{flex-basis:98px;} table{font-size:.94em;}}
    </style>
</head>
<body>
<div class="navbar">
    <a href="admin_dashboard.php" class="logo-link"><div class="logo">SEMS</div></a>
    <div class="menu">
        <a href="students_admin_dashboard.php">USERS</a>
        <a href="events_admin_dashboard.php">EVENTS</a>
        <a href="attendance_admin_dashboard.php">ATTENDANCE</a>
        <a href="notifications_admin_dashboard.php">NOTIFICATIONS</a>
        <a href="resources_admin_dashboard.php" class="active">RESOURCES</a>
        <a href="financialrecords_admin_dashboard.php">FINANCIAL RECORDS</a>
    </div>
    <div class="user-dropdown">
        <div class="user-name"><?= htmlspecialchars($userRow['name']) ?></div>
        <div class="dropdown-content-user">
            <a href="profile_admin_dashboard.php">My Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>
<div class="table-area">
    <div class="heading"><?= $editRow ? "Edit Resource" : "Resources Management" ?></div>
    <?php if(isset($msg)): ?>
        <div class="result-alert"><?= $msg ?></div>
    <?php endif; ?>
    <div class="form-card">
    <form method="post" autocomplete="off">
        <input type="hidden" name="resource_id" value="<?= $editRow['resource_id'] ?? '' ?>">
        <div class="form-row">
            <div>
                <label class="label">Resource Name:</label>
                <input class="finput" name="name" type="text" required value="<?= $editRow['name'] ?? '' ?>">
            </div>
            <div>
                <label class="label">Type <span style="font-weight:400;color:#999;">(optional)</span>:</label>
                <input class="finput" name="type" type="text" value="<?= $editRow['type'] ?? '' ?>">
            </div>
            <div>
                <label class="label">Quantity:</label>
                <input class="finput" name="quantity" type="number" min="1" required value="<?= $editRow['quantity'] ?? 1 ?>">
            </div>
            <div>
                <label class="label">Availability Status:</label>
                <select name="availability_status" class="finput" required>
                    <option value="available" <?= ($editRow['availability_status'] ?? '')=='available'?'selected':''; ?>>Available</option>
                    <option value="unavailable" <?= ($editRow['availability_status'] ?? '')=='unavailable'?'selected':''; ?>>In Use</option>
                    <option value="maintenance" <?= ($editRow['availability_status'] ?? '')=='maintenance'?'selected':''; ?>>Maintenance</option>
                </select>
            </div>
        </div>
        <button name="saveResource" class="send-btn"><?= $editRow ? "Update" : "Add Resource"?></button>
        <?php if ($editRow): ?>
        <a href="resources_admin_dashboard.php" class="cancelbtn">Cancel</a>
        <?php endif; ?>
    </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Resource Name</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Availability</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$resources): ?>
            <tr><td colspan="5" style="color:#8a8a8a;text-align:center;font-style:italic;">No resources found.</td></tr>
        <?php else: foreach ($resources as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= htmlspecialchars($r['type'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['quantity']) ?></td>
                <td><?= htmlspecialchars(ucfirst($r['availability_status'])) ?></td>
                <td>
                    <a href="?edit=<?= $r['resource_id'] ?>" class="editbtn">Edit</a>
                    <a href="?delete=<?= $r['resource_id'] ?>" class="delbtn" onclick="return confirm('Delete this resource?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
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
