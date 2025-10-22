<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../auth/login.php");
    exit();
}

include '../db/db.php';
$empId = $_SESSION['user_id'];

// ✅ Fetch employee info safely
$empQuery = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$empQuery->bind_param("i", $empId);
$empQuery->execute();
$empResult = $empQuery->get_result();
$empData = $empResult->fetch_assoc() ?? ['username' => 'Unknown', 'email' => null];

// ✅ Fetch all tasks using correct column names
$taskQuery = $conn->prepare("
    SELECT id, task_title, task_description, status, created_at 
    FROM tasks 
    WHERE employee_id = ? 
    ORDER BY created_at DESC
");
$taskQuery->bind_param("i", $empId);
$taskQuery->execute();
$taskResult = $taskQuery->get_result();
$tasks = $taskResult->fetch_all(MYSQLI_ASSOC);

// ✅ Handle mark completed action
if (isset($_POST['complete_task']) && isset($_POST['task_id'])) {
    $taskId = (int)$_POST['task_id'];
    $stmt = $conn->prepare("UPDATE tasks SET status = 'Completed' WHERE id = ? AND employee_id = ?");
    $stmt->bind_param("ii", $taskId, $empId);
    $stmt->execute();
    header("Location: tasks.php");
    exit();
}
?>
<!-- HTML HEAD STARTS HERE -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tasks | Employee Dashboard</title>
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
    transition: 0.3s;
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
    transition: all .3s ease;
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

        /* Main Area */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
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
            font-weight: 600;
        }

        .logout-btn {
            background: #fff;
            color: #16a085;
            font-weight: 700;
            padding: 10px 18px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #16a085;
            color: #fff;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.3);
        }

        /* Content */
        .content {
            padding: 35px;
        }

        h2.heading-center {
            color: #16a085;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 700;
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


        /* Tasks Table */
        .tasks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .tasks-table th,
        .tasks-table td {
            border: 1px solid #ccc;
            padding: 14px;
            text-align: center;
        }

        .tasks-table th {
            background: linear-gradient(90deg, #16a085, #1abc9c);
            color: #fff;
        }

        .completed {
            background: #e8f5e9;
            color: #0e6655;
            font-weight: 600;
        }

        .complete-btn {
            padding: 8px 14px;
            border: none;
            border-radius: 10px;
            background: #27ae60;
            color: #fff;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 600;
        }

        .complete-btn:hover {
            background: #1e8449;
        }

        /* Footer */
        footer {
            width: 100%;
            text-align: center;
            background: linear-gradient(90deg, #1abc9c, #16a085);
            color: #fff;
            padding: 16px;
            border-radius: 25px 25px 0 0;
            font-weight: 700;
            margin-top: auto;
        }

        @media(max-width:900px) {

            .tasks-table,
            .tasks-table th,
            .tasks-table td {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile">
            <div class="profile-pic">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Employee">
            </div>
            <h3><?= htmlspecialchars($empData['username'] ?? 'Unknown') ?></h3>
            <p><?= htmlspecialchars($empData['email'] ?? 'Email not available') ?></p>
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
<a href="tasks.php" class="sidebar-link active">
    <i class="fas fa-tasks"></i>&nbsp;&nbsp;&nbsp;Tasks
</a>
<hr class="divider">
<a href="attendance.php" class="sidebar-link ">
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
            <h1>Tasks Panel</h1>
            <div class="top-info">
                <span id="currentDateTime"></span>
            </div>
        </div>

        <div class="content">
            <div class="welcome-card">
                <h2>Employee Tasks</h2>
                <p>Tasks Mentioned As Per Assinged Date</p>
            </div>
            <table class="tasks-table">
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Assigned On</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php if (count($tasks) > 0): ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr class="<?= ($task['status'] ?? '') === 'Completed' ? 'completed' : '' ?>">
                            <td><?= htmlspecialchars($task['task_title'] ?? '') ?></td>
                            <td><?= htmlspecialchars($task['task_description'] ?? '') ?></td>
                            <td><?= !empty($task['created_at']) ? date('d M Y', strtotime($task['created_at'])) : '-' ?></td>
                            <td><?= ucfirst($task['status'] ?? 'Pending') ?></td>
                            <td>
                                <?php if (($task['status'] ?? '') !== 'Completed'): ?>
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                        <button type="submit" name="complete_task" class="complete-btn">Mark Completed</button>
                                    </form>
                                <?php else: ?> ✅ <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No tasks assigned yet.</td></tr>
                <?php endif; ?>
            </table>
        </div>

        <footer>© 2025 HR Management | Employee Panel</footer>
    </div>

    <script>
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('currentDateTime').innerText = now.toLocaleString('en-US', options);
        }
        setInterval(updateDateTime, 1000);
        updateDateTime();
    </script>
</body>
</html>