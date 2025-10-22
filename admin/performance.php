<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

include '../db/db.php';

$adminId = $_SESSION['user_id'];
$admin = $conn->query("SELECT * FROM users WHERE id='$adminId' AND role='admin'")->fetch_assoc();
$employees = $conn->query("SELECT id, username FROM users WHERE role='employee'");

$msg = '';
if (isset($_POST['submit'])) {
    $employee_id = $_POST['employee_id'];
    $week = $_POST['week'];
    $rating = (int)$_POST['rating'];
    $feedback = $_POST['feedback'];

    $allowedRatings = [1, 2, 3, 4, 5];
    if (!in_array($rating, $allowedRatings)) {
        $msg = "Invalid rating selected.";
    } else {
        $stmt = $conn->prepare("INSERT INTO performance (employee_id, week, rating, feedback) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $employee_id, $week, $rating, $feedback);
        $msg = $stmt->execute() ? "✅ Performance added successfully!" : "❌ Error: " . $stmt->error;
    }
}

$performanceData = $conn->query("
    SELECT p.*, u.username 
    FROM performance p 
    JOIN users u ON p.employee_id = u.id 
    ORDER BY p.week DESC
");

$chartLabels = [];
$chartData = [];
$chartQuery = $conn->query("
    SELECT u.username, p.week, AVG(p.rating) as avg_rating 
    FROM performance p 
    JOIN users u ON p.employee_id = u.id 
    GROUP BY u.username, p.week 
    ORDER BY p.week ASC
");

while ($row = $chartQuery->fetch_assoc()) {
    $chartLabels[] = "Week " . $row['week'] . " - " . $row['username'];
    $chartData[] = round($row['avg_rating'], 2);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin | Performance Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Orbitron&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
/* Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: sans-serif;
}

body {
  
  display: flex;
  min-height: 100vh;
  background: #fff8f0;
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
    transition: all 0.3s ease;
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

/* Main layout */
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

/* Cards */
.form-box,
.chart-card,
.table-box {
  background: #fff;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
  margin-bottom: 40px;
  max-width: 900px;
  margin-left: auto;
  margin-right: auto;
}

/* Floating label effect */
.form-group {
  position: relative;
  margin-top: 25px;
}
.form-group input,
.form-group select,
.form-group textarea {
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
.form-group input:focus ~ label,
.form-group input:not(:placeholder-shown) ~ label,
.form-group textarea:focus ~ label,
.form-group textarea:not(:placeholder-shown) ~ label,
.form-group select:focus ~ label,
.form-group select:not([value=""]) ~ label {
  top: -8px;
  left: 10px;
  color: #ff7b00;
  font-size: 13px;
}

button {
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
button:hover {
  background: #e86c00;
  transform: translateY(-2px);
}

.msg {
  text-align: center;
  font-weight: bold;
  margin-bottom: 20px;
  color: #e86c00;
}

/* Table */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}
th {
  background: #ff7b00;
  color: #fff;
  padding: 12px;
}
td {
  background: #fff3e0;
  padding: 12px;
  text-align: center;
  transition: all 0.3s ease;
}
tr:hover td {
  background: #ffe6cc;
  transform: scale(1.02);
  box-shadow: 0 2px 10px rgba(0,0,0,0.15);
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
canvas {
  max-width: 100%;
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
        <h3><?= htmlspecialchars($admin['username'] ?? '') ?></h3>
        <p><?= htmlspecialchars($admin['email'] ?? 'Email not available') ?></p>
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
<a href="performance.php" class="active">
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

<!-- Main Section -->
<div class="main">
    <div class="navbar">
        <h1>HR Management Dashboard</h1>
        <div class="top-info" id="currentDateTime"></div>
    </div>
    <div class="content">
        <div class="welcome-card">
            <h2>Performance</h2>
            <p>Add Employee and see performance records.</p>
        </div>

        <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>

        <div class="form-box">
            <form method="POST">
                <div class="form-group">
                    <select name="employee_id" required>
                        <option value="">-- Select Employee --</option>
                        <?php while ($emp = $employees->fetch_assoc()): ?>
                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['username']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <label>Employee</label>
                </div>

                <div class="form-group">
                    <input type="number" name="week" placeholder=" " required>
                    <label>Week</label>
                </div>

                <div class="form-group">
                    <select name="rating" required>
                        <option value="">-- Select Rating --</option>
                        <option value="5">🟢 Excellent</option>
                        <option value="4">🟡 Good</option>
                        <option value="3">🟠 Average</option>
                        <option value="2">🔴 Poor</option>
                        <option value="1">❌ Unsatisfactory</option>
                    </select>
                    <label>Rating</label>
                </div>

                <div class="form-group">
                    <textarea name="feedback" rows="4" placeholder=" " required></textarea>
                    <label>Feedback</label>
                </div>

                <button type="submit" name="submit">Submit Performance</button>
            </form>
        </div>

        <div class="chart-card">
            <h2 style="color:#ff7b00;">Average Ratings by Week</h2>
            <canvas id="ratingChart"></canvas>
        </div>

        <div class="table-box">
            <h2 style="color:#ff7b00;">All Performance Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Week</th>
                        <th>Rating</th>
                        <th>Feedback</th>
                        <th>Evaluated At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $performanceData->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= $row['week'] ?></td>
                            <td><?= $row['rating'] ?></td>
                            <td><?= htmlspecialchars($row['feedback']) ?></td>
                            <td><?= date('d M Y, h:i A', strtotime($row['evaluated_at'])) ?></td>
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

new Chart(document.getElementById('ratingChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Average Rating',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: '#ff7b00',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: true, position: 'top' } },
        scales: { y: { beginAtZero: true, max: 5 } }
    }
});
</script>
</body>
</html>
