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

// =====================
// Handle Add/Edit Employee
// =====================
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

    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        $editId = intval($_POST['edit_id']);
        $stmt = $conn->prepare("UPDATE employees SET name=?, email=?, phone=?, age=?, gender=?, address=?, qualification=?, position=?, date_of_joining=? WHERE id=?");
        $stmt->bind_param("sssiissssi", $name, $email, $phone, $age, $gender, $address, $qualification, $position, $doj, $editId);
        if ($stmt->execute()) {
            $password = $_POST['password'];
            if (!empty($password)) {
                $password = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("UPDATE users SET username=?, password=?, role=? WHERE employee_id=?");
                $stmt2->bind_param("sssi", $username, $password, $role, $editId);
            } else {
                $stmt2 = $conn->prepare("UPDATE users SET username=?, role=? WHERE employee_id=?");
                $stmt2->bind_param("ssi", $username, $role, $editId);
            }
            $stmt2->execute();
            $msg = "Employee updated successfully!";
        } else {
            $msg = "Error: " . $conn->error;
        }
    } else {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO employees (name,email,phone,age,gender,address,qualification,position,date_of_joining) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssiissss", $name, $email, $phone, $age, $gender, $address, $qualification, $position, $doj);
        if ($stmt->execute()) {
            $employee_id = $stmt->insert_id;
            $stmt2 = $conn->prepare("INSERT INTO users (username,password,role,employee_id) VALUES (?,?,?,?)");
            $stmt2->bind_param("sssi", $username, $password, $role, $employee_id);
            $stmt2->execute();
            $msg = "Employee added successfully!";
        } else {
            $msg = "Error: " . $conn->error;
        }
    }
}

// =====================
// Handle Soft Delete Employee
// =====================
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']); // employee id
    $stmt = $conn->prepare("UPDATE employees SET deleted_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $delId);
    $stmt->execute();
    $msg = "Employee deleted (soft delete).";
}

// =====================
// Fetch employee for edit
// =====================
$editEmployee = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT e.*, u.username, u.role FROM employees e LEFT JOIN users u ON u.employee_id=e.id WHERE e.id=? AND e.deleted_at IS NULL");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editEmployee = $stmt->get_result()->fetch_assoc();
}

// =====================
// Fetch all active employees
// =====================
$employees = $conn->query("
    SELECT e.id, e.name, e.email, e.phone, e.position, u.role
    FROM employees e 
    LEFT JOIN users u ON u.employee_id = e.id 
    WHERE e.deleted_at IS NULL
    ORDER BY e.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add/Edit Employee | HR Management</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #f5f5f5;
    display: flex;
    min-height: 100vh;
    color: #333;
}
.sidebar a i {
    margin-right: 10px;
    color: #fff; /* example */
}

/* ==================== Sidebar ==================== */
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

/* ==================== Main Area ==================== */
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
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
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
    box-shadow: 0 0 10px rgba(0,0,0,0.2);
}

.content {
    padding: 30px;
    flex: 1;
}

/* ==================== Welcome Card ==================== */
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

/* ==================== Form ==================== */
.signup-form {
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    max-width: 900px;
    margin: 0 auto 40px auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.signup-form h2 {
    grid-column: 1 / 3;
    text-align: center;
    margin-bottom: 25px;
    color: #ff7b00;
    font-family: 'Orbitron', sans-serif;
}

.input-group {
    position: relative;
}

.input-group input,
.input-group select {
    width: 100%;
    padding: 12px 15px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
    background: transparent;
    transition: 0.3s;
}

.input-group label {
    position: absolute;
    top: 12px;
    left: 15px;
    pointer-events: none;
    color: #aaa;
    font-size: 14px;
    transition: 0.3s;
}

.input-group input:focus + label,
.input-group input:not(:placeholder-shown) + label,
.input-group select:focus + label,
.input-group select:not([value=""]) + label {
    top: -10px;
    left: 10px;
    font-size: 12px;
    color: #ff7b00;
    background: #fff;
    padding: 0 5px;
}

.signup-form input[type="submit"] {
    grid-column: 1 / 3;
    width: 100%;
    background: #ff7b00;
    color: #fff;
    border: none;
    font-weight: 700;
    padding: 18px;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.3s;
}

.signup-form input[type="submit"]:hover {
    background: #ff9d2f;
}

/* ==================== Table ==================== */
.table-wrapper {
    background: #fff;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    max-width: 1000px;
    margin: 0 auto;
}

.table-wrapper h2 {
    text-align: center;
    color: #ff7b00;
    font-family: 'Orbitron', sans-serif;
    margin-bottom: 20px;
}

.table-wrapper table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.table-wrapper th,
.table-wrapper td {
    padding: 12px;
    text-align: left;
}

.table-wrapper th {
    background: #ff7b00;
    color: #fff;
    text-transform: uppercase;
}

.table-wrapper tr:nth-child(even) {
    background: #fff2e6;
}

.table-wrapper tr:hover {
    background: #ffe6cc;
    transition: 0.3s;
}

/* ==================== Buttons ==================== */
.btn {
    padding: 5px 10px;
    border: none;
    border-radius: 5px;
    color: #fff;
    cursor: pointer;
    transition: 0.3s;
    text-decoration: none;
    font-size: 13px;
    margin-right: 5px;
}

.btn-edit {
    background: #16a085;
}

.btn-edit:hover {
    background: #1abc9c;
}

.btn-delete {
    background: #e74c3c;
}

.btn-delete:hover {
    background: #ff7675;
}

/* ==================== Footer ==================== */
footer {
    width: 100%;
    text-align: center;
    background: linear-gradient(90deg, #ff7b00, #ffb84d);
    color: #fff;
    padding: 14px;
    border-radius: 25px 25px 0 0;
    font-weight: 600;
}

/* ==================== Responsive ==================== */
@media (max-width: 900px) {
    .signup-form {
        grid-template-columns: 1fr;
        padding: 25px;
    }
    .table-wrapper {
        padding: 15px;
    }
}
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
<a href="admin_dashboard.php" >
    <i class="fas fa-tachometer-alt"></i> Dashboard
</a>
<hr class="divider">
<a href="add_employee.php" class="active">
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

<div class="main">
<div class="navbar">
<h1>HR Management Dashboard</h1>
<div class="top-info"><span id="currentDateTime"></span></div>
</div>

<div class="content">
<div class="welcome-card">
<h2><?= $editEmployee ? "Edit Employee" : "Add Employee" ?></h2>
<p><?= $editEmployee ? "Update Employee Info" : "Add a new Employee" ?></p>
</div>

<?php if ($msg) echo "<p style='color:green;margin-bottom:15px;text-align:center;'>$msg</p>"; ?>

<form method="POST" class="signup-form">
<?php if($editEmployee): ?>
<input type="hidden" name="edit_id" value="<?= $editEmployee['id'] ?>">
<?php endif; ?>
<div class="input-group"><input type="text" name="name" required placeholder=" " value="<?= $editEmployee['name'] ?? '' ?>"><label>Full Name</label></div>
<div class="input-group"><input type="email" name="email" required placeholder=" " value="<?= $editEmployee['email'] ?? '' ?>"><label>Email</label></div>
<div class="input-group"><input type="text" name="phone" required placeholder=" " value="<?= $editEmployee['phone'] ?? '' ?>"><label>Phone</label></div>
<div class="input-group"><input type="number" name="age" required placeholder=" " value="<?= $editEmployee['age'] ?? '' ?>"><label>Age</label></div>
<div class="input-group"><select name="gender" required>
<option value="" disabled <?= !isset($editEmployee) ? 'selected' : '' ?>></option>
<option value="Male" <?= ($editEmployee['gender'] ?? '')=='Male'?'selected':'' ?>>Male</option>
<option value="Female" <?= ($editEmployee['gender'] ?? '')=='Female'?'selected':'' ?>>Female</option>
<option value="Other" <?= ($editEmployee['gender'] ?? '')=='Other'?'selected':'' ?>>Other</option>
</select><label>Gender</label></div>
<div class="input-group"><input type="text" name="address" required placeholder=" " value="<?= $editEmployee['address'] ?? '' ?>"><label>Address</label></div>
<div class="input-group"><input type="text" name="qualification" required placeholder=" " value="<?= $editEmployee['qualification'] ?? '' ?>"><label>Qualification</label></div>
<div class="input-group"><input type="text" name="position" required placeholder=" " value="<?= $editEmployee['position'] ?? '' ?>"><label>Position</label></div>
<div class="input-group"><input type="date" name="doj" required placeholder=" " value="<?= $editEmployee['date_of_joining'] ?? '' ?>"><label>Date of Joining</label></div>
<div class="input-group"><input type="text" name="username" required placeholder=" " value="<?= $editEmployee['username'] ?? '' ?>"><label>Username</label></div>
<div class="input-group"><input type="password" name="password" placeholder=" "><label>Password (Leave blank to keep same)</label></div>
<div class="input-group"><select name="role" required>
<option value="" disabled <?= !isset($editEmployee) ? 'selected' : '' ?>></option>
<option value="admin" <?= ($editEmployee['role'] ?? '')=='admin'?'selected':'' ?>>Admin</option>
<option value="employee" <?= ($editEmployee['role'] ?? '')=='employee'?'selected':'' ?>>Employee</option>
</select><label>Role</label></div>
<input type="submit" name="submit" value="<?= $editEmployee ? "Update Employee" : "Add Employee" ?>">
</form>

<div class="table-wrapper">
<h2>Employee List</h2>
<table>
<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Phone</th>
<th>Position</th>
<th>Role</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php while ($row = $employees->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['name'] ?></td>
<td><?= $row['email'] ?></td>
<td><?= $row['phone'] ?></td>
<td><?= $row['position'] ?></td>
<td><?= $row['role'] ?></td>
<td>
<a class="btn btn-edit" href="add_employee.php?edit=<?= $row['id'] ?>">Edit</a>
<a class="btn btn-delete" href="add_employee.php?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this employee?');">Delete</a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
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