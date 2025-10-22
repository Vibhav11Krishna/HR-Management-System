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
$empData = $conn->query("SELECT username, email FROM users WHERE id='$empId'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Skill Growth & Training | HR Management</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@600&display=swap" rel="stylesheet">
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
            margin: 6px 0;
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
            transition: all .3s;
        }

        .logout-btn:hover {
            background: #16a085;
            color: #fff;
        }

        .content {
            padding: 35px;
            flex: 1;
        }

        .section-title {
            text-align: center;
            font-size: 26px;
            font-weight: 700;
            color: #16a085;
            margin-bottom: 30px;
        }

        /* Skill Grid */
        .skill-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .skill-card {
            background: #fff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: .3s;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .skill-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .skill-card h3 {
            color: #16a085;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .progress-bar {
            background: rgba(22, 160, 133, 0.1);
            height: 15px;
            border-radius: 10px;
            position: relative;
        }

        .progress-bar div {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #1abc9c, #16a085);
            border-radius: 10px;
            text-align: right;
            padding-right: 8px;
            line-height: 15px;
            color: #fff;
            font-weight: 600;
            transition: width 1.5s ease-in-out;
        }
         .top-info {
            font-weight: 600;
        }

        /* Cards Container */
        .cards-container {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            flex: 1;
            transition: .3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            color: #16a085;
            margin-bottom: 10px;
            text-align: center;
        }

        .chart-container {
            height: 260px;
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

        @media(max-width:950px) {
            .skill-grid {
                grid-template-columns: 1fr 1fr;
            }

            .cards-container {
                flex-direction: column;
            }

            .chart-container {
                height: 250px;
            }
        }

        @media(max-width:600px) {
            .skill-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile">
            <div class="profile-pic"><img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Employee"></div>
            <h3><?= htmlspecialchars($empData['username']) ?></h3>
            <p><?= htmlspecialchars($empData['email'] ?? 'Email not available') ?></p>
        </div>
            <hr class="divider">
<a href="employee_dashboard.php" class="sidebar-link ">
    <i class="fas fa-tachometer-alt"></i>&nbsp;&nbsp;&nbsp;Dashboard
</a>
<hr class="divider">
<a href="training.php" class="sidebar-link active">
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

    <!-- Main -->
    <div class="main">
        <div class="navbar">
            <h1>Skill Growth & Training Panel</h1>
            <div class="top-info">
                <span id="currentDateTime"></span>
            </div>
        </div>

        <div class="content">
             <div class="welcome-card">
                <h2>Employee  Skills Growth & Training</h2>
                <p>Updated your skills and training information</p>
            </div>
            <div class="skill-grid">
                <div class="skill-card">
                    <h3>Communication & Soft Skills</h3>
                    <div class="progress-bar">
                        <div data-width="90%">90%</div>
                    </div>
                </div>
                <div class="skill-card">
                    <h3>Recruitment & Interviewing</h3>
                    <div class="progress-bar">
                        <div data-width="75%">75%</div>
                    </div>
                </div>
                <div class="skill-card">
                    <h3>Payroll & Salary Management</h3>
                    <div class="progress-bar">
                        <div data-width="85%">85%</div>
                    </div>
                </div>
                <div class="skill-card">
                    <h3>Employee Engagement</h3>
                    <div class="progress-bar">
                        <div data-width="70%">70%</div>
                    </div>
                </div>
                <div class="skill-card">
                    <h3>Performance Analysis</h3>
                    <div class="progress-bar">
                        <div data-width="80%">80%</div>
                    </div>
                </div>
                <div class="skill-card">
                    <h3>HR Compliance & Policies</h3>
                    <div class="progress-bar">
                        <div data-width="65%">65%</div>
                    </div>
                </div>
            </div>

            <div class="section-title">Training Progress Overview</div>
            <div class="cards-container">
                <div class="card">
                    <h3>Training Sessions Completed</h3><canvas id="trainingChart" class="chart-container"></canvas>
                </div>
                <div class="card">
                    <h3>Skill Level Overview</h3><canvas id="radarChart" class="chart-container"></canvas>
                </div>
            </div>
        </div>

        <footer>© 2025 HR Management | Designed by Pulkit Krishna</footer>
    </div>

    <script>
        // Animate Progress Bars
        document.querySelectorAll('.progress-bar div').forEach(bar => {
            setTimeout(() => {
                bar.style.width = bar.getAttribute('data-width');
            }, 300);
        });

        // Bar Chart
        new Chart(document.getElementById('trainingChart'), {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [{
                    label: 'Sessions Completed',
                    data: [2, 3, 4, 3, 5, 6, 5, 7, 8, 9],
                    backgroundColor: ctx => {
                        const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, '#1abc9c');
                        gradient.addColorStop(1, '#16a085');
                        return gradient;
                    },
                    borderRadius: 6
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

        // Radar Chart
        new Chart(document.getElementById('radarChart'), {
            type: 'radar',
            data: {
                labels: ['Communication', 'Recruitment', 'Payroll', 'Performance', 'Engagement', 'Analytics'],
                datasets: [{
                    label: 'Skill Level',
                    data: [9, 8, 8, 7, 8, 6],
                    backgroundColor: 'rgba(26,188,156,0.3)',
                    borderColor: '#16a085',
                    borderWidth: 2,
                    pointBackgroundColor: '#16a085'
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            stepSize: 2
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>

</html>