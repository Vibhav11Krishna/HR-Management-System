<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../db/db.php';

$error = "";
$success = "";

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        if ($password !== $confirm) {
            $error = "Passwords do not match!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $user['id']);
            $stmt->execute();
            $success = "Password updated successfully! <a href='login.php'>Login here</a>";
        }
    } else {
        $error = "No account found with this email!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password | HR Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body, html { height:100%; font-family:'Poppins', sans-serif; background:#f4f6fc; }
        .main-wrapper { display:flex; height:100vh; width:100%; }

        /* Left Image + Heading */
        .image-side { flex:1; background:white; display:flex; flex-direction:column; justify-content:center; align-items:center; padding:30px; text-align:center; }
        .image-side h1 { font-family:'Orbitron', sans-serif; font-size:36px; color:#0B1F5C; margin-bottom:10px; }
        .image-side p { font-size:18px; color:#0B1F5C; margin-bottom:30px; }
        .image-side img { max-width:80%; height:auto; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.1); }

        /* Right Form */
        .form-side { flex:1; display:flex; justify-content:center; align-items:center; padding:40px; background: linear-gradient(135deg,#0B1F5C,#2575fc); }
        .form-box { background:#f4f6fc; padding:50px; border-radius:20px; max-width:450px; width:100%; box-shadow:0 20px 50px rgba(0,0,0,0.2); text-align:center; }

        .form-box h1 { font-family:'Orbitron', sans-serif; color:#0B1F5C; margin-bottom:10px; }
        .form-box h2 { font-size:16px; color:#444; margin-bottom:25px; }
        .form-group { position:relative; margin:20px 0; }
        .form-group input { width:100%; padding:15px; border-radius:8px; border:1px solid #ccc; font-size:16px; }
        .form-group label { position:absolute; left:12px; top:15px; font-size:14px; color:#999; pointer-events:none; transition: all 0.3s ease; }
        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label { top:-10px; left:10px; font-size:12px; color:#0B1F5C; background:#f4f6fc; padding:0 5px; }

        input[type=submit] { width:100%; padding:18px; margin-top:25px; border:none; border-radius:10px; background:#0B1F5C; color:white; font-size:18px; font-weight:bold; cursor:pointer; }
        .error-msg { color:red; font-weight:bold; margin-bottom:10px; }
        .success-msg { color:green; font-weight:bold; margin-bottom:10px; }
        .back-link { margin-top:15px; text-align:center; }
        .back-link a { color:#0B1F5C; text-decoration:none; font-weight:500; }
        .back-link a:hover { text-decoration:underline; }

        @media(max-width:768px) {
            .main-wrapper { flex-direction:column; }
            .image-side, .form-side { padding:20px; }
        }
    </style>
</head>
<body>
<div class="main-wrapper">
    <!-- Left Image + Heading -->
    <div class="image-side">
        <h1>Reset Your Password</h1>
        <p>Securely update your HR Management account password</p>
        <img src="../assets/forgot-password.png" alt="Forgot Password Illustration">
    </div>

    <!-- Right Form -->
    <div class="form-side">
        <div class="form-box">
            <h1>Reset Password</h1>
            <h2>Enter your email and new password</h2>
            <?php if($error) echo "<div class='error-msg'>$error</div>"; ?>
            <?php if($success) echo "<div class='success-msg'>$success</div>"; ?>
            <?php if(!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <input type="email" name="email" placeholder=" " required>
                    <label>Email</label>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder=" " required>
                    <label>New Password</label>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm" placeholder=" " required>
                    <label>Confirm Password</label>
                </div>

                <input type="submit" name="submit" value="Reset Password">
            </form>
            <div class="back-link">
                <a href="login.php">Back to Login</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
