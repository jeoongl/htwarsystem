<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign-up</title>
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
    }
    .login-btn {
      order: 1;
      margin-left: auto;
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
    .login-text {
      color: white;
      margin-top: 10px;
      text-align: center;
    }
    .login-link {
      color: green;
      cursor: pointer;
      text-decoration: underline;
    }
    .social-signup {
      margin-top: 20px;
    }
    .social-signup button {
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
  </style>
</head>
<body>
  <header>
    <img class="logo" src="img/logo.png" alt="Logo">
    <button class="login-btn" onclick="openLogin()">Login</button>
  </header>

  <!-- Sign-Up Form -->
  <div class="signup-container">
    <h2>Create an account</h2>
    <form action="sign-up-continue.php" method="post">
      <div class="input-container">
        <input type="email" name="email" placeholder="Email" required>
      </div>
      <div class="input-container">
        <input type="password" name="password" id="password" placeholder="Password" required>
        <i class="fa fa-eye password-toggle" onclick="togglePassword('password', this)"></i>
      </div>
      <div class="input-container">
        <input type="password" id="verify-password" placeholder="Verify Password" required>
        <i class="fa fa-eye password-toggle" onclick="togglePassword('verify-password', this)"></i>
      </div>
      <button type="submit">Continue</button>
    </form>
    <div class="login-text">
      Already have an account? <span class="login-link" onclick="redirectToLogin()">Login</span>
    </div>
    <div class="social-signup">
      <button class="google-btn" onclick="continueWithGoogle()"><i class="fab fa-google"></i> Continue with Google</button>
      <button class="facebook-btn" onclick="continueWithFacebook()"><i class="fab fa-facebook-f"></i> Continue with Facebook</button>
    </div>
  </div>

  <script>
    function openLogin() {
        window.location.href = 'log-in.php';
    }

    function continueWithGoogle() {
      alert("Redirect to Google OAuth");
    }

    function continueWithFacebook() {
      alert("Redirect to Facebook OAuth");
    }

    function redirectToLogin() {
      window.location.href = 'log-in.php';
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
