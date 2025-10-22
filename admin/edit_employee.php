<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

include '../db/db.php';
$msg = "";

// Fetch logged-in admin info
$adminId = $_SESSION['user_id'];
$adminData = $conn->query("SELECT username, email FROM users WHERE id='$adminId' AND role='admin'")->fetch_assoc();

// Fetch employee ID from GET
if(!isset($_GET['id'])){
    header("Location: add_employee.php");
    exit();
}

$empId = intval($_GET['id']);

// Fetch employee and user data
$empData = $conn->query("SELECT e.*, u.username, u.role FROM employees e LEFT JOIN users u ON u.employee_id=e.id WHERE e.id='$empId'")->fetch_assoc();

if (!$empData) {
    header("Location: add_employee.php");
    exit();
}

// Handle form submission
if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $qualification = $_POST['qualification'];
    $position = $_POST['position'];
    $doj = $_POST['doj'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';

    // Update employees table
    $stmt = $conn->prepare("UPDATE employees SET name=?, email=?, phone=?, age=?, gender=?, address=?, qualification=?, position=?, date_of_joining=? WHERE id=?");
    $stmt->bind_param("sssiissssi", $name, $email, $phone, $age, $gender, $address, $qualification, $position, $doj, $empId);
    $stmt->execute();

    // Update users table
    if(!empty($password)){
        $stmt2 = $conn->prepare("UPDATE users SET username=?, password=?, role=? WHERE employee_id=?");
        $stmt2->bind_param("sssi", $username, $password, $role, $empId);
    } else {
        $stmt2 = $conn->prepare("UPDATE users SET username=?, role=? WHERE employee_id=?");
        $stmt2->bind_param("ssi", $username, $role, $empId);
    }
    $stmt2->execute();

    $msg = "Employee updated successfully!";
    
    // Refresh data
    $empData = $conn->query("SELECT e.*, u.username, u.role FROM employees e LEFT JOIN users u ON u.employee_id=e.id WHERE e.id='$empId'")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Employee | HR Management</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@600&display=swap" rel="stylesheet">
<style>
/* Use same CSS as add_employee.php for consistency */
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:#f5f5f5;display:flex;min-height:100vh;color:#333;}
.sidebar{width:250px;background:linear-gradient(200deg,#ff7b00,#ffb84d);display:flex;flex-direction:column;padding:20px 15px;color:#fff;box-shadow:5px 0 25px rgba(0,0,0,0.15);border-radius:0 25px 25px 0;}
.profile{text-align:center;margin-bottom:30px;}
.profile-pic{display:flex;justify-content:center;align-items:center;background:#fff;width:120px;height:120px;border-radius:50%;margin:0 auto 15px auto;box-shadow:0 0 15px rgba(255,255,255,0.3);}
.profile-pic img{width:90px;height:90px;border-radius:50%;}
.profile h3{font-size:20px;font-weight:700;}
.profile p{font-size:14px;color:white;margin-top:5px;}
.sidebar a{position:relative;text-decoration:none;color:#fff;padding:12px 20px;margin:8px 0;border-radius:12px;display:flex;align-items:center;font-weight:600;overflow:hidden;transition:all .3s ease;}
.sidebar a:hover{background:rgba(255,255,255,0.2);transform:translateX(8px);}
.sidebar a.active{background:rgba(255,255,255,0.3);}
.divider{border:none;height:1px;background:rgba(255,255,255,0.50);margin:4px 0;}
.main{flex:1;display:flex;flex-direction:column;width:100%;}
.navbar{width:100%;background:linear-gradient(90deg,#ff7b00,#ffb84d);color:#fff;padding:15px 30px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,0.2);border-radius:0 0 25px 25px;}
.navbar h1{font-family:'Orbitron',sans-serif;font-size:28px;}
.logout-btn{background:#fff;color:#ff7b00;font-weight:700;padding:8px 16px;border:none;border-radius:10px;cursor:pointer;transition:all 0.3s ease;}
.logout-btn:hover{background:#ff7b00;color:#fff;box-shadow:0 0 10px rgba(0,0,0,0.2);}
#currentDateTime{font-weight:600;font-size:14px;}
.content{padding:30px;flex:1;}
.welcome-card{padding:25px;text-align:center;margin-bottom:30px;background:#fff;border-radius:15px;box-shadow:0 6px 20px rgba(0,0,0,0.1);}
.welcome-card h2{color:#ff7b00;font-size:28px;font-weight:700;margin-bottom:10px;}
.welcome-card p{color:#ff9d2f;}
.signup-form{background:#fff;padding:30px;border-radius:15px;box-shadow:0 8px 25px rgba(0,0,0,0.15);max-width:900px;margin:0 auto 40px auto;display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.signup-form h2{grid-column:1/3;text-align:center;margin-bottom:25px;color:#ff7b00;font-family:'Orbitron',sans-serif;}
.input-group{position:relative;}
.input-group input,.input-group select{width:100%;padding:12px 15px;border-radius:8px;border:1px solid #ccc;font-size:14px;background:transparent;transition:0.3s;}
.input-group label{position:absolute;top:12px;left:15px;pointer-events:none;color:#aaa;font-size:14px;transition:0.3s;}
.input-group input:focus+label,
.input-group input:not(:placeholder-shown)+label,
.input-group select:focus+label,
.input-group select:not([value=""])+label{top:-10px;left:10px;font-size:12px;color:#ff7b00;background:#fff;padding:0 5px;}
.signup-form input[type=submit]{grid-column:1/3;width:100%;background:#ff7b00;color:#fff;border:none;font-weight:700;padding:18px;border-radius:10px;cursor:pointer;transition:0.3s;}
.signup-form input[type=submit]:hover{background:#ff9d2f;}
footer{width:100%;text-align:center;background:linear-gradient(90deg,#ff7b00,#ffb84d);color:#fff;padding:14px;border-radius:25px 25px 0 0;font-weight:600;}
@media(max-width:900px){.signup-form{grid-template-columns:1fr;padding:25px;}}
</style>
</head>
<body>
<div class="sidebar">
<div class="profile">
    <div class="profile-pic"><img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Admin"></div>
    <h3><?= $adminData['username'] ?></h3>
    <p><?= $adminData['email'] ?></p>
</div>
<hr class="divider">
<a href="admin_dashboard.php">Dashboard</a>
<hr class="divider">
<a href="add_employee.php">Add Employee</a>
<hr class="divider">
<a href="reports.php">Reports</a>
<hr class="divider">
<a href="tasks.php">Tasks</a>
<hr class="divider">
<a href="performance.php">Performance</a>
<hr class="divider">
<a href="attendance.php">Attendance</a>
<hr class="divider">
<a href="payement.php">Payment</a>
<hr class="divider">
<a href="settings.php">Settings</a>
<hr class="divider">
<a href="../auth/logout.php">Logout</a>
</div>

<div class="main">
<div class="navbar">
<h1>Edit Employee</h1>
<div class="top-info">
<span id="currentDateTime"></span>
</div>
</div>

<div class="content">
<div class="welcome-card">
<h2>Edit Employee Info</h2>
<p>Update employee information below</p>
</div>

<?php if ($msg) echo "<p style='color:green;margin-bottom:15px;text-align:center;'>$msg</p>"; ?>

<form method="POST" class="signup-form">
<div class="input-group"><input type="text" name="name" required placeholder=" " value="<?= htmlspecialchars($empData['name']) ?>"><label>Full Name</label></div>
<div class="input-group"><input type="email" name="email" required placeholder=" " value="<?= htmlspecialchars($empData['email']) ?>"><label>Email</label></div>
<div class="input-group"><input type="text" name="phone" required placeholder=" " value="<?= htmlspecialchars($empData['phone']) ?>"><label>Phone</label></div>
<div class="input-group"><input type="number" name="age" required placeholder=" " value="<?= htmlspecialchars($empData['age']) ?>"><label>Age</label></div>
<div class="input-group">
<select name="gender" required>
<option value="" disabled>Choose Gender</option>
<option value="Male" <?= $empData['gender']=='Male'?'selected':'' ?>>Male</option>
<option value="Female" <?= $empData['gender']=='Female'?'selected':'' ?>>Female</option>
<option value="Other" <?= $empData['gender']=='Other'?'selected':'' ?>>Other</option>
</select>
<label>Gender</label>
</div>
<div class="input-group"><input type="text" name="address" required placeholder=" " value="<?= htmlspecialchars($empData['address']) ?>"><label>Address</label></div>
<div class="input-group"><input type="text" name="qualification" required placeholder=" " value="<?= htmlspecialchars($empData['qualification']) ?>"><label>Qualification</label></div>
<div class="input-group"><input type="text" name="position" required placeholder=" " value="<?= htmlspecialchars($empData['position']) ?>"><label>Position</label></div>
<div class="input-group"><input type="date" name="doj" required placeholder=" " value="<?= htmlspecialchars($empData['date_of_joining']) ?>"><label>Date of Joining</label></div>
<div class="input-group"><input type="text" name="username" required placeholder=" " value="<?= htmlspecialchars($empData['username']) ?>"><label>Username</label></div>
<div class="input-group"><input type="password" name="password" placeholder=" "><label>Password (leave blank to keep unchanged)</label></div>
<div class="input-group">
<select name="role" required>
<option value="admin" <?= $empData['role']=='admin'?'selected':'' ?>>Admin</option>
<option value="employee" <?= $empData['role']=='employee'?'selected':'' ?>>Employee</option>
</select>
<label>Role</label>
</div>
<input type="submit" name="submit" value="Update Employee">
</form>
</div>

<footer>© 2025 HR Management System | Designed by Pulkit Krishna</footer>
</div>

<script>
function updateDateTime(){
const now=new Date();
const options={weekday:'long',year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit'};
document.getElementById('currentDateTime').innerText=now.toLocaleString('en-US',options);
}
setInterval(updateDateTime,1000);
updateDateTime();
</script>
</body>
</html>
