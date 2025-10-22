<?php
session_start();
include '../db/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$adminId = $_SESSION['user_id'];
$adminData = $conn->query("SELECT username, email FROM users WHERE id='$adminId'")->fetch_assoc();
$employees = $conn->query("SELECT id, name FROM employees ORDER BY name");

$msg = '';
if (isset($_POST['submit_salary'])) {
    $employee_id = $_POST['employee_id'];
    $amount = $_POST['amount'];
    $month = $_POST['month'];
    $credited_date = $_POST['credited_date'];

    $stmt = $conn->prepare("INSERT INTO salary (employee_id, amount, month, credited_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $employee_id, $amount, $month, $credited_date);
    $msg = $stmt->execute() ? "✅ Salary credited successfully!" : "❌ Error: " . $stmt->error;
}

// Fetch salary records
$salaryRecords = $conn->query("
    SELECT s.id, e.name, s.amount, s.month, s.credited_date
    FROM salary s
    JOIN employees e ON s.employee_id = e.id
    ORDER BY s.credited_date DESC
");

// Prepare data for Chart.js
$salaryByMonth = $conn->query("
    SELECT month, SUM(amount) as total
    FROM salary
    GROUP BY month
    ORDER BY STR_TO_DATE(CONCAT('01 ', month), '%d %M %Y')
");

$chartLabels = [];
$chartData = [];
while ($row = $salaryByMonth->fetch_assoc()) {
    $chartLabels[] = $row['month'];
    $chartData[] = $row['total'];
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin | Credit Salary</title>
    
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Orbitron&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            background: #fff8f0;
            color: #333;
        }
        .sidebar a i {
    margin-right: 10px;
    color: #fff; /* example */
}


        .sidebar {
            width: 260px;
            background: linear-gradient(200deg, #ff7b00, #ffb84d);
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
            background: #fff;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 15px auto;
            display: flex;
            align-items: center;
            justify-content: center;
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
            color: #ffe0c2;
            margin-top: 5px;
        }

        .sidebar a {
            text-decoration: none;
            color: #fff;
            padding: 14px 22px;
            margin: 5px 0;
            border-radius: 12px;
            display: block;
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

        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .navbar {
            width: 100%;
            background: linear-gradient(90deg, #ff7b00, #ffb84d);
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
            font-size: 14px;
        }

        .content {
            padding: 35px;
            flex: 1;
        }

        footer {
            width: 100%;
            text-align: center;
            background: linear-gradient(90deg, #ff7b00, #ffb84d);
            color: #fff;
            padding: 16px;
            border-radius: 25px 25px 0 0;
            font-weight: 700;
        }

        .form-box {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            max-width: 500px;
            margin: auto;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            position: relative;
            margin-top: 25px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            background: transparent;
        }

        .form-group label {
            position: absolute;
            top: 12px;
            left: 12px;
            color: #666;
            font-size: 15px;
            transition: 0.2s ease all;
            background-color: #fff;
            padding: 0 5px;
            pointer-events: none;
        }

        .form-group input:focus~label,
        .form-group input:not(:placeholder-shown)~label,
        .form-group select:focus~label,
        .form-group select:not([value=""])~label {
            top: -8px;
            left: 10px;
            color: #ff7b00;
            font-size: 13px;
        }

        .form-box button {
            margin-top: 25px;
            background: #ff7b00;
            color: #fff;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .form-box button:hover {
            background: #e86c00;
            transform: translateY(-2px);
        }

        .table-wrapper {
            margin-top: 40px;
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 15px;
            overflow: hidden;
        }

        .salary-table th {
            background: linear-gradient(90deg, #ff7b00, #e86c00);
            color: #fff;
            font-weight: 600;
            padding: 14px;
            text-align: center;
            font-size: 15px;
        }

        .salary-table td {
            background: #fff3e0;
            padding: 14px;
            text-align: center;
            font-size: 14px;
            border-bottom: 1px solid #ffd9b3;
            transition: all 0.3s ease;
        }

        .salary-table tr:hover td {
            background: #ffe6cc;
            transform: scale(1.02);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }

        .msg {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .welcome-card {
    padding: 25px;
    text-align: center;
    margin-bottom: 20px;
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


        .chart-container {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            margin-top: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
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
<a href="tasks.php">
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
<a href="payement.php" class="active">
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

    <!-- Main Section -->
    <div class="main">
        <div class="navbar">
            <h1>HR Management Dashboard</h1>
            <div class="top-info" id="currentDateTime"></div>
        </div>

        <div class="content">
            <div class="welcome-card">
                <h2>Payment</h2>
                <p>Add Employee and view salary records</p>
            </div>

            <!-- Salary Form -->
            <div class="form-box">
                
                <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <select name="employee_id" required>
                            <option value="">-- Select Employee --</option>
                            <?php while ($emp = $employees->fetch_assoc()): ?>
                                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <label>Employee</label>
                    </div>
                    <div class="form-group">
                        <input type="number" name="amount" step="0.01" placeholder=" " required>
                        <label>Amount</label>
                    </div>
                    <div class="form-group">
                        <input type="text" name="month" placeholder=" " required>
                        <label>Month (e.g. October 2025)</label>
                    </div>
                    <div class="form-group">
                        <input type="date" name="credited_date" placeholder=" " required>
                        <label>Credited Date</label>
                    </div>
                    <button type="submit" name="submit_salary">Credit Salary</button>
                </form>
            </div>

            <!-- Salary Table -->
            <div class="table-wrapper">
                <table class="salary-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Amount</th>
                            <th>Month</th>
                            <th>Credited Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1;
                        while ($row = $salaryRecords->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td>₹ <?= number_format($row['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($row['month']) ?></td>
                                <td><?= date('d M Y', strtotime($row['credited_date'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Chart Section -->
            <div class="chart-container">
                <canvas id="salaryChart"></canvas>
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

        // Chart.js
        const ctx = document.getElementById('salaryChart').getContext('2d');
        const salaryChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Total Salary Paid (₹)',
                    data: <?= json_encode($chartData) ?>,
                    backgroundColor: 'rgba(255,123,0,0.7)',
                    borderColor: 'rgba(255,123,0,1)',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

</body>

</html>