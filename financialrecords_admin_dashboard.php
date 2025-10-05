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

// DELETE record
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM financial_records WHERE financial_id=?")->execute([$_GET['delete']]);
    header("Location: financialrecords_admin_dashboard.php");
    exit();
}

// Add/Edit record
if (isset($_POST['saveFinancial'])) {
    $event_id = $_POST['event_id'];
    $expense_type = trim($_POST['expense_type']);
    $amount = floatval($_POST['amount']);
    $notes = trim($_POST['notes']);
    $financial_id = $_POST['financial_id'] ?? '';

    if ($financial_id) {
        $stmt = $pdo->prepare("UPDATE financial_records SET event_id=?, expense_type=?, amount=?, notes=? WHERE financial_id=?");
        $stmt->execute([$event_id, $expense_type, $amount, $notes ?: null, $financial_id]);
        $resultMessage = "Record updated!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO financial_records (event_id, expense_type, amount, date_logged, notes) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->execute([$event_id, $expense_type, $amount, $notes ?: null]);
        $resultMessage = "Record added!";
    }
}

// List all records
$sql = "
SELECT f.*, e.title AS event_title
FROM financial_records f
LEFT JOIN events e ON f.event_id = e.event_id
ORDER BY date_logged DESC, financial_id DESC";
$records = $pdo->query($sql)->fetchAll();

// For edit form
$editRecord = null;
if (isset($_GET['edit'])) {
    $editRecord = $pdo->prepare("SELECT * FROM financial_records WHERE financial_id=?");
    $editRecord->execute([$_GET['edit']]);
    $editRecord = $editRecord->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Records | School Event Management System</title>
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
            font-size:1em;
        }
        .dropdown-content-user a:hover {
            background:#eaeffe;
        }
        .table-area {
            width:100%; 
            max-width:1050px; 
            margin:36px auto 0 auto; 
            background:#fff; 
            border-radius:14px; 
            box-shadow:0 2px 10px #20407605; 
            padding:32px 32px 34px 32px;
        }
        .heading {
            font-size:1.25em; 
            font-weight:700; 
            margin-bottom:27px; 
            color:#204076; 
            text-align:center;
        }
        table {
            width:100%; 
            border-collapse:collapse; 
            margin-top:34px;
        }
        th, td {
            padding:14px 11px; 
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
        .form-row {
            display:flex; 
            flex-wrap:wrap; 
            gap:26px; 
            margin-bottom:18px;
        }
        .form-row > div {
            flex:1; 
            min-width:125px;
        }
        .finput {
            padding:10px 13px; 
            font-size:1.09em; 
            border-radius:7px; 
            border:1px solid #d2d7e9; 
            width:100%;
        }
        .send-btn {
            padding:10px 34px; 
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
        .editbtn {
            padding:8px 14px; 
            background:#347edd; 
            color:#fff; border:none; 
            border-radius:7px; 
            font-weight:600; 
            cursor:pointer;
        }
        .editbtn:hover {
            background:#234488;
        }
        .delbtn {
            padding:8px 16px; 
            background:#b71c1c; 
            color:#fff; 
            border:none; 
            border-radius:7px; 
            font-weight:600;
             margin-left:5px; 
             cursor:pointer;
            }
        .delbtn:hover {
            background:#641212;
        }
        .result-alert {
            color:#094; 
            text-align:center; 
            margin-bottom:17px;
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
        @media (max-width:950px) {.navbar{padding:0 9px;} .table-area{padding:12px 2vw 14px 2vw;}}
        @media (max-width:650px) {.table-area{padding:11px 0.5vw 11px 0.5vw;} .form-row{gap:7px;}}
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
        <a href="resources_admin_dashboard.php">RESOURCES</a>
        <a href="financialrecords_admin_dashboard.php" class="active">FINANCIAL RECORDS</a>
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
    <div class="heading"><?= $editRecord ? "Edit Financial Record" : "Financial Records Management" ?></div>
    <?php if(isset($resultMessage)): ?>
        <div class="result-alert"><?= $resultMessage ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off" style="margin-bottom:30px;">
        <input type="hidden" name="financial_id" value="<?= $editRecord['financial_id'] ?? '' ?>">
        <div class="form-row">
            <div>
                <label class="label">Event:</label>
                <select name="event_id" class="finput" required>
                    <option value="">-- Select event --</option>
                    <?php
                    $event_opts = $pdo->query("SELECT event_id, title FROM events ORDER BY event_id DESC");
                    foreach ($event_opts as $ev) {
                        $sel = (@$editRecord['event_id'] == $ev['event_id']) ? "selected" : "";
                        echo '<option value="'.$ev['event_id'].'" '.$sel.'>'.htmlspecialchars($ev['title']).'</option>';
                    }
                    ?>
                </select>
            </div>
            <div>
                <label class="label">Expense Type:</label>
                <input class="finput" name="expense_type" type="text" required value="<?= $editRecord['expense_type'] ?? '' ?>">
            </div>
            <div>
                <label class="label">Amount:</label>
                <input class="finput" name="amount" type="number" min="0" step="0.01" required value="<?= $editRecord['amount'] ?? '' ?>">
            </div>
            <div>
                <label class="label">Notes <span style="color:#aaa;">(optional)</span>:</label>
                <input class="finput" name="notes" type="text" value="<?= $editRecord['notes'] ?? '' ?>">
            </div>
        </div>
        <button name="saveFinancial" class="send-btn"><?= $editRecord ? "Update" : "Add Record"?></button>
        <?php if ($editRecord): ?>
        <a href="financialrecords_admin_dashboard.php" style="margin-left:16px;color:#4066b3;font-weight:600;">Cancel</a>
        <?php endif; ?>
    </form>
    <table>
        <thead>
            <tr>
                <th>Date Logged</th>
                <th>Event</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$records): ?>
            <tr><td colspan="6" style="color:#8a8a8a;text-align:center;font-style:italic;">No records found.</td></tr>
        <?php else: foreach ($records as $rec): ?>
            <tr>
                <td><?= htmlspecialchars(date("M d, Y", strtotime($rec['date_logged']))) ?></td>
                <td><?= htmlspecialchars($rec['event_title'] ?? '') ?></td>
                <td><?= htmlspecialchars($rec['expense_type']) ?></td>
                <td>₱<?= number_format($rec['amount'],2) ?></td>
                <td><?= htmlspecialchars($rec['notes']) ?></td>
                <td>
                    <a href="?edit=<?= $rec['financial_id'] ?>" class="editbtn">Edit</a>
                    <a href="?delete=<?= $rec['financial_id'] ?>" class="delbtn" onclick="return confirm('Delete this record?')">Delete</a>
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
