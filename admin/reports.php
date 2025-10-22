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

// Summary counts
$totalEmployees = $conn->query("SELECT * FROM employees")->num_rows;
$totalAdmins = $conn->query("SELECT * FROM users WHERE role='admin'")->num_rows;
$totalEmployeesUser = $conn->query("SELECT * FROM users WHERE role='employee'")->num_rows;
$totalDepartments = $conn->query("SELECT DISTINCT position FROM employees")->num_rows;

// Gender distribution (normalized)
$genderResult = $conn->query("
    SELECT LOWER(TRIM(gender)) as gender, COUNT(*) as count 
    FROM employees 
    GROUP BY LOWER(TRIM(gender))
");

$genders = [];
$genderCounts = [];
while ($row = $genderResult->fetch_assoc()) {
    $genderLabel = ucfirst($row['gender']); // e.g., 'male' -> 'Male'
    $genders[] = $genderLabel;
    $genderCounts[] = $row['count'];
}

// Employees per department
$deptResult = $conn->query("SELECT position, COUNT(*) as count FROM employees GROUP BY position");
$departments = [];
$deptCounts = [];
while ($row = $deptResult->fetch_assoc()) {
    $departments[] = $row['position'];
    $deptCounts[] = $row['count'];
}

// Age distribution
$ageResult = $conn->query("SELECT age, COUNT(*) as count FROM employees GROUP BY age ORDER BY age ASC");
$ages = [];
$ageCounts = [];
while ($row = $ageResult->fetch_assoc()) {
    $ages[] = $row['age'];
    $ageCounts[] = $row['count'];
}

// Recent joiners over time (monthly count)
$joinerResult = $conn->query("SELECT DATE_FORMAT(date_of_joining,'%Y-%m') as month, COUNT(*) as count FROM employees GROUP BY month ORDER BY month ASC");
$joinerMonths = [];
$joinerCounts = [];
while ($row = $joinerResult->fetch_assoc()) {
    $joinerMonths[] = $row['month'];
    $joinerCounts[] = $row['count'];
}

// Recent joiners
$recentJoiners = $conn->query("SELECT name, position, date_of_joining FROM employees ORDER BY date_of_joining DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Analytics | HR Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* --- CSS same as your previous design --- */
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

        .divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.50);
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

        .reports-section {
            padding: 30px;
            
        }

        .reports-section h2 {
            font-family: sans-serif;
            color: #ff7b00;
            text-align: center;
            margin-bottom: 25px;
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

        .recent-joiners {
            max-width: 900px;
            margin: 0 auto 40px auto;
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .recent-joiners h3 {
            font-family: 'Orbitron', sans-serif;
            color: #ff7b00;
            text-align: center;
            margin-bottom: 15px;
        }

        .recent-joiners table {
            width: 100%;
            border-collapse: collapse;
        }

        .recent-joiners th,
        .recent-joiners td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .recent-joiners th {
            background: #ff7b00;
            color: #fff;
        }

        .recent-joiners tr:hover {
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
            <div class="profile-pic"><img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Employee"></div>
            <h3><?= $adminData['username'] ?></h3>
            <p><?= $adminData['email'] ?></p>
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
<a href="reports.php" class="active">
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

    <div class="main">
        <div class="navbar">
            <h1>HR Management Dashboard</h1><span id="currentDateTime"></span>
        </div>

        <div class="content">  
            <div class="welcome-card">
                <h2>Analytics Overview</h2>
                <p> Visulaize growth,Monitor growth,and analyze data</p>
            </div>
             

            <div class="reports-section">
            
                <div class="report-cards">
                    <div class="report-card">
                        <h3>Total Employees</h3>
                        <p><?= $totalEmployees ?></p>
                    </div>
                    <div class="report-card">
                        <h3>Total Admins</h3>
                        <p><?= $totalAdmins ?></p>
                    </div>
                    <div class="report-card">
                        <h3>Total Users</h3>
                        <p><?= $totalEmployeesUser ?></p>
                    </div>
                    <div class="report-card">
                        <h3>Departments</h3>
                        <p><?= $totalDepartments ?></p>
                    </div>
                </div>

                <div class="charts-wrapper">
                    <div class="chart-card">
                        <h3>Gender Distribution</h3>
                        <p>Proportion of male, female, and other employees.</p><canvas id="genderChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3>Employees per Department</h3>
                        <p>Distribution of employees across positions.</p><canvas id="deptChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3>Age Distribution</h3>
                        <p>Number of employees per age.</p><canvas id="ageChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3>Recent Joiners Over Time</h3>
                        <p>Employees joined month-wise.</p><canvas id="joinerChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3>Admin vs Employee</h3>
                        <p>Comparison of admin users vs employee users.</p><canvas id="roleChart"></canvas>
                    </div>
                </div>

                <div class="recent-joiners">
                    <h3>Recent Joiners</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Date of Joining</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $recentJoiners->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['name'] ?></td>
                                    <td><?= $row['position'] ?></td>
                                    <td><?= $row['date_of_joining'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <footer>© 2025 HR Management System | Designed by Pulkit Krishna</footer>
    </div>

    <script>
        // Current Date & Time
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

        // Gender Chart
        new Chart(document.getElementById('genderChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($genders) ?>,
                datasets: [{
                    data: <?= json_encode($genderCounts) ?>,
                    backgroundColor: ['#ff7b00', '#ffb84d', '#ffa500'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        enabled: true
                    }
                }
            }
        });

        // Department Chart
        new Chart(document.getElementById('deptChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($departments) ?>,
                datasets: [{
                    label: 'Employees per Department',
                    data: <?= json_encode($deptCounts) ?>,
                    backgroundColor: '#ff7b00'
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

        // Age Chart
        new Chart(document.getElementById('ageChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($ages) ?>,
                datasets: [{
                    label: 'Employees Age Distribution',
                    data: <?= json_encode($ageCounts) ?>,
                    backgroundColor: '#ffb84d'
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

        // Recent Joiners Over Time
        new Chart(document.getElementById('joinerChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($joinerMonths) ?>,
                datasets: [{
                    label: 'Joiners per Month',
                    data: <?= json_encode($joinerCounts) ?>,
                    borderColor: '#ff7b00',
                    backgroundColor: 'rgba(255,123,0,0.2)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
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

        // Admin vs Employee
        new Chart(document.getElementById('roleChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Admins', 'Employees'],
                datasets: [{
                    data: [<?= $totalAdmins ?>, <?= $totalEmployeesUser ?>],
                    backgroundColor: ['#ff7b00', '#ffb84d'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        enabled: true
                    }
                }
            }
        });
    </script>
</body>

</html>