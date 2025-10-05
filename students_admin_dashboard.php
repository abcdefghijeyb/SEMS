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
    <title>Students | School Event Management System</title>
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
        .students-table-area {
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
        .action-btn {
            padding:7px 16px; 
            border-radius:7px; 
            font-weight:500; 
            margin-right:7px; 
            border:none; 
            cursor:pointer;
        }
        .action-edit {
            background:#3b82f6; 
            color:#fff;
        }
        .action-edit:hover {
            background:#234488;
        }
        .no-records {
            text-align:center; 
            color:#888; 
            font-style:italic; 
            padding:18px 5px;
        }
        #editModal {
            display:none; 
            position:fixed; 
            z-index:1001; 
            left:0; 
            top:0; 
            width:100vw; 
            height:100vh; 
            background:rgba(0,0,0,0.33); 
            align-items: center; 
            justify-content: center;
        }
        .modal-card {
            background:#fff; 
            border-radius:12px; 
            max-width:370px; 
            width:92vw; 
            margin:auto;
            box-shadow: 0 10px 40px #27365940; 
            padding:2.3em 2.2em 1.7em 2.2em; 
            position:relative; 
            animation: popin .23s;
        }
        @keyframes popin {0%{transform:scale(.92);opacity:0;}100%{transform:scale(1);opacity:1;}}
        .modal-close {
            position:absolute; 
            right:17px; 
            top:12px; 
            cursor:pointer; 
            font-size:1.4em; 
            color:#888; 
            background:none; 
            border:none;}
        .modal-close:hover{
            color:#ff4747;
        }
        .modal-title {
            font-size:1.18em;
            font-weight:700;
            color:#204076;
            margin-bottom:.6em;
        }
        .modal-form input{
            width:100%; 
            margin-bottom:1em; 
            padding:8px 11px; 
            border-radius:7px; 
            border:1px solid #d2d7e9; 
            font-size:.99em;
        }
        .modal-form input:focus{
            outline:2px solid #3b82f6;
        }
        .modal-form button{
            padding:8px 20px; 
            border-radius:8px; 
            background:#4066b3; 
            color:#fff; 
            border:none; 
            font-weight:600; 
            font-size:1em; 
            cursor:pointer;
        }
        .modal-form button:hover{
            background:#204076;
        }
        .modal-alert {
            background:#e6f2e9; 
            color:#1a8c45; 
            border-radius:7px; 
            padding:7px 10px; 
            margin-top:.6em; 
            text-align:center;
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
        @media (max-width:900px) {.navbar{padding:0 9px;} .students-table-area{padding:13px 2vw 15px 2vw;}}
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php" class="logo-link"><div class="logo">SEMS</div></a>
        <div class="menu">
            <a href="students_admin_dashboard.php" class="active">USERS</a>
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
    <div class="students-table-area">
        <h2 style="color:#204076; font-size:1.25em; margin-bottom:19px; font-weight:700; text-align:center;">
            All Users (Students, Organizers, Admin)
        </h2>
        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users = $pdo->query("SELECT user_id, name, email, department, role FROM users");
                $has_users = false;
                while ($row = $users->fetch()) {
                    $has_users = true;
                    $dept = $row['department'] ?? "";
                    $role = ucfirst($row['role']);
                    echo "<tr>
                        <td>{$row['user_id']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['email']}</td>
                        <td>".htmlspecialchars($dept)."</td>
                        <td>{$role}</td>
                        <td>
                            <button class='action-btn action-edit' type='button' onclick='showEditModal({$row['user_id']})'>Edit</button>
                        </td>
                    </tr>";
                }
                if (!$has_users) {
                    echo "<tr><td colspan='6' class='no-records'>No users found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <div id="editModal">
        <div class="modal-card">
            <button id="closeEditModal" class="modal-close" aria-label="Close">&times;</button>
            <div id="editContent"></div>
        </div>
    </div>
    <footer>
        School Event Management System &copy; <?php echo date("Y"); ?>
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
            document.getElementById('closeEditModal').onclick = function(){
                document.getElementById('editModal').style.display='none';
            };
        });

        function showEditModal(user_id){
            fetch('get_user_info.php?user_id=' + user_id)
            .then(res => res.json())
            .then(data => {
                let e = `<div class='modal-title'>Edit User</div>
                    <form class='modal-form' id='editForm'>
                        <input type='hidden' name='user_id' value='${data.user_id}' />
                        <label>Name:</label>
                        <input type='text' name='name' value='${data.name || ""}' required />
                        <label>Email:</label>
                        <input type='email' name='email' value='${data.email || ""}' required />
                        <label>Department:</label>
                        <input type='text' name='department' value='${data.department || ""}' />
                        <label>Role:</label>
                        <input type='text' name='role' value='${data.role || ""}' />
                        <button type='submit'>Save</button>
                    </form>
                    <div id='editMsg'></div>`;
                document.getElementById('editContent').innerHTML = e;
                document.getElementById('editModal').style.display = 'flex';
                document.getElementById('editForm').onsubmit = function(ev){
                    ev.preventDefault();
                    let formData = new FormData(this);
                    fetch('update_user_info.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.text())
                    .then(msg => {
                        document.getElementById('editMsg').innerHTML =
                            `<div class='modal-alert'>${msg}</div>`;
                    });
                }
            });
        }
    </script>
</body>
</html>
