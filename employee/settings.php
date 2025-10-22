<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'employee') {
    header("Location: ../auth/login.php");
    exit();
}

include '../db/db.php';

// Fetch logged-in employee info
$employeeId = $_SESSION['user_id'];
$employee = $conn->query("SELECT * FROM users WHERE id='$employeeId' AND role='employee'")->fetch_assoc();

$msg = '';
if (isset($_POST['submit'])) {
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $age = $_POST['age'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';
    $qualification = $_POST['qualification'] ?? '';
    $position = $_POST['position'] ?? '';
    $doj = $_POST['doj'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=?, age=?, gender=?, address=?, qualification=?, position=?, dob=?, username=?, password=? WHERE id=?");
        $stmt->bind_param("sssssssssssi", $name, $email, $phone, $age, $gender, $address, $qualification, $position, $dob, $username, $password_hash, $employeeId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=?, age=?, gender=?, address=?, qualification=?, position=?, dob=?, username=? WHERE id=?");
        $stmt->bind_param("ssssssssssi", $fullname, $email, $phone, $age, $gender, $address, $qualification, $position, $dob, $username, $employeeId);
    }

    if ($stmt->execute()) {
        $msg = "Profile updated successfully!";
        $employee = $conn->query("SELECT * FROM users WHERE id='$employeeId' AND role='employee'")->fetch_assoc();
    } else {
        $msg = "Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Settings | Employee Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <style>
        /* Basic Reset */
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
            transition: 0.3s;
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
            margin: 0 auto 15px;
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
            margin: 4px 0;
        }

        /* Main Layout */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        /* Navbar */
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

        .top-info {
            font-weight: 600;
            font-size: 14px;
        }

        /* Content */
        .content {
            padding: 35px;
            flex: 1;
        }

        /* Welcome Card */
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

        /* Settings Form */
        .settings-form {
            background: #fff;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            max-width: 750px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .settings-form h2 {
            grid-column: 1/3;
            text-align: center;
            margin-bottom: 30px;
            color: #16a085;
            font-family: 'Orbitron', sans-serif;
        }

        .input-group {
            position: relative;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 14px;
            background: transparent;
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

        .input-group input:focus+label,
        .input-group input:not(:placeholder-shown)+label,
        .input-group select:focus+label,
        .input-group select:not([value=""])+label {
            top: -10px;
            left: 10px;
            font-size: 12px;
            color: #16a085;
            background: #fff;
            padding: 0 5px;
        }

        .settings-form input[type=submit] {
            grid-column: 1/3;
            width: 100%;
            background: #16a085;
            color: #fff;
            border: none;
            font-weight: 700;
            padding: 18px;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
        }

        .settings-form input[type=submit]:hover {
            background: #1abc9c;
        }

        /* Footer */
        footer {
            width: 100%;
            text-align: center;
            background: linear-gradient(90deg, #1abc9c, #16a085);
            color: #fff;
            padding: 16px;
            border-radius: 25px 25px 0 0;
            font-weight: 700;
        }

        @media(max-width:900px) {
            .settings-form {
                grid-template-columns: 1fr;
                padding: 30px 20px;
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
            <h3><?= htmlspecialchars($employee['username'] ?? 'Employee') ?></h3>
            <p><?= !empty($employee['email']) ? htmlspecialchars($employee['email']) : 'Email not available' ?></p>
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
<a href="salary.php" class="sidebar-link">
    <i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;&nbsp;Salary
</a>
<hr class="divider">
<a href="settings.php" class="sidebar-link active">
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
            <h1>Settings Panel</h1>
            <div class="top-info"><span id="currentDateTime"></span></div>
        </div>

        <div class="content">
            <div class="welcome-card">
                <h2>Employee Settings</h2>
                <p>Update your profile and account information</p>
            </div>

            <?php if ($msg) echo "<p style='color:green;margin-bottom:15px;text-align:center;'>$msg</p>"; ?>

            <form method="POST" class="settings-form">
                <h2>Profile Information</h2>

                <div class="input-group">
                    <input type="text" name="name" required value="<?= htmlspecialchars($employee['name'] ?? '') ?>" placeholder=" ">
                    <label>Full Name</label>
                </div>
                <div class="input-group">
                    <input type="email" name="email" required value="<?= htmlspecialchars($employee['email'] ?? '') ?>" placeholder=" ">
                    <label>Email</label>
                </div>
                <div class="input-group">
                    <input type="text" name="phone" value="<?= htmlspecialchars($employee['phone'] ?? '') ?>" placeholder=" ">
                    <label>Phone</label>
                </div>
                <div class="input-group">
                    <input type="number" name="age" value="<?= htmlspecialchars($employee['age'] ?? '') ?>" placeholder=" ">
                    <label>Age</label>
                </div>
                <div class="input-group">
                    <select name="gender" required>
                        <option value="" disabled <?= ($employee['gender'] ?? '') == '' ? "selected" : "" ?>></option>
                        <option value="Male" <?= ($employee['gender'] ?? '') == "Male" ? "selected" : "" ?>>Male</option>
                        <option value="Female" <?= ($employee['gender'] ?? '') == "Female" ? "selected" : "" ?>>Female</option>
                        <option value="Other" <?= ($employee['gender'] ?? '') == "Other" ? "selected" : "" ?>>Other</option>
                    </select>
                    <label>Gender</label>
                </div>
                <div class="input-group">
                    <input type="text" name="address" value="<?= htmlspecialchars($employee['address'] ?? '') ?>" placeholder=" ">
                    <label>Address</label>
                </div>
                <div class="input-group">
                    <input type="text" name="qualification" value="<?= htmlspecialchars($employee['qualification'] ?? '') ?>" placeholder=" ">
                    <label>Qualification</label>
                </div>
                <div class="input-group">
                    <input type="text" name="position" value="<?= htmlspecialchars($employee['position'] ?? '') ?>" placeholder=" ">
                    <label>Position</label>
                </div>
                <div class="input-group">
                    <input type="date" name="doj" value="<?= htmlspecialchars($employee['doj'] ?? '') ?>" placeholder=" ">
                    <label>Date of Joining</label>
                </div>
                <div class="input-group">
                    <input type="text" name="username" required value="<?= htmlspecialchars($employee['username'] ?? '') ?>" placeholder=" ">
                    <label>Username</label>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder=" ">
                    <label>New Password (leave blank to keep old)</label>
                </div>

                <input type="submit" name="submit" value="Update Profile">
            </form>
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
    </script>
</body>

</html>