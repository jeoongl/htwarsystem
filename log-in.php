<?php
session_start();
require 'includes/dbconnection.php';

// Initialize error message variable
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare a statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, password, role_id FROM users_tbl WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password, $role_id);
    $stmt->fetch();

    if ($stmt->num_rows > 0) {
      if (password_verify($password, $hashed_password)) {
          // Store user details in session
          $_SESSION['user_id'] = $id;
          $_SESSION['role_id'] = $role_id;

          // Redirect based on role_id
          if ($role_id == 1) {
              header('Location: admin-dashboard.php');
          } elseif ($role_id == 2 || $role_id == 3) {
              header('Location: user-login-index.php');
          } else {
              $error_message = "Invalid role.";
          }
          exit();
      } else {
          $error_message = "Incorrect password.";
      }
  } else {
      $error_message = "No user found: Incorrect username or password.";
  }

  $stmt->close();
  $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hinunangan Tourism Website and Reservation System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      margin: 0;
      padding: 0;
      background-color: black;
      color: white;
    }
    header {
      background-color: green; 
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo {
      width: 350px;
    }
    .login-btn {
      background-color: transparent;
      border: none;
      font-size: 16px;
      cursor: pointer;
      color: white;
      order: 1;
      margin-left: auto;
    }
    .login-container {
      background-color: black;
      padding: 20px;
      border-radius: 8px;
      width: 300px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
      text-align: center;
      margin: 50px auto;
    }
    .login-container h2 {
      margin-top: 0;
      font-size: 32px;
      text-align: left;
      color: white;
    }
    .login-container .input-container {
      position: relative;
      width: 100%;
      margin: 20px 0;
      margin-bottom: 0;
      min-height: 40px;
    }
    .login-container input {
      width: 81%;
      padding: 15px;
      border: 1px solid #555;
      border-radius: 4px;
      background-color: #222;
      color: white;
      padding-right: 40px;
    }
    .password-toggle {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: white;
    }
    .login-container button {
      width: 100%;
      padding: 12px; /* Reduced padding to make it closer to input */
      margin-top: 10px; /* Reduced margin-top to make button closer */
      border: none;
      border-radius: 4px;
      cursor: pointer;
      background-color: green;
      color: white;
      font-size: 16px;
    }
    .signup-text {
      color: white;
      margin-top: 10px;
      text-align: center;
    }
    .signup-link {
      color: green;
      cursor: pointer;
      text-decoration: underline;
    }
    .social-login {
      margin-top: 20px;
    }
    .social-login button {
      width: 100%;
      padding: 15px;
      margin: 5px 0;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }
    .google-btn {
      background-color: #db4437;
      color: white;
    }
    .facebook-btn {
      background-color: #4267B2;
      color: white;
    }
    .error-message {
      color: #ff6347; /* Tomato color for errors */
      margin-top: 5px; /* Reduced margin-top */
      font-size: 12px; /* Smaller font size */
      line-height: 1.2; /* Reduced line-height to make it more compact */
    }
    .floating-home {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 60px;
    height: 60px;
    background-color: green;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    cursor: pointer;
    z-index: 1000;
    transition: transform 0.3s ease;
  }

  .floating-home:hover {
    transform: scale(1.1);
  }

  .floating-home i {
    color: white;
    font-size: 24px;
  }
  </style>
</head>
<body>
  <header>
    <img class="logo" src="img/logo.png" alt="Logo">
    <button class="login-btn" onclick="openLogin()">Login</button>
  </header>

  <!-- Login Form -->
  <div class="login-container">
    <h2>Login account</h2>
    <form action="log-in.php" method="POST">
      <div class="input-container">
        <input type="text" name="username" placeholder="Username" required>
      </div>
      <div class="input-container">
        <input type="password" name="password" id="login-password" placeholder="Password" required>
        <i class="fa fa-eye password-toggle" onclick="togglePassword('login-password', this)"></i>
      </div>
      <!-- Display error message if exists -->
      <?php if (isset($error_message) && !empty($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
      <?php else: ?>
        <div class="error-message">&nbsp;</div> <!-- Placeholder to keep layout consistent -->
      <?php endif; ?>
      <button type="submit">Log In</button>
    </form>
    <div class="signup-text">
      No account? <span class="signup-link" onclick="openSignup()">Sign-up</span>
    </div>
    <div class="social-login">
      <button class="google-btn" onclick="continueWithGoogle()"><i class="fab fa-google"></i> Continue with Google</button>
      <button class="facebook-btn" onclick="continueWithFacebook()"><i class="fab fa-facebook-f"></i> Continue with Facebook</button>
    </div>
  </div>

  <script>
    function openSignup() {
      window.location.href = 'sign-up.php'; // Redirect to Sign-Up page
    }
    function openLogin() {
        window.location.href = 'log-in.php';
    }
    function continueWithGoogle() {
      alert("Redirect to Google OAuth");
    }

    function continueWithFacebook() {
      alert("Redirect to Facebook OAuth");
    }

    function togglePassword(fieldId, icon) {
      const field = document.getElementById(fieldId);
      if (field.type === "password") {
        field.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        field.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    }
  </script>
</body>
</html>
