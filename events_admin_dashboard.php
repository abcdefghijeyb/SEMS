<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;
if (!$user_name && $user_id) {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row) $user_name = $row['name'];
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: access_denied.php"); // Or redirect to appropriate page
    exit;
}
// Get all events
$stmt = $pdo->query("SELECT event_id, title, description, start_date, end_date, venue, image FROM events ORDER BY start_date DESC");
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events | School Event Management System</title>
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
            font-weight: 500; font-size: 1.11em; 
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
        .action-view {
            background:#5a80ff;
            color:#fff;
        }
        .action-edit {
            background:#eef4fa;
            color:#234488;
            border:1px solid #3b82f6;
        }
        .action-delete {
            background:#e53e3e;
            color:#fff;
        }
        .action-delete:hover {
            background:#be1b1b;
        }
        .add-event-btn {
            margin-top:24px;
             padding:9px 25px; 
             font-size:1.08em; 
             font-weight:700; 
             background:#4066b3; 
             color:#fff; 
             border:none; 
             border-radius:11px; 
             cursor:pointer;
            }
        .add-event-btn:hover {
            background:#204076;
        }
        .no-records {
            text-align:center; 
            color:#888; 
            font-style:italic; 
            padding:18px 5px;
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
        #viewModal, #editModal {
            display:none; position:fixed; z-index:1001; left:0; top:0; width:100vw; height:100vh;
            background:rgba(0,0,0,0.33); align-items: center; justify-content: center;
        }
        .modal-card {
            background:#fff; 
            border-radius:12px;
             max-width:370px; 
             width:92vw; margin:auto;
            box-shadow: 0 10px 40px #27365940;
             padding:2.3em 2.2em 1.7em 2.2em; 
             position:relative;
            animation: popin .23s;
        }
        @keyframes popin {0%{transform:scale(.92);opacity:0;}100%{transform:scale(1);opacity:1;}}
        .modal-close {
            position:absolute; 
            right:17px; top:12px; 
            cursor:pointer; 
            font-size:1.4em; 
            color:#888; 
            background:none; 
            border:none;
        }
        .modal-close:hover{
            color:#ff4747;
        }
        .modal-title {
            font-size:1.18em;
            font-weight:700;
            color:#204076;
            margin-bottom:.6em;
        }
        .modal-details {
            line-height:1.7;
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
            color:#fff; border:none; 
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
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php" class="logo-link"><div class="logo">SEMS</div></a>
        <div class="menu">
            <a href="students_admin_dashboard.php">USERS</a>
            <a href="events_admin_dashboard.php" class="active">EVENTS</a>
            <a href="attendance_admin_dashboard.php">ATTENDANCE</a>
            <a href="notifications_admin_dashboard.php">NOTIFICATIONS</a>
            <a href="resources_admin_dashboard.php">RESOURCES</a>
            <a href="financialrecords_admin_dashboard.php">FINANCIAL RECORDS</a>
        </div>
        <div class="user-dropdown">
            <div class="user-name"><?= htmlspecialchars($user_name ?? 'User') ?></div>
            <div class="dropdown-content-user">
                <a href="profile_admin_dashboard.php">My Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
    <div class="table-area">
        <h2 style="color:#204076; font-size:1.25em; margin-bottom:19px; font-weight:700; text-align:center;">
            Events Management
        </h2>
        <table>
            <thead>
                <tr>
                    <th>EVENT</th>
                    <th>DESCRIPTION</th>
                    <th>DATE</th>
                    <th>TIME</th>
                    <th>VENUE</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($events) {
                foreach ($events as $row) {
                    echo "<tr>
                        <td>".htmlspecialchars($row['title'])."</td>
                        <td>".htmlspecialchars($row['description'])."</td>
                        <td>".date('Y-m-d', strtotime($row['start_date'])).
                        (($row['end_date'] && $row['end_date'] != $row['start_date']) ? " - " . date('Y-m-d', strtotime($row['end_date'])) : '')."</td>
                        <td>".date('H:i', strtotime($row['start_date'])).
                        (($row['end_date'] && $row['end_date'] != $row['start_date']) ? " to " . date('H:i', strtotime($row['end_date'])) : '')."</td>
                        <td>".htmlspecialchars($row['venue'])."</td>
                        <td>
                            <button class='action-btn action-view' onclick='showViewModal({$row['event_id']})'>View</button>
                            <button class='action-btn action-edit' onclick='showEditModal({$row['event_id']})'>Edit</button>
                            <button class='action-btn action-delete' onclick='deleteEvent({$row['event_id']})'>Delete</button>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='no-records'>No events found.</td></tr>";
            }
            ?>
            </tbody>
        </table>
        <button class="add-event-btn" onclick="showAddEventModal()">ADD EVENT</button>
    </div>
    <!-- Modals -->
    <div id="viewModal"><div class="modal-card"><button id="closeViewModal" class="modal-close">&times;</button><div id="viewContent"></div></div></div>
    <div id="editModal"><div class="modal-card"><button id="closeEditModal" class="modal-close">&times;</button><div id="editContent"></div></div></div>
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
        document.getElementById('closeViewModal').onclick = function(){
            document.getElementById('viewModal').style.display='none';
        };
        document.getElementById('closeEditModal').onclick = function(){
            document.getElementById('editModal').style.display='none';
        };
    });

    function showAddEventModal() {
        document.getElementById('editModal').style.display = 'flex';
        document.getElementById('editContent').innerHTML = `
            <div class='modal-title'>Add Event</div>
            <form class='modal-form' id='eventForm' enctype='multipart/form-data'>
                <label>Event Name:</label>
                <input type='text' name='title' required>
                <label>Description:</label>
                <input type='text' name='description'>
                <label>Start Date:</label>
                <input type='date' name='start_date' required>
                <label>End Date:</label>
                <input type='date' name='end_date' required>
                <label>Start Time:</label>
                <input type='time' name='start_time' required>
                <label>End Time:</label>
                <input type='time' name='end_time' required>
                <label>Venue:</label>
                <input type='text' name='venue'>
                <label>Image:</label>
                <input type='file' name='image' accept='image/*'>
                <button type='submit'>Add Event</button>
            </form>
            <div id='modalMsg'></div>
        `;
        document.getElementById('eventForm').onsubmit = function(ev){
            ev.preventDefault();
            let form = this;
            let formData = new FormData(form);
            fetch('add_event.php', {method:'POST', body:formData})
            .then(res => res.text())
            .then(msg => {
                document.getElementById('modalMsg').innerHTML = `<div class='modal-alert'>${msg}</div>`;
                setTimeout(()=>{document.getElementById('editModal').style.display='none';window.location.reload();},1100);
            });
        };
    }

    function showViewModal(event_id){
        fetch('get_event_info.php?event_id='+event_id)
        .then(res => res.json())
        .then(data => {
            let html = `<div class='modal-title'>Event Details</div>
                <div class='modal-details'>
                <b>Event Name:</b> `+ (data.title || '') + `<br>
                <b>Description:</b> `+ (data.description || '') + `<br>
                <b>Date:</b> `+ (data.start_date ? data.start_date.substr(0,10):'') + (data.end_date && data.end_date!=data.start_date ? ' - ' + data.end_date.substr(0,10):'') + `<br>
                <b>Time:</b> `+ (data.start_date ? data.start_date.substr(11,5):'') + (data.end_date && data.end_date != data.start_date ? " to " + data.end_date.substr(11,5):'') + `<br>
                <b>Venue:</b> `+ (data.venue || '') + `<br>`;
            if (data.image) {
                html += `<img src='uploads/${data.image}' style='max-width:98%;border-radius:9px;margin:10px 0;' alt='Event Image'>`;
            }
            html += `</div>`;
            document.getElementById('viewContent').innerHTML = html;
            document.getElementById('viewModal').style.display = 'flex';
        });
    }

    function showEditModal(event_id){
        fetch('get_event_info.php?event_id='+event_id)
        .then(res => res.json())
        .then(data => {
            let html = `<div class='modal-title'>Edit Event</div>
                <form class='modal-form' id='editEventForm' enctype='multipart/form-data'>
                    <input type='hidden' name='event_id' value='${data.event_id}' />
                    <label>Event Name:</label>
                    <input type='text' name='title' value='${data.title || ""}' required>
                    <label>Description:</label>
                    <input type='text' name='description' value='${data.description || ""}'>
                    <label>Start Date:</label>
                    <input type='date' name='start_date' value='${data.start_date ? data.start_date.substr(0,10): ""}' required>
                    <label>End Date:</label>
                    <input type='date' name='end_date' value='${data.end_date ? data.end_date.substr(0,10): ""}' required>
                    <label>Start Time:</label>
                    <input type='time' name='start_time' value='${data.start_date ? data.start_date.substr(11,5): ""}' required>
                    <label>End Time:</label>
                    <input type='time' name='end_time' value='${data.end_date ? data.end_date.substr(11,5): ""}' required>
                    <label>Venue:</label>
                    <input type='text' name='venue' value='${data.venue || ""}'>
                    <label>Change Image:</label>
                    <input type='file' name='image' accept='image/*'><br>
                    ${data.image ? `<img src="uploads/${data.image}" style="max-width:85%;border-radius:8px;margin-bottom:11px;">` : ''}
                    <button type='submit'>Save Changes</button>
                </form>
                <div id='modalMsg'></div>`;
            document.getElementById('editContent').innerHTML = html;
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('editEventForm').onsubmit = function(ev){
                ev.preventDefault();
                let form = this;
                let formData = new FormData(form);
                fetch('update_event_info.php', {method:'POST', body:formData})
                .then(res => res.text())
                .then(msg => {
                    document.getElementById('modalMsg').innerHTML = `<div class='modal-alert'>${msg}</div>`;
                    setTimeout(()=>{document.getElementById('editModal').style.display='none';window.location.reload();},1100);
                });
            }
        });
    }

    function deleteEvent(event_id){
        if (confirm("Are you sure you want to delete this event?")) {
            fetch('delete_event.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: "event_id=" + encodeURIComponent(event_id)
            })
            .then(res => res.text())
            .then(msg => {
                alert(msg);
                location.reload();
            });
        }
    }
    </script>
</body>
</html>
