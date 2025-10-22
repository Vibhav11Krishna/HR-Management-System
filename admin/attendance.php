<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

include '../db/db.php';

// Fetch logged-in admin info
$adminId = $_SESSION['user_id'];
$adminData = $conn->query("SELECT username, email FROM users WHERE id='$adminId' AND role='admin'")->fetch_assoc();

// Total employees
$totalEmployees = $conn->query("SELECT * FROM employees")->num_rows;

// Attendance today
$today = date('Y-m-d');
$presentToday = $conn->query("SELECT * FROM attendance WHERE date='$today' AND status='Present'")->num_rows;
$absentToday = $conn->query("SELECT * FROM attendance WHERE date='$today' AND status='Absent'")->num_rows;
$leaveToday = $conn->query("SELECT * FROM attendance WHERE date='$today' AND status='Leave'")->num_rows;

// Attendance distribution (Pie)
$attendanceResult = $conn->query("SELECT status, COUNT(*) as count FROM attendance WHERE date='$today' GROUP BY status");
$attendanceLabels = [];
$attendanceCounts = [];
while ($row = $attendanceResult->fetch_assoc()) {
    $attendanceLabels[] = $row['status'];
    $attendanceCounts[] = $row['count'];
}

// Attendance over last 30 days (Bar chart)
$monthResult = $conn->query("SELECT date, COUNT(*) as present_count FROM attendance WHERE status='Present' AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY date ORDER BY date ASC");
$attendanceDates = [];
$attendancePresentCounts = [];
while ($row = $monthResult->fetch_assoc()) {
    $attendanceDates[] = $row['date'];
    $attendancePresentCounts[] = $row['present_count'];
}

// Today's attendance table
$todayAttendance = $conn->query("
    SELECT e.name, e.position, a.status
    FROM employees e
    LEFT JOIN attendance a ON e.id = a.employee_id AND a.date='$today'
    ORDER BY e.name ASC
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Attendance | HR Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@600&display=swap" rel="stylesheet">
    
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* You can copy the CSS from your analytics.php for sidebar, navbar, cards, charts, table, footer */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
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

       
        .sidebar a {
            position: relative;
            text-decoration: none;
            color: #fff;
            padding: 12px 20px;
            margin: 8px 0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            font-weight: 600;
            overflow: hidden;
            transition: all .3s ease;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(8px);
        }

        .sidebar a i {
            margin-right: 12px;
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
    color: white;
    margin-top: 5px;
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
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            border-radius: 0 0 25px 25px;
        }

        .navbar h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
        }

        #currentDateTime {
            font-weight: 600;
            font-size: 14px;
        }

        .content {
            padding: 30px;
            flex: 1;
        }

        .report-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 40px;
        }

        .report-card {
            flex: 1 1 200px;
             background: linear-gradient(500deg, #ff7b00, #ffb84d);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .report-card h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            color: white;
            margin-bottom: 10px;
        }

        .report-card p {
            font-size: 28px;
            font-weight: 700;
            color: white;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .charts-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            justify-content: center;
            margin-bottom: 40px;
        }

        .chart-card {
            flex: 1 1 400px;
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .chart-card h3 {
            font-family: 'Orbitron', sans-serif;
            color: #ff7b00;
            text-align: center;
            margin-bottom: 10px;
        }

        .chart-card p {
            text-align: center;
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
        }

        .attendance-table {
            max-width: 900px;
            margin: 0 auto 40px auto;
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
             .divider {
    border: none;
    height: 1px;
    background: rgba(255, 255, 255, 0.50);
    margin: 4px 0;
}

        .attendance-table h3 {
            font-family: 'Orbitron', sans-serif;
            color: #ff7b00;
            text-align: center;
            margin-bottom: 15px;
        }

        .attendance-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-table th,
        .attendance-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .page-heading {
            font-family: 'Orbitron', sans-serif;
            text-align: center;
            color: #ff7b00;
            margin-bottom: 40px;
            font-size: 32px;
            font-weight: 600;
        }

        .attendance-table th {
            background: #ff7b00;
            color: #fff;
        }

        .attendance-table tr:hover {
            background: #fff2e6;
        }

        footer {
            width: 100%;
            text-align: center;
            background: linear-gradient(90deg, #ff7b00, #ffb84d);
            color: #fff;
            padding: 14px;
            border-radius: 25px 25px 0 0;
            font-weight: 600;
        }
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.3);
        }
        .welcome-card {
    padding: 25px;
    text-align: center;
    margin-bottom: 20px;
    margin-right: 40px;
    margin-left: 40px;
    width: auto;
    margin-top: 20px;
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


        @media(max-width:1100px) {
            .charts-wrapper {
                flex-direction: column;
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
            <h3><?= $adminData['username'] ?></h3>
            <p><?= $adminData['email'] ?></p>
        </div>
        <hr class="divider">
<a href="admin_dashboard.php" >
    <i class="fas fa-tachometer-alt"></i> Dashboard
</a>
<hr class="divider">
<a href="add_employee.php">
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
<a href="attendance.php" class="active">
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

    <div class="main">
        <div class="navbar">
            <h1>HR Management Dashboard</h1>
            <span id="currentDateTime"></span>
        </div>
        <div class="welcome-card">
            <h2>Attendance</h2>
            <p> Manage & Analyze Attendabce data Effortlessly</p>
    </div>



            <div class="content">
                <!-- Summary Cards -->
                <div class="report-cards">
                    <div class="report-card">
                        <h3>Total Employees</h3>
                        <p><?= $totalEmployees ?></p>
                    </div>
                    <div class="report-card">
                        <h3>Present Today</h3>
                        <p><?= $presentToday ?></p>
                    </div>
                    <div class="report-card">
                        <h3>Absent Today</h3>
                        <p><?= $absentToday ?></p>
                    </div>
                    <div class="report-card">
                        <h3>On Leave Today</h3>
                        <p><?= $leaveToday ?></p>
                    </div>
                </div>

                <!-- Charts -->
                <div class="charts-wrapper">
                    <div class="chart-card">
                        <h3>Today's Attendance</h3>
                        <p>Proportion of Present, Absent, Leave</p>
                        <canvas id="attendancePie"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3>Attendance Last 30 Days</h3>
                        <p>Employees present per day</p>
                        <canvas id="attendanceBar"></canvas>
                    </div>
                </div>

                <!-- Attendance Table -->
                <div class="attendance-table">
                    <h3>Today's Attendance</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $todayAttendance->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['name'] ?></td>
                                    <td><?= $row['position'] ?></td>
                                    <td><?= $row['status'] ?? 'Not Marked' ?></td>
                                </tr>
                            <?php endwhile; ?>
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

            // Attendance Pie Chart
            new Chart(document.getElementById('attendancePie').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode($attendanceLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($attendanceCounts) ?>,
                        backgroundColor: ['#ff9900ff', '#dc3545', '#ffc107'],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Attendance Bar Chart
            new Chart(document.getElementById('attendanceBar').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($attendanceDates) ?>,
                    datasets: [{
                        label: 'Present Employees',
                        data: <?= json_encode($attendancePresentCounts) ?>,
                        backgroundColor: '#ff8c00ff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
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