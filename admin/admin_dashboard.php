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

// Stats
$totalEmployees = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='employee'")->fetch_assoc()['total'];
$totalAdmins = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='admin'")->fetch_assoc()['total'];
$totalPositions = $conn->query("SELECT COUNT(DISTINCT employee_id) as total FROM users WHERE role='employee'")->fetch_assoc()['total'];

// Performance Ratings Distribution
$ratingCounts = [];
for ($i = 1; $i <= 5; $i++) {
    $ratingCounts[] = $conn->query("SELECT COUNT(*) as total FROM performance WHERE rating = $i")->fetch_assoc()['total'];
}
$ratingLabels = ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'];

// Tasks per month
$taskMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$taskCounts = [];
for ($i = 1; $i <= 12; $i++) {
    $taskCounts[] = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE MONTH(created_at) = $i")->fetch_assoc()['total'];
}

// Employee names for Pie chart
$employeeNames = [];
$result = $conn->query("SELECT username FROM users WHERE role='employee'");
while ($row = $result->fetch_assoc()) {
    $employeeNames[] = $row['username'];
}

// Example monthly employee data
$monthlyEmployees = [5, 10, 15, 20, 25, 30];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];

// Admin reminders
$reminders = [
    "Approve new leave requests",
    "Update employee records",
    "Check pending reports",
    "Schedule team meeting"
];

// --- Payment Analytics Data ---
$totalPayments = $conn->query("SELECT SUM(amount) as total FROM salary")->fetch_assoc()['total'] ?? 0;
$totalPaidEmployees = $conn->query("SELECT COUNT(DISTINCT employee_id) as total FROM salary")->fetch_assoc()['total'] ?? 0;
$totalPendingPayments = $totalEmployees - $totalPaidEmployees;

// Payment over months
$paymentMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$paymentCounts = [];
for ($i = 1; $i <= 12; $i++) {
    $paymentCounts[] = $conn->query("SELECT SUM(amount) as total FROM salary WHERE MONTH(credited_date) = $i")->fetch_assoc()['total'] ?? 0;
}

// --- Attendance Data (New Graph) ---
$attendanceMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$attendanceCounts = [];
for ($i = 1; $i <= 12; $i++) {
    $attendanceCounts[] = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE MONTH(date) = $i AND status='present'")->fetch_assoc()['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | HR Management</title>
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

        .sidebar a.active {
            background: rgba(255, 255, 255, 0.3);
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

        .divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.50);
            margin: 4px 0;
        }

        /* Main */
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
            font-size: 26px;
        }

        .navbar .top-info {
            display: flex;
            align-items: center;
            gap: 20px;
            font-weight: 600;
            font-size: 14px;
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

        .content {
            padding: 30px;
            flex: 1;
        }

        .welcome {
            font-size: 26px;
            color: #ff7b00;
            font-weight: 700;
            text-align: center;
            margin-bottom: 25px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            background: linear-gradient(135deg, #ff9d2f, #ff6a00);
            color: #fff;
            border-radius: 25px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transition: transform .3s, box-shadow .3s;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
        }

        .card h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .card p {
            font-size: 28px;
            font-weight: 700;
        }

        .progress-bar {
            background: rgba(255, 255, 255, 0.3);
            height: 8px;
            border-radius: 5px;
            margin-top: 20px;
            overflow: hidden;
        }

        .progress-bar div {
            height: 100%;
            background: #fff;
            border-radius: 5px;
            transition: width 1s ease;
        }

        /* Charts */
        .charts-section {
            margin-top: 40px;
        }

        .charts-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 40px;
        }

        .chart-card {
            background: #fff;
            border-radius: 25px;
            padding: 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            transition: .3s;
        }

        .chart-card:hover {
            transform: translateY(-5px);
        }

        .chart-card h2 {
            font-size: 20px;
            color: #ff7b00;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .chart-container {
            width: 100%;
            height: 350px;
        }

        .reminders {
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .reminder-card {
            background: #ffeecf;
            padding: 15px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
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

        @media(max-width:900px) {
            .charts-row {
                grid-template-columns: 1fr;
            }

            .reminders {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile">
            <div class="profile-pic"><img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Admin"></div>
            <h3><?= $adminData['username'] ?></h3>
            <p><?= $adminData['email'] ?></p>
        </div>
       <hr class="divider">
<a href="admin_dashboard.php" class="active">
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

    <!-- Main -->
    <div class="main">
        <div class="navbar">
            <h1>HR Management Dashboard</h1>
            <div class="top-info">
                <span id="currentDateTime"></span>
                <a href="../auth/logout.php"><button class="logout-btn">Logout</button></a>
            </div>
        </div>

        <div class="content">
            <div class="welcome">Welcome, <?= $adminData['username'] ?>!</div>

            <!-- Top Summary Cards -->
            <div class="cards">
                <div class="card" style="background:linear-gradient(135deg,#ff9d2f,#ff6a00);">
                    <h3>Total Payments</h3>
                    <p>₹ <?= number_format($totalPayments, 2) ?></p>
                </div>
                <div class="card" style="background:linear-gradient(135deg,#ff7b00,#ffa64d);">
                    <h3>Employees Paid</h3>
                    <p><?= $totalPaidEmployees ?></p>
                </div>
                <div class="card" style="background:linear-gradient(135deg,#ff7300,#ffb84d);">
                    <h3>Pending Payments</h3>
                    <p><?= $totalPendingPayments ?></p>
                </div>
            </div>

            <!-- Dashboard Cards -->
            <div class="cards">
                <div class="card">
                    <h3>Total Employees</h3>
                    <p class="counter" data-target="<?= $totalEmployees ?>">0</p>
                    <div class="progress-bar">
                        <div style="width:<?= min($totalEmployees, 100) ?>%"></div>
                    </div>
                </div>
                <div class="card">
                    <h3>Total Admins</h3>
                    <p class="counter" data-target="<?= $totalAdmins ?>">0</p>
                    <div class="progress-bar">
                        <div style="width:<?= min($totalAdmins * 10, 100) ?>%"></div>
                    </div>
                </div>
                <div class="card">
                    <h3>Total Positions</h3>
                    <p class="counter" data-target="<?= $totalPositions ?>">0</p>
                    <div class="progress-bar">
                        <div style="width:<?= min($totalPositions * 15, 100) ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-section">
                <div class="charts-row">
                    <div class="chart-card">
                        <h2>New Employees per Month</h2>
                        <div class="chart-container"><canvas id="employeeChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <h2>Employees Distribution</h2>
                        <div class="chart-container"><canvas id="roleChart"></canvas></div>
                    </div>
                </div>

                <div class="charts-row">
                    <div class="chart-card">
                        <h2>Performance Ratings</h2>
                        <div class="chart-container"><canvas id="performanceChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <h2>Tasks Assigned per Month</h2>
                        <div class="chart-container"><canvas id="taskChart"></canvas></div>
                    </div>
                </div>

                <div class="charts-row">
                    <div class="chart-card">
                        <h2>Payments Made per Month</h2>
                        <div class="chart-container"><canvas id="paymentChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <h2>Attendance per Month</h2>
                        <div class="chart-container"><canvas id="attendanceChart"></canvas></div>
                    </div>
                </div>
            </div>

            <!-- Reminders -->
            <div class="reminders">
                <?php foreach ($reminders as $reminder): ?>
                    <div class="reminder-card">📌 <?= $reminder ?></div>
                <?php endforeach; ?>
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

        document.querySelectorAll('.counter').forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const inc = target / 80;
                if (count < target) {
                    counter.innerText = Math.ceil(count + inc);
                    setTimeout(updateCount, 20);
                } else counter.innerText = target;
            }
            updateCount();
        });

        function createGradient(ctx, color1, color2) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, color1);
            gradient.addColorStop(1, color2);
            return gradient;
        }

        // --- CHARTS ---

        const empCtx = document.getElementById('employeeChart').getContext('2d');
        new Chart(empCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [{
                    label: 'New Employees per Month',
                    data: <?= json_encode($monthlyEmployees) ?>,
                    backgroundColor: createGradient(empCtx, 'rgba(255,155,0,0.4)', 'rgba(255,120,0,0.1)'),
                    borderColor: '#ff7300',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.45,
                    pointBackgroundColor: '#ff7b00',
                    pointRadius: 6,
                    pointHoverRadius: 8
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
                },
                animation: {
                    duration: 1800,
                    easing: 'easeOutQuart'
                }
            }
        });

        const roleCtx = document.getElementById('roleChart').getContext('2d');
        new Chart(roleCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($employeeNames) ?>,
                datasets: [{
                    label: 'Employee Names',
                    data: Array(<?= count($employeeNames) ?>).fill(1),
                    backgroundColor: ['#FFB74D', '#FFA726', '#FF9800', '#FB8C00', '#F57C00', '#EF6C00', '#E65100'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: '#ff7b00',
                        titleColor: '#fff',
                        bodyColor: '#fff'
                    }
                },
                cutout: '60%',
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });

        const perfCtx = document.getElementById('performanceChart').getContext('2d');
        new Chart(perfCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($ratingLabels) ?>,
                datasets: [{
                    label: 'Employees',
                    data: <?= json_encode($ratingCounts) ?>,
                    backgroundColor: ['#ff9f43', '#ff7b00', '#ffb84d', '#ffa726', '#ffc107'],
                    borderRadius: 10,
                    borderSkipped: false
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
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutBounce'
                }
            }
        });

        const taskCtx = document.getElementById('taskChart').getContext('2d');
        new Chart(taskCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($taskMonths) ?>,
                datasets: [{
                    label: 'Tasks Assigned',
                    data: <?= json_encode($taskCounts) ?>,
                    borderColor: '#f76c0f',
                    backgroundColor: createGradient(taskCtx, 'rgba(255,145,0,0.3)', 'rgba(255,70,0,0.05)'),
                    tension: 0.35,
                    pointBackgroundColor: '#ff7b00',
                    borderWidth: 3,
                    fill: true
                }]
            },
            options: {
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
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                }
            }
        });

        const payCtx = document.getElementById('paymentChart').getContext('2d');
        new Chart(payCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($paymentMonths) ?>,
                datasets: [{
                    label: 'Payments (₹)',
                    data: <?= json_encode($paymentCounts) ?>,
                    backgroundColor: createGradient(payCtx, 'rgba(255,140,0,0.9)', 'rgba(255,200,100,0.6)'),
                    borderColor: '#ff7b00',
                    borderWidth: 1,
                    borderRadius: 8
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
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutBack'
                }
            }
        });

        const attCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($attendanceMonths) ?>,
                datasets: [{
                    label: 'Present Employees',
                    data: <?= json_encode($attendanceCounts) ?>,
                    backgroundColor: createGradient(attCtx, 'rgba(255,215,64,0.4)', 'rgba(255,150,0,0.1)'),
                    borderColor: '#ffab03',
                    borderWidth: 3,
                    tension: 0.4,
                    pointBackgroundColor: '#ff9800',
                    fill: true
                }]
            },
            options: {
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
                },
                animation: {
                    duration: 1800,
                    easing: 'easeInOutQuart'
                }
            }
        });
    </script>
</body>

</html>