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
$admin = $conn->query("SELECT * FROM users WHERE id='$adminId' AND role='admin'")->fetch_assoc();

$msg = '';
if (isset($_POST['submit'])) {
    // Fetch all fields safely
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $age = $_POST['age'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';
    $qualification = $_POST['qualification'] ?? '';
    $position = $_POST['position'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Update password only if filled
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, phone=?, age=?, gender=?, address=?, qualification=?, position=?, dob=?, username=?, password=? WHERE id=?");
        $stmt->bind_param("sssssssssssi", $name, $email, $phone, $age, $gender, $address, $qualification, $position, $dob, $username, $password_hash, $adminId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, phone=?, age=?, gender=?, address=?, qualification=?, position=?, dob=?, username=? WHERE id=?");
        $stmt->bind_param("ssssssssssi", $name, $email, $phone, $age, $gender, $address, $qualification, $position, $dob, $username, $adminId);
    }

    if ($stmt->execute()) {
        $msg = "Profile updated successfully!";
        // Refresh admin data
        $admin = $conn->query("SELECT * FROM users WHERE id='$adminId' AND role='admin'")->fetch_assoc();
    } else {
        $msg = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Admin Settings | HR Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@600&display=swap" rel="stylesheet">
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
  .divider {
    border: none;
    height: 1px;
    background: rgba(255, 255, 255, 0.50);
    margin: 4px 0;
}
    

        /* Main Area */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        /* Navbar */
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
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.3);
        }


        .navbar h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 26px;
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

        .settings-form {
            background: #fff;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            max-width: 700px;
            margin: 0 auto 40px auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .settings-form h2 {
            grid-column: 1/3;
            text-align: center;
            margin-bottom: 30px;
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
            color: #ff7b00;
            background: #fff;
            padding: 0 5px;
        }

        .settings-form input[type=submit] {
            grid-column: 1/3;
            width: 100%;
            background: #ff7b00;
            color: #fff;
            border: none;
            font-weight: 700;
            padding: 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        .settings-form input[type=submit]:hover {
            background: #ff9d2f;
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
        

        @media(max-width:900px) {
            .settings-form {
                grid-template-columns: 1fr;
                padding: 30px 20px;
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
            <h3><?= htmlspecialchars($admin['username'] ?? '') ?></h3>
            <p><?= htmlspecialchars($admin['email'] ?? '') ?></p>
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
<a href="payement.php">
    <i class="fas fa-money-bill-wave"></i> Payment
</a>
<hr class="divider">
<a href="settings.php" class="active">
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

        <div class="content">
            <div class="welcome-card">
                <h2>Admin Settings</h2>
                <p>Update your profile and account information</p>
            </div>
            <?php if ($msg) echo "<p style='color:green;margin-bottom:15px;text-align:center;'>$msg</p>"; ?>
            <form method="POST" class="settings-form">
              

                <div class="input-group">
                    <input type="text" name="name" required value="<?= htmlspecialchars($admin['name'] ?? '') ?>" placeholder=" ">
                    <label>Full Name</label>
                </div>
                <div class="input-group">
                    <input type="email" name="email" required value="<?= htmlspecialchars($admin['email'] ?? '') ?>" placeholder=" ">
                    <label>Email</label>
                </div>
                <div class="input-group">
                    <input type="text" name="phone" value="<?= htmlspecialchars($admin['phone'] ?? '') ?>" placeholder=" ">
                    <label>Phone</label>
                </div>
                <div class="input-group">
                    <input type="number" name="age" value="<?= htmlspecialchars($admin['age'] ?? '') ?>" placeholder=" ">
                    <label>Age</label>
                </div>
                <div class="input-group">
                    <select name="gender" required>
                        <option value="" disabled <?= ($admin['gender'] ?? '') == '' ? "selected" : "" ?>></option>
                        <option value="Male" <?= ($admin['gender'] ?? '') == "Male" ? "selected" : "" ?>>Male</option>
                        <option value="Female" <?= ($admin['gender'] ?? '') == "Female" ? "selected" : "" ?>>Female</option>
                        <option value="Other" <?= ($admin['gender'] ?? '') == "Other" ? "selected" : "" ?>>Other</option>
                    </select>
                    <label>Gender</label>
                </div>
                <div class="input-group">
                    <input type="text" name="address" value="<?= htmlspecialchars($admin['address'] ?? '') ?>" placeholder=" ">
                    <label>Address</label>
                </div>
                <div class="input-group">
                    <input type="text" name="qualification" value="<?= htmlspecialchars($admin['qualification'] ?? '') ?>" placeholder=" ">
                    <label>Qualification</label>
                </div>
                <div class="input-group">
                    <input type="text" name="position" value="<?= htmlspecialchars($admin['position'] ?? '') ?>" placeholder=" ">
                    <label>Position</label>
                </div>
                <div class="input-group">
                    <input type="date" name="doj" value="<?= htmlspecialchars($admin['doj'] ?? '') ?>" placeholder=" ">
                    <label>Date of Joining</label>
                </div>
                <div class="input-group">
                    <input type="text" name="username" required value="<?= htmlspecialchars($admin['username'] ?? '') ?>" placeholder=" ">
                    <label>Username</label>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder=" ">
                    <label>New Password (leave blank to keep old)</label>
                </div>

                <input type="submit" name="submit" value="Update Settings">
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