<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../db/db.php';

$error = "";

if (isset($_POST['login'])) {
    $username_or_email = $_POST['username_or_email'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    // Fetch user
    $stmt = $conn->prepare("
        SELECT u.*, 
               COALESCE(u.email, e.email) AS email 
        FROM users u 
        LEFT JOIN employees e ON u.employee_id = e.id 
        WHERE u.username=? OR u.email=? OR e.email=?
        LIMIT 1
    ");
    $stmt->bind_param("sss", $username_or_email, $username_or_email, $username_or_email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Store session info
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['employee_id'] = $user['employee_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];

        // Remember Me: store cookie for 30 days
        if ($remember) {
            setcookie("hr_user", $user['id'], time() + (86400 * 30), "/");
        }

        // Redirect
        if ($user['role'] == 'admin') {
            header("Location: ../admin/admin_dashboard.php");
        } else {
            header("Location: ../employee/employee_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid username/email or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login | HR Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { height: 100%; font-family: 'Poppins', sans-serif; background: #f4f6fc; }
        .main-wrapper { display: flex; height: 100vh; width: 100%; }
        .image-side { flex: 1; background: white; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 30px; text-align: center; }
        .image-side h1 { font-family: 'Orbitron', sans-serif; font-size: 36px; color: #0B1F5C; margin-bottom: 10px; }
        .image-side p { font-size: 18px; color: #444; margin-bottom: 30px; }
        .image-side img { max-width: 80%; height: auto; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .login-side { flex: 1; background: linear-gradient(135deg, #0B1F5C, #2575fc); display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px; }
        .login-container { background: white; border-radius: 20px; padding: 50px; max-width: 450px; width: 100%; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.2); }
        .login-container img { height: 100px; display: block; margin: 0 auto 30px; }
        .login-container h1 { font-family: 'Orbitron', sans-serif; font-size: 32px; color: #0B1F5C; margin-bottom: 10px; }
        .login-container h2 { font-size: 16px; color: #444; margin-bottom: 25px; }
        .form-group { position: relative; margin: 20px 0; }
        .form-group input { width: 100%; padding: 15px; border-radius: 8px; border: 1px solid #ccc; font-size: 16px; }
        .form-group label { position: absolute; left: 12px; top: 15px; font-size: 14px; color: #999; pointer-events: none; transition: all 0.3s ease; }
        .form-group input:focus + label, .form-group input:not(:placeholder-shown) + label { top: -10px; left: 10px; font-size: 12px; color: #0B1F5C; background: white; padding: 0 5px; }
        input[type=submit] { width: 100%; padding: 18px; margin-top: 25px; border: none; border-radius: 10px; background: #0B1F5C; color: white; font-size: 18px; font-weight: bold; cursor: pointer; }
        .error-msg { color: red; font-weight: bold; margin-bottom: 10px; }
        .login-container p { margin-top: 18px; font-size: 15px; color: #555; }
        .login-container p a { color: #0B1F5C; text-decoration: none; font-weight: bold; }
        .box-footer-center { margin-top: 20px; text-align: center; font-size: 14px; width: 100%; }
        .box-footer-center a { margin: 0 15px; color: #fff; text-decoration: none; font-weight: 500; }
        .box-footer-center a:hover { text-decoration: underline; }
        @media(max-width: 768px) { .main-wrapper { flex-direction: column; } .login-side { padding: 20px; } .box-footer-center a { color: #0B1F5C; } }
    </style>
</head>
<body>
<div class="main-wrapper">
    <div class="image-side">
        <h1>LOGIN</h1>
        <p>Access your dashboard and manage HR tasks</p>
        <img src="../assets/login-graphic.png" alt="Login Illustration">
    </div>
    <div class="login-side">
        <div class="login-container">
            <h1>HR Management</h1>
            <h2>Welcome back! Please login</h2>
            <?php if ($error) echo "<div class='error-msg'>$error</div>"; ?>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="username_or_email" placeholder=" " required>
                    <label>Username or Email</label>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder=" " required>
                    <label>Password</label>
                </div>

                <!-- Remember Me and Forgot Password -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                    <div>
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember" style="font-size:14px; color:#444;">Remember Me</label>
                    </div>
                    <div>
                        <a href="../auth/forgot_password.php" style="font-size:14px; color:#0B1F5C; text-decoration:none; font-weight:500;">Forgot Password?</a>
                    </div>
                </div>

                <input type="submit" name="login" value="Login">
            </form>
            <p>Don't have an account? <a href="../auth/register.php">Register here</a></p>
        </div>
        <div class="box-footer-center">
            <a href="../auth/privacy.php">Privacy Policy</a>
            <a href="../auth/terms.php">Terms of Use</a>
            <a href="../auth/help.php">Help Center</a>
        </div>
    </div>
</div>
</body>
</html>
