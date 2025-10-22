<?php
session_start();
include '../db/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../auth/login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userData = $conn->query("SELECT employee_id, username, email FROM users WHERE id='$userId'")->fetch_assoc();
$empId = $userData['employee_id'];

// Fetch salary records
$salaryResult = $conn->query("SELECT amount, month, credited_date FROM salary WHERE employee_id='$empId' ORDER BY credited_date DESC");

// Summary calculations
$totalMonths = $salaryResult->num_rows;
$totalAmount = 0;
$latestSalary = 0;
$latestMonth = '';
$salaryResult->data_seek(0);
while ($row = $salaryResult->fetch_assoc()) {
    $totalAmount += $row['amount'];
    if ($latestSalary == 0) {
        $latestSalary = $row['amount'];
        $latestMonth = $row['month'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Salary</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Orbitron&display=swap" rel="stylesheet">
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

        /* Sidebar */
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
            font-weight: 900;
            margin-bottom: 10px;
        }

        .welcome-card p {
            color: white;
        }
        .divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 4px 0;
        }

        /* Main Layout */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(90deg, #1abc9c, #16a085);
            color: #fff;
            padding: 18px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 25px 25px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
        }

        .navbar h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 26px;
        }

        .top-info {
            font-weight: 600;
            font-size: 14px;
        }

        /* Content */
        .content {
            flex: 1;
            padding: 40px 35px;
        }

        

        /* Summary Boxes */
        .summary-boxes {
            display: flex;
            justify-content: center;
            align-items: stretch;
            gap: 25px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .summary-box {
            flex: 1;
            min-width: 350px;
            background: linear-gradient(135deg, #16a085, #1abc9c);
            color: #fff;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform .3s;
        }

        .summary-box:hover {
            transform: translateY(-5px);
        }

        .summary-box h3 {
            font-family: 'Orbitron';
            margin-bottom: 10px;
        }

        .summary-box p {
            font-size: 25px;
            font-weight: 700;
        }

        /* Table */
        table {
            width: 100%;
            max-width: 4000px;
            margin: 0 auto;
            border-collapse: collapse;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background: linear-gradient(90deg, #16a085, #1abc9c);
            color: #fff;
            padding: 12px;
            font-size: 18px;
        }

        td {
            background: #e8f5e9;
            padding: 10px;
            text-align: center;
            font-size: 18px;
        }

        tr:hover td {
            background: #d0f5e0;
            transition: 0.3s;
        }

        /* Footer */
        footer {
            text-align: center;
            background: linear-gradient(90deg, #1abc9c, #16a085);
            color: #fff;
            padding: 16px;
            font-weight: 700;
            border-radius: 25px 25px 0 0;
        }

        /* Responsive */
        @media(max-width:900px) {
            .navbar, .content {
                padding: 15px;
            }
            .summary-boxes {
                flex-direction: column;
                align-items: center;
            }
            table {
                width: 95%;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile">
            <div class="profile-pic">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Employee">
            </div>
            <h3><?= htmlspecialchars($userData['username']) ?></h3>
            <p><?= htmlspecialchars($userData['email']) ?></p>
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
<a href="attendance.php" class="sidebar-link ">
    <i class="fas fa-calendar-check"></i>&nbsp;&nbsp;&nbsp;Attendance
</a>
<hr class="divider">
<a href="salary.php" class="sidebar-link active">
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

    <!-- Main Section -->
    <div class="main">
        <div class="navbar">
            <h1>Salary Panel</h1>
            <div class="top-info" id="currentDateTime"></div>
        </div>

        <div class="content">
            <div class="welcome-card">
                <h2>Employee Salary</h2>
                <p>Updated Salary Per Month</p>
            </div>
            

            <div class="summary-boxes">
                <div class="summary-box">
                    <h3>Latest Salary</h3>
                    <p>₹ <?= number_format($latestSalary, 2) ?> (<?= $latestMonth ?>)</p>
                </div>
                <div class="summary-box">
                    <h3>Total Months</h3>
                    <p><?= $totalMonths ?></p>
                </div>
                <div class="summary-box">
                    <h3>Total Earnings</h3>
                    <p>₹ <?= number_format($totalAmount, 2) ?></p>
                </div>
            </div>

            <table>
                <tr>
                    <th>#</th>
                    <th>Month</th>
                    <th>Amount</th>
                    <th>Credited Date</th>
                </tr>
                <?php if ($salaryResult->num_rows > 0): $i = 1;
                    $salaryResult->data_seek(0); ?>
                    <?php while ($row = $salaryResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['month']) ?></td>
                            <td>₹ <?= number_format($row['amount'], 2) ?></td>
                            <td><?= date('d M Y', strtotime($row['credited_date'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No salary records found.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <footer>© 2025 HR Management System | Employee Panel</footer>
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
