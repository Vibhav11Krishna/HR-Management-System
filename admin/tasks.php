<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

include '../db/db.php';

// ✅ Fetch admin info
$adminId = $_SESSION['user_id'];
$adminQuery = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$adminQuery->bind_param("i", $adminId);
$adminQuery->execute();
$adminData = $adminQuery->get_result()->fetch_assoc();

// ✅ Fetch employees for dropdown
$employees = $conn->query("SELECT id, username FROM users WHERE role = 'employee'");

// ✅ Handle new task submission
$msg = '';
if (isset($_POST['submit'])) {
    $employee_id = $_POST['employee_id'];
    $title = $_POST['task_title'];
    $desc = $_POST['task_description'];
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("INSERT INTO tasks (employee_id, task_title, task_description, due_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $employee_id, $title, $desc, $due_date);

    if ($stmt->execute()) {
        $msg = "✅ Task assigned successfully!";
    } else {
        $msg = "❌ Error: " . $stmt->error;
    }
}

// ✅ Fetch all tasks with employee names
$tasks = $conn->query("
    SELECT t.*, u.username 
    FROM tasks t 
    JOIN users u ON t.employee_id = u.id 
    ORDER BY t.assigned_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tasks | HR Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff8f0;
            display: flex;
            min-height: 100vh;
            color: #333;
        }
.sidebar a i {
    margin-right: 10px;
    color: #fff; /* example */
}

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(200deg, #ff7b00, #ffb84d);
            display: flex;
            flex-direction: column;
            padding: 20px 15px;
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
            margin: 0 auto 15px;
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
            color: white;
            margin-top: 5px;
        }

        .divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.5);
            margin: 4px 0;
        }

        .sidebar a {
            text-decoration: none;
            color: #fff;
            padding: 12px 20px;
            margin: 6px 0;
            border-radius: 12px;
            display: block;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(8px);
        }

        .sidebar a.active {
            background: rgba(255, 255, 255, 0.3);
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
            background: linear-gradient(90deg, #ff7b00, #ffb84d);
            color: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            border-radius: 0 0 25px 25px;
        }

        .navbar h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 26px;
        }

        .logout-btn {
            background: #fff;
            color: #ff7b00;
            font-weight: 700;
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #ff7b00;
            color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        /* Content */
        .content {
            padding: 30px;
            flex: 1;
        }
         
        .welcome-card {
    padding: 25px;
    text-align: center;
    margin-bottom: 30px;
    background: linear-gradient(90deg, #ff7b00, #ffb84d);
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

        .msg {
            color: green;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
        }

        /* Form */
        .form-wrapper {
            background: #fff;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            max-width: 800px;
            margin: 0 auto 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-wrapper h2 {
            grid-column: 1/3;
            text-align: center;
            margin-bottom: 30px;
            color: #ff7b00;
            font-family: 'Orbitron', sans-serif;
        }

        .input-group {
            position: relative;
        }

        .input-group input,
        .input-group select,
        .input-group textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
            background: transparent;
            resize: none;
        }

        .input-group label {
            position: absolute;
            top: 12px;
            left: 15px;
            color: #aaa;
            font-size: 14px;
            transition: 0.3s;
            pointer-events: none;
        }

        .input-group input:focus+label,
        .input-group input:not(:placeholder-shown)+label,
        .input-group select:focus+label,
        .input-group select:not([value=""])+label,
        .input-group textarea:focus+label,
        .input-group textarea:not(:placeholder-shown)+label {
            top: -10px;
            left: 10px;
            font-size: 12px;
            color: #ff7b00;
            background: #fff;
            padding: 0 5px;
        }

        .form-wrapper input[type=submit] {
            grid-column: 1/3;
            width: 100%;
            background: #ff7b00;
            color: #fff;
            border: none;
            font-weight: 700;
            padding: 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        .form-wrapper input[type=submit]:hover {
            background: #ff9d2f;
        }
        /* Table */
        .table-wrapper {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
            margin: 0 auto;
        }

        .table-wrapper table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-wrapper th,
        .table-wrapper td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .table-wrapper th {
            background: #ff7b00;
            color: #fff;
        }

        .table-wrapper tr:hover {
            background: #fff2e6;
        }

        /* Footer */
        footer {
            width: 100%;
            text-align: center;
            background: linear-gradient(90deg, #ff7b00, #ffb84d);
            color: #fff;
            padding: 14px;
            border-radius: 25px 25px 0 0;
            font-weight: 600;
        }

        @media(max-width:900px) {
            .form-wrapper {
                grid-template-columns: 1fr;
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile">
            <div class="profile-pic">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Admin">
            </div>
            <h3><?= htmlspecialchars($adminData['username']) ?></h3>
            <p><?= htmlspecialchars($adminData['email']) ?></p>
        </div>

       <hr class="divider">
<a href="admin_dashboard.php" >
    <i class="fas fa-tachometer-alt"></i> Dashboard
</a>
<hr class="divider">
<a href="add_employee.php" >
    <i class="fas fa-user-plus"></i> Add Employee
</a>
<hr class="divider">
<a href="reports.php">
    <i class="fas fa-chart-line"></i> Reports
</a>
<hr class="divider">
<a href="tasks.php" class="active">
    <i class="fas fa-tasks"></i> Tasks
</a>
<hr class="divider">
<a href="performance.php">
    <i class="fas fa-star"></i> Performance
</a>
<hr class="divider">
<a href="attendance.php">
    <i class="fas fa-calendar-check"></i> Attendance
</a>
<hr class="divider">
<a href="payement.php">
    <i class="fas fa-money-bill-wave"></i> Payment
</a>
<hr class="divider">
<a href="settings.php">
    <i class="fas fa-cog"></i> Settings
</a>
<hr class="divider">
<a href="../auth/logout.php">
    <i class="fas fa-sign-out-alt"></i> Logout
</a>
    </div>

    <!-- Main Area -->
    <div class="main">
        <div class="navbar">
            <h1>HR Management Dashboard</h1>
            <span id="currentDateTime"></span>
        </div>

        <div class="content">
            <div class="welcome-card">
                <h2>Assign Tasks</h2>
                <p>Assign work to team member and track status</p>
                
            </div>
            <?php if ($msg) echo "<div class='msg'>$msg</div>"; ?>

            <!-- Task Form -->
            <form method="POST" class="form-wrapper">
                

                <div class="input-group">
                    <select name="employee_id" required>
                        <option value="" disabled selected></option>
                        <?php while ($emp = $employees->fetch_assoc()): ?>
                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['username']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <label>Employee</label>
                </div>

                <div class="input-group">
                    <input type="text" name="task_title" required placeholder=" ">
                    <label>Task Title</label>
                </div>

                <div class="input-group">
                    <textarea name="task_description" required placeholder=" " rows="3"></textarea>
                    <label>Task Description</label>
                </div>

                <div class="input-group">
                    <input type="date" name="due_date" required placeholder=" ">
                    <label>Due Date</label>
                </div>

                <input type="submit" name="submit" value="Assign Task">
            </form>

            <!-- Tasks Table -->
            <div class="table-wrapper">
                <h2 style="text-align:center; color:#ff7b00; margin-bottom:20px; font-family:'Orbitron',sans-serif;">
                    All Tasks
                </h2>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Assigned At</th>
                            <th>Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tasks->num_rows > 0): ?>
                            <?php while ($task = $tasks->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $task['id'] ?></td>
                                    <td><?= htmlspecialchars($task['username']) ?></td>
                                    <td><?= htmlspecialchars($task['task_title']) ?></td>
                                    <td><?= htmlspecialchars($task['task_description']) ?></td>
                                    <td><?= htmlspecialchars($task['status']) ?></td>
                                    <td><?= htmlspecialchars($task['assigned_at']) ?></td>
                                    <td><?= htmlspecialchars($task['due_date']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center;">No tasks assigned yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer>© 2025 HR Management System | Designed by Pulkit Krishna</footer>
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
