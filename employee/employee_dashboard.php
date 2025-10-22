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
$empData = $conn->query("SELECT employee_id, username, email FROM users WHERE id='$empId'")->fetch_assoc();
$empCode = $empData['employee_id'];

// Stats
$totalTasks = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE employee_id='$empId'")->fetch_assoc()['total'];
$completedTasks = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE employee_id='$empId' AND status='completed'")->fetch_assoc()['total'];
$pendingTasks = $totalTasks - $completedTasks;

$salaryCredited = $conn->query("SELECT SUM(amount) as total FROM salary WHERE employee_id='$empCode'")->fetch_assoc()['total'] ?? 0;

// Attendance from database
$attendanceData = $conn->query("SELECT status, COUNT(*) as count FROM attendance WHERE employee_id='$empCode' GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$presentDays = 0;
$absentDays = 0;
$leaveDays = 0;
foreach ($attendanceData as $att) {
    if ($att['status'] === 'Present') $presentDays = (int)$att['count'];
    if ($att['status'] === 'Absent') $absentDays = (int)$att['count'];
    if ($att['status'] === 'Leave') $leaveDays = (int)$att['count'];
}

// Monthly attendance %
$attendanceMonthly = [];
for ($i = 1; $i <= 12; $i++) {
    $present = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE employee_id='$empCode' AND MONTH(date)='$i' AND status='Present'")->fetch_assoc()['total'];
    $totalDays = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE employee_id='$empCode' AND MONTH(date)='$i'")->fetch_assoc()['total'];
    $attendanceMonthly[] = $totalDays > 0 ? round(($present / $totalDays) * 100) : 0;
}

// Skill progress
$skillCompletion = 85;
$skills = [
    "Communication" => 80,
    "Recruitment" => 70,
    "Payroll" => 75,
    "Engagement" => 60,
    "Performance" => 85,
    "Compliance" => 65
];

// Monthly task stats
$tasksPerMonth = [];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
for ($i = 1; $i <= 12; $i++) {
    $tasksPerMonth[] = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE employee_id='$empId' AND MONTH(created_at)='$i'")->fetch_assoc()['total'];
}

// Performance score
$performanceScore = $conn->query("SELECT AVG(rating) as avgRating FROM performance WHERE employee_id='$empId'")->fetch_assoc()['avgRating'];
if (!$performanceScore) $performanceScore = 3.8;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Employee Dashboard | HR Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f4fdf6;
            display: flex;
            min-height: 100vh;
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
        }

        .content {
            padding: 35px;
            flex: 1;
        }

        .welcome {
            text-align: center;
            font-size: 26px;
            font-weight: 700;
            color: #16a085;
            margin-bottom: 25px;
        }

        /* Top Boxes 6 boxes layout */
        .top-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .rectangle {
            background: linear-gradient(90deg, #1abc9c, #16a085);
            color: #fff;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transition: .3s;
        }

        .rectangle h3 {
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .rectangle p {
            font-size: 20px;
            font-weight: 700;
        }

        .rectangle:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
        }

        /* Charts */
        .cards-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 35px;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            transition: .3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            color: #16a085;
            margin-bottom: 10px;
        }

        .chart-container {
            width: 100%;
            height: 220px;
        }

        footer {
            width: 100%;
            text-align: center;
            background: linear-gradient(90deg, #1abc9c, #16a085);
            color: #fff;
            padding: 16px;
            border-radius: 25px 25px 0 0;
            font-weight: 700;
        }

        @media(max-width:950px) {
            .top-row {
                grid-template-columns: 1fr 1fr;
            }

            .cards-row {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width:600px) {
            .top-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="profile">
            <div class="profile-pic"><img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Employee"></div>
            <h3><?= htmlspecialchars($empData['username']) ?></h3>
            <p><?= !empty($empData['email']) ? htmlspecialchars($empData['email']) : 'Email not available'; ?></p>
        </div>
        <!-- Include Font Awesome CDN in <head> -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<hr class="divider">
<a href="employee_dashboard.php" class="sidebar-link active">
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
<a href="attendance.php" class="sidebar-link">
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
            <h1>Employee Dashboard</h1>
            <div class="top-info">
                <span id="currentDateTime"></span>
                <a href="../auth/logout.php"><button class="logout-btn">Logout</button></a>
            </div>
        </div>

        <div class="content">
            <div class="welcome">Welcome, <?= htmlspecialchars($empData['username']) ?>!</div>

            <!-- Top 6 Boxes -->
            <div class="top-row">
                <div class="rectangle">
                    <h3>Attendance</h3>
                    <p><?= $presentDays ?></p>
                </div>
                <div class="rectangle">
                    <h3>Salary Credited</h3>
                    <p>₹<?= number_format($salaryCredited, 2) ?></p>
                </div>
                <div class="rectangle">
                    <h3>Performance</h3>
                    <p><?= round($performanceScore, 1) ?></p>
                </div>
                <div class="rectangle">
                    <h3>Total Tasks</h3>
                    <p><?= $totalTasks ?></p>
                </div>
                <div class="rectangle">
                    <h3>Completed Tasks</h3>
                    <p><?= $completedTasks ?></p>
                </div>
                <div class="rectangle">
                    <h3>Pending Tasks</h3>
                    <p><?= $pendingTasks ?></p>
                </div>
            </div>

            <!-- Charts -->
            <div class="cards-row">
                <div class="card">
                    <h3>Task Progress</h3><canvas id="lineChart" class="chart-container"></canvas>
                </div>
                <div class="card">
                    <h3>Monthly Tasks</h3><canvas id="barChart" class="chart-container"></canvas>
                </div>
            </div>

            <div class="cards-row">
                <div class="card">
                    <h3>Training Progress</h3><canvas id="radarChart" class="chart-container"></canvas>
                </div>
                <div class="card">
                    <h3 style="text-align:center; margin-bottom:20px;">Attendance Overview</h3>
                    <canvas id="attendanceLineChart" style="width:100%; height:350px;"></canvas>
                    <div style="width:150px; height:200px; margin:0 auto;">
    <canvas id="attendanceDonutChart"></canvas>
</div>

                </div>
            </div>

        </div>
        <footer>© 2025 HR Management | Designed by Pulkit Krishna</footer>
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

        const months = <?= json_encode($months) ?>;
        const tasks = <?= json_encode($tasksPerMonth) ?>;
        const performance = <?= $performanceScore ?>;
        const skillLabels = <?= json_encode(array_keys($skills)) ?>;
        const skillValues = <?= json_encode(array_values($skills)) ?>;
        const presentDays = <?= $presentDays ?>;
        const absentDays = <?= $absentDays ?>;
        const attendanceMonthly = <?= json_encode($attendanceMonthly) ?>;

        // Task Progress Line Chart
        new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Tasks',
                    data: tasks,
                    backgroundColor: 'rgba(26,188,156,0.2)',
                    borderColor: '#16a085',
                    borderWidth: 2,
                    tension: 0.3
                }]
            },
            options: {
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

        // Monthly Tasks Bar Chart
        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    data: tasks,
                    backgroundColor: 'rgba(26,188,156,0.7)',
                    borderColor: '#16a085',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Training/Radar Chart
        new Chart(document.getElementById('radarChart'), {
            type: 'radar',
            data: {
                labels: skillLabels,
                datasets: [{
                    label: 'Skill Level',
                    data: skillValues,
                    backgroundColor: 'rgba(26,188,156,0.2)',
                    borderColor: '#16a085',
                    borderWidth: 2,
                    pointBackgroundColor: '#16a085'
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Attendance Monthly Line Chart
        new Chart(document.getElementById('attendanceLineChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Attendance (%)',
                    data: attendanceMonthly,
                    backgroundColor: 'rgba(26,188,156,0.2)',
                    borderColor: '#16a085',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Attendance (%)'
                        }
                    }
                }
            }
        });

        // Small Donut: Present vs Absent
        new Chart(document.getElementById('attendanceDonutChart'), {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [presentDays, absentDays],
                    backgroundColor: ['#16a085', '#ff7675']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10
                        }
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>

</html>