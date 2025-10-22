<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../db/db.php';

$msg = "";
if(isset($_POST['register'])){
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
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Insert into employees table
    $stmt = $conn->prepare("INSERT INTO employees (name,email,phone,age,gender,address,qualification,position,date_of_joining) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssiissss",$name,$email,$phone,$age,$gender,$address,$qualification,$position,$doj);

    if($stmt->execute()){
        $employee_id = $stmt->insert_id;

        // Insert into users table including email
        $stmt2 = $conn->prepare("INSERT INTO users (username,password,role,employee_id,email) VALUES (?,?,?,?,?)");
        $stmt2->bind_param("sssis",$username,$password,$role,$employee_id,$email);

        if($stmt2->execute()){
            $msg = "User registered successfully! <a href='login.php'>Login here</a>";
        } else {
            $msg = "Error: ".$conn->error;
        }
    } else {
        $msg = "Error: ".$conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>HR Management Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        *{margin:0; padding:0; box-sizing:border-box;}
        body, html {
            height: 100%;
            font-family: 'Poppins', sans-serif;
        }

        .main-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .image-side {
            flex: 1;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 30px;
            text-align: center;
        }

        .image-side h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 36px;
            color: #0B1F5C;
            margin-bottom: 10px;
        }

        .image-side p {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            color: #444;
            margin-bottom: 30px;
        }

        .image-side img {
            max-width: 80%;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .form-side {
            flex: 1;
            background: linear-gradient(135deg, #0B1F5C, #2575fc);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
        }

        .register-container {
            background:white;
            border-radius:20px;
            padding:40px;
            max-width:900px;
            width:100%;
            box-shadow:0 20px 50px rgba(0,0,0,0.3);
            text-align:center;
        }

        .register-container h2 {
            font-family:'Orbitron', sans-serif;
            font-size:28px;
            color:#0B1F5C;
            margin-bottom:10px;
        }

        .register-container h3 {
            font-family:'Poppins', sans-serif;
            font-size:16px;
            color:#444;
            margin-bottom:25px;
        }

        form {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:20px;
        }

        .form-group {
            position:relative;
        }

        .form-group input, .form-group select {
            width:100%;
            padding:15px;
            border-radius:8px;
            border:1px solid #ccc;
            font-size:16px;
            transition: all 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
            border-color:#0B1F5C;
            box-shadow:0 0 10px rgba(11,31,92,0.3);
            outline:none;
        }

        .form-group label {
            position:absolute;
            left:12px;
            top:15px;
            font-size:14px;
            color:#999;
            pointer-events:none;
            transition: all 0.3s ease;
            font-weight:bold;
        }

        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label,
        .form-group select:focus + label,
        .form-group select:not([value=""]) + label {
            top:-10px;
            left:10px;
            font-size:12px;
            color:#0B1F5C;
            background:white;
            padding:0 5px;
        }

        .full-width {
            grid-column: span 2;
        }

        input[type=submit] {
            grid-column: span 2;
            padding:18px;
            border:none;
            border-radius:10px;
            background:#0B1F5C;
            color:white;
            font-size:18px;
            font-weight:bold;
            cursor:pointer;
            transition: all 0.3s ease;
        }

        input[type=submit]:hover {
            opacity:0.9;
        }

        .msg {
            color:red;
            font-weight:bold;
            margin-bottom:10px;
            grid-column: span 2;
        }

        .register-container p {
            margin-top:18px;
            font-size:15px;
            color:#555;
        }

        .register-container p a {
            color:#0B1F5C;
            font-weight:bold;
            text-decoration:none;
        }

        .register-container p a:hover {
            text-decoration:underline;
        }

        @media(max-width:768px){
            .main-wrapper {
                flex-direction: column;
            }

            .form-side, .image-side {
                flex: none;
                width: 100%;
                height: auto;
            }

            form {grid-template-columns:1fr;}
            input[type=submit], .msg {grid-column: span 1;}
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="form-side">
            <div class="register-container">
                <h2>Create Your Account</h2>
                <h3>Fill in your details to register</h3>
                <?php if($msg) echo "<div class='msg'>$msg</div>"; ?>
                <form method="POST">
                    <div class="form-group"><input type="text" name="name" placeholder=" " required><label>Full Name</label></div>
                    <div class="form-group"><input type="email" name="email" placeholder=" " required><label>Email</label></div>
                    <div class="form-group"><input type="text" name="phone" placeholder=" " required><label>Phone</label></div>
                    <div class="form-group"><input type="number" name="age" placeholder=" " required><label>Age</label></div>
                    <div class="form-group">
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                        <label>Gender</label>
                    </div>
                    <div class="form-group"><input type="text" name="address" placeholder=" " required><label>Address</label></div>
                    <div class="form-group"><input type="text" name="qualification" placeholder=" " required><label>Qualification</label></div>
                    <div class="form-group"><input type="text" name="position" placeholder=" " required><label>Position/Job Title</label></div>
                    <div class="form-group"><input type="date" name="doj" placeholder=" " required><label>Date of Joining</label></div>
                    <div class="form-group"><input type="text" name="username" placeholder=" " required><label>Username</label></div>
                    <div class="form-group"><input type="password" name="password" placeholder=" " required><label>Password</label></div>
                    <div class="form-group">
                        <select name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="employee">Employee</option>
                        </select>
                        <label>Role</label>
                    </div>
                    <input type="submit" name="register" value="Register">
                </form>
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
        <div class="image-side">
            <h1>REGISTRATION</h1>
            <p>Join the team and get started</p>
            <img src="../assets/registration-graphic.png" alt="Register Illustration">
        </div>
    </div>
</body>
</html>