<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Change Password</title>
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

    .profile-icon {
      position: relative;
      cursor: pointer;
      color: white;
      font-size: 30px;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      top: 40px;
      right: 0;
      background-color: #333;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
      border-radius: 8px;
      overflow: hidden;
      width: 150px;
    }

    .dropdown-menu a {
      display: flex;
      align-items: center;
      padding: 10px;
      color: white;
      text-decoration: none;
      background-color: #333;
      border-bottom: 1px solid #444;
      font-size: 14px;
    }

    .dropdown-menu a:hover {
      background-color: #444;
    }

    .dropdown-menu i {
      margin-right: 10px;
    }

    .signup-container {
      background-color: black;
      padding: 20px;
      border-radius: 8px;
      width: 300px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
      text-align: center;
      margin: 50px auto;
    }

    .signup-container h2 {
      margin-top: 0;
      font-size: 32px;
      text-align: left;
      color: white;
    }

    .signup-container .input-container {
      position: relative;
      width: 100%;
      margin: 20px 0;
    }

    .signup-container input {
      width: 81%;
      padding: 15px;
      border: 1px solid #555;
      border-radius: 4px;
      background-color: #222;
      color: white;
      padding-right: 40px; /* Space for the icon */
    }

    .password-toggle {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: white;
    }

    .signup-container button {
      width: 100%;
      padding: 15px;
      margin: 10px 0;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      background-color: green;
      color: white;
      font-size: 16px;
    }

    .forgot-password-text {
      color: white;
      margin-top: 10px;
      text-align: center;
    }

    .forgot-password-link {
      color: green;
      cursor: pointer;
      text-decoration: underline;
    }

  </style>
</head>
<body>
  <header>
    <img class="logo" src="img/logo.png" alt="Logo">
    <div class="profile-icon" onclick="toggleDropdown()">
      <i class="fas fa-user-circle"></i>
      <div class="dropdown-menu" id="dropdown-menu">
      <a href="user-login-index.php"><i class="fas fa-home"></i> Home</a>
      <a href="user-profile-page.php"><i class="fas fa-user"></i> My Account</a>
        <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </header>

  <!-- Change Password Form -->
  <div class="signup-container">
    <h2>Change Password</h2>
    <form action="sign-up-continue.php" method="post">
      <div class="input-container">
        <input type="password" name="old-password" id="old-password" placeholder="Old Password" required>
        <i class="fa fa-eye password-toggle" onclick="togglePassword('old-password', this)"></i>
      </div>
      <div class="input-container">
        <input type="password" name="new-password" id="new-password" placeholder="New Password" required>
        <i class="fa fa-eye password-toggle" onclick="togglePassword('new-password', this)"></i>
      </div>
      <div class="input-container">
        <input type="password" id="verify-password" placeholder="Verify New Password" required>
        <i class="fa fa-eye password-toggle" onclick="togglePassword('verify-password', this)"></i>
      </div>
      <button type="submit">Change Password</button>
    </form>
    <div class="forgot-password-text">
      <span class="forgot-password-link" onclick="redirectToForgotPassword()">Forgot Password?</span>
    </div>
  </div>

  <script>
    function toggleDropdown() {
      const dropdown = document.getElementById("dropdown-menu");
      dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    window.onclick = function(event) {
      if (!event.target.matches('.profile-icon, .profile-icon *')) {
        const dropdown = document.getElementById("dropdown-menu");
        if (dropdown.style.display === "block") {
          dropdown.style.display = "none";
        }
      }
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

    function redirectToForgotPassword() {
      window.location.href = 'forgot-password.php';
    }
  </script>
</body>
</html>
