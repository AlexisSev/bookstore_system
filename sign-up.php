<?php
include_once('connection.php');
session_start();

// Handle user registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Insert into residents_table (or your user table)
    try {
        $query = "INSERT INTO users (name, email, password) VALUES (:username, :email, :password)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->execute();

        echo "<div class='alert alert-success'>Registration successful! You can now log in.</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['email_or_username'];
    $password = $_POST['password']; // The raw password entered by the user

    // First, check in the admin table
    $checkAdminQuery = "SELECT * FROM admin WHERE name = :username OR email = :email";
    $stmt = $pdo->prepare($checkAdminQuery);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':email', $username, PDO::PARAM_STR);
    $stmt->execute();
    $adminResult = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($adminResult) {
        // If found in admin table, check password
        if ($password === $adminResult['password']) {
            $_SESSION['user_id'] = $adminResult['admin_id'];
            $_SESSION['username'] = $adminResult['name'];
            $_SESSION['role'] = 'admin';
            header("Location: admin-dashboard.php"); // Redirect to admin dashboard
            exit();
        } else {
            $error_message = "Invalid password!";
        }
    } else {
        // If not found in admin table, check in residents table
        $checkResidentQuery = "SELECT * FROM users WHERE name = :username OR email = :email";
        $stmt = $pdo->prepare($checkResidentQuery);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $username, PDO::PARAM_STR);
        $stmt->execute();
        $residentResult = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($residentResult) {
            // If found in residents table, check password
            if ($password === $residentResult['password']) {
                $_SESSION['user_id'] = $residentResult['user_id'];
                $_SESSION['name'] = $residentResult['name'];
                $_SESSION['role'] = 'user';
                header("Location: user-homepage.php"); // Redirect to user dashboard
                exit();
            } else {
                $error_message = "Invalid password!";
            }
        } else {
            $error_message = "User not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookstore Management - User Access</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
  <style>
    body {
      background: linear-gradient(to right, #13547A, #80D0C7);
      font-family: 'Arial', sans-serif;
      color: #333;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }

    .header {
      background: linear-gradient(to right, #13547A, #80D0C7);
      padding: 40px 0;
      text-align: center;
      margin-bottom: 10px;
    }

    .header h1 {
      font-size: 36px;
      font-weight: bold;
      color: #2c3e50;
    }

    .header p {
      font-size: 16px;
      color: white;
    }

    .header img {
      width: 100%;
      max-height: 200px;
      object-fit: cover;
      border-radius: 8px;
      margin-top: 10px;
    }

    .form-container {
      background-image: url('images/calculator-cup-coffee-notebook-black-background-top-view.jpg');
      background-size: cover;
      background-position: center;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      color: #fff;
      opacity: 0.9;
    }

    .form-title {
      font-size: 26px;
      text-align: center;
      margin-bottom: 30px;
    }

    .form-container .form-label {
      font-weight: 600;
    }

    .form-control {
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 20px;
      font-size: 16px;
    }

    .btn-primary,
    .btn-success {
      border-radius: 8px;
      padding: 12px;
      font-size: 18px;
    }

    .btn-primary {
      background-color: #3498db;
      border-color: #3498db;
    }

    .btn-primary:hover {
      background-color: #2980b9;
      border-color: #2980b9;
    }

    .btn-success {
      background-color: #27ae60;
      border-color: #27ae60;
    }

    .btn-success:hover {
      background-color: #2ecc71;
      border-color: #2ecc71;
    }

    .toggle-link {
      display: inline-block;
      font-size: 1.3em;
      text-decoration: underline;
      cursor: pointer;
      color: blue;
    }

    .form-footer {
      text-align: center;
      margin-top: 20px;
    }

    .form-footer a {
      color: #3498db;
      text-decoration: none;
    }

    .form-footer a:hover {
      text-decoration: underline;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .container {
      margin-top: 30px;
    }

    @media (max-width: 768px) {
      .header img {
        height: 200px;
      }

      .form-container {
        padding: 20px;
      }

      .header h1 {
        font-size: 30px;
      }
    }

    @media (max-width: 576px) {
      .form-container {
        padding: 15px;
      }

      .form-title {
        font-size: 24px;
      }

      .header h1 {
        font-size: 26px;
      }
    }
  </style>
</head>

<body>
    <div class="header">
        <h1>Bookstore Management System</h1>
        <p>Manage your books and user access easily.</p>
        <img src="images/book-banner.jpg" alt="Bookstore Banner" class="book-image">
    </div>

    <div class="container mt-3 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="form-container" id="form-container">
                    <h2 class="form-title" id="form-title">User Login</h2>

                    <!-- Login Form -->
                    <form method="POST" action="" id="login-form">
                        <div class="mb-3">
                            <label for="login-email-or-username" class="form-label">Username or Email</label>
                            <input type="text" class="form-control" id="login-email-or-username" name="email_or_username" required>
                        </div>
                        <div class="mb-3">
                            <label for="login-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="login-password" name="password" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-success w-100">Login</button>
                        <div class="d-flex justify-content-center mt-4">
                            <h4 class="m-0">Don't have an account? </h4><span class="toggle-link ms-2" id="show-register">Sign up now</span>
                        </div>
                    </form>

                    <!-- Registration Form -->
                    <form method="POST" action="" id="register-form" style="display: none;">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
                        <div class="d-flex justify-content-center mt-4">
                            <h4 class="m-0">Already have an account? </h4><span class="toggle-link ms-2" id="show-login">Login here</span>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const formTitle = document.getElementById('form-title');
        const showRegister = document.getElementById('show-register');
        const showLogin = document.getElementById('show-login');

        showRegister.addEventListener('click', () => {
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
            formTitle.textContent = 'User Registration';
        });

        showLogin.addEventListener('click', () => {
            registerForm.style.display = 'none';
            loginForm.style.display = 'block';
            formTitle.textContent = 'User Login';
        });
    </script>
</body>

</html>
