<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if employee is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../auth/login.php");
    exit();
}

include '../db/db.php';

// Get employee email from session
$userEmail = $_SESSION['email'] ?? '';
if (!$userEmail) {
    die("❌ User email not found in session.");
}

// Fetch employee data from employees table
$query = $conn->prepare("SELECT id, name, email, username FROM employees WHERE email = ?");
$query->bind_param("s", $userEmail);
$query->execute();
$empData = $query->get_result()->fetch_assoc();

if (!$empData) {
    die("❌ Employee record not found for this user.");
}

// Fallback if username is empty
if (empty($empData['username'])) {
    $empData['username'] = $empData['name'];
}

// Mark attendance
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $today = date('Y-m-d');

    // Check if attendance already exists for today
    $check = $conn->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
    $check->bind_param("is", $empData['id'], $today);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $message = "Attendance already marked for today!";
    } else {
        $insert = $conn->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, ?)");
        $insert->bind_param("iss", $empData['id'], $today, $status);
        $insert->execute();
        $message = "Attendance marked successfully!";
    }
}

// Fetch attendance history
$attendanceHistory = $conn->prepare("SELECT date, status FROM attendance WHERE employee_id = ? ORDER BY date DESC");
$attendanceHistory->bind_param("i", $empData['id']);
$attendanceHistory->execute();
$attendanceResult = $attendanceHistory->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Attendance | Employee Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            min-height: 100vh;
            background: #f4fdf6;
            color: #333;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1abc9c, #16a085);
            display: flex;
            flex-direction: column;
            padding: 25px 15px;
            color: #fff;
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.15);
            border-radius: 0 25px 25px 0;
        }

        .profile {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-pic {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 15px auto;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
        }

        .profile-pic img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
        }

        .profile h3 {
            font-size: 20px;
            font-weight: 700;
        }

        .profile p {
            font-size: 14px;
            color: #c0f5e0;
            margin-top: 5px;
        }

        .sidebar a {
            text-decoration: none;
            color: #fff;
            padding: 14px 22px;
            margin: 5px 0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            font-weight: 600;
            transition: .3s;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(6px);
        }

        .sidebar a.active {
            background: rgba(255, 255, 255, 0.25);
        }

        .divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 4px 0;
        }

        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .navbar {
            width: 100%;
            background: linear-gradient(90deg, #1abc9c, #16a085);
            color: #fff;
            padding: 18px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.25);
            border-radius: 0 0 25px 25px;
        }

        .navbar h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
        }

        .top-info {
            display: flex;
            align-items: center;
            gap: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .logout-btn {
            background: #fff;
            color: #16a085;
            font-weight: 700;
            padding: 10px 18px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: .3s;
        }

        .logout-btn:hover {
            background: #16a085;
            color: #fff;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.3);
        }

        .content {
            padding: 35px;
            flex: 1;
        }

        

        .message {
            color: #16a085;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .attendance-form {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 20px;
        }

        .attendance-form button {
            padding: 12px 30px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: .3s;
        }

        .attendance-form button.present {
            background: #16a085;
            color: #fff;
        }

        .attendance-form button.present:hover {
            background: #1abc9c;
        }

        .attendance-form button.absent {
            background: #e74c3c;
            color: #fff;
        }

        .attendance-form button.absent:hover {
            background: #c0392b;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #16a085;
            padding: 12px;
            text-align: center;
        }

        .attendance-table th {
            background: linear-gradient(90deg, #16a085, #1abc9c);
            color: #fff;
            font-weight: 600;
        }

        .attendance-table td {
            background: #e8f5e9;
        }

        footer {
            width: 100%;
            text-align: center;
            background: linear-gradient(90deg, #1abc9c, #16a085);
            color: #fff;
            padding: 16px;
            border-radius: 25px 25px 0 0;
            font-weight: 700;
            margin-top: 20px;
        }
        .welcome-card {
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            background: linear-gradient(90deg, #1abc9c, #16a085);
            border-radius: 15px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .welcome-card h2 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .welcome-card p {
            color: white;
        }


        @media(max-width:900px) {

            .navbar,
            .content {
                padding: 15px;
            }

            .attendance-form {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="profile">
            <div class="profile-pic"><img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Employee"></div>
            <h3><?= htmlspecialchars($empData['username']) ?></h3>
            <p><?= htmlspecialchars($empData['email']) ?></p>
        </div>
        <hr class="divider">
<a href="employee_dashboard.php" class="sidebar-link ">
    <i class="fas fa-tachometer-alt"></i>&nbsp;&nbsp;&nbsp;Dashboard
</a>
<hr class="divider">
<a href="training.php" class="sidebar-link">
    <i class="fas fa-chalkboard-teacher"></i>&nbsp;&nbsp;&nbsp;Training & Skills
</a>
<hr class="divider">
<a href="tasks.php" class="sidebar-link">
    <i class="fas fa-tasks"></i>&nbsp;&nbsp;&nbsp;Tasks
</a>
<hr class="divider">
<a href="attendance.php" class="sidebar-link active">
    <i class="fas fa-calendar-check"></i>&nbsp;&nbsp;&nbsp;Attendance
</a>
<hr class="divider">
<a href="salary.php" class="sidebar-link">
    <i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;&nbsp;Salary
</a>
<hr class="divider">
<a href="settings.php" class="sidebar-link">
    <i class="fas fa-cog"></i>&nbsp;&nbsp;&nbsp;Settings
</a>
<hr class="divider">
<a href="../auth/logout.php" class="sidebar-link">
    <i class="fas fa-sign-out-alt"></i>&nbsp;&nbsp;&nbsp;Logout
</a>
    </div>

    <div class="main">
        <div class="navbar">
            <h1>Attendance Panel</h1>
            <div class="top-info"><span id="currentDateTime"></span></div>
        </div>

        <div class="content">
            <div class="welcome-card">
                <h2>Mark Your Attendance</h2>
                <p>Click below to mark your attendance for today</p>
            </div>

            <?php if ($message): ?>
                <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="post" class="attendance-form">
                <button type="submit" name="status" value="present" class="present">Present</button>
                <button type="submit" name="status" value="absent" class="absent">Absent</button>
            </form>

            <h2 style="text-align:center;color:#16a085;margin-bottom:15px;">Attendance History</h2>
            <table class="attendance-table">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
                <?php $i = 1;
                while ($row = $attendanceResult->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($row['status'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <footer>© 2025 HR Management System | Designed by Pulkit Krishna</footer>
    </div>

    <script>
        function updateDateTime() {
            const now = new Date();
            const opts = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('currentDateTime').innerText = now.toLocaleString('en-US', opts);
        }
        setInterval(updateDateTime, 1000);
        updateDateTime();
    </script>

</body>

</html>