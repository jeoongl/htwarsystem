<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['email'] = $_POST['email'];
    $_SESSION['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
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
    .role-container {
      background-color: black;
      padding: 20px;
      border-radius: 8px;
      width: 300px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
      text-align: center;
      margin: 50px auto;
    }
    .role-container h2 {
      margin-top: 0;
      font-size: 32px;
      text-align: left;
      color: white;
    }
    .role-container .input-container {
      width: 100%;
      margin: 20px 0;
    }
    .role-container input[type="text"] {
      width: 89.4%;
      padding: 15px;
      border: 1px solid #555;
      border-radius: 4px;
      background-color: #222;
      color: white;
    }
    .role-options {
      text-align: left;
      margin: 20px 0;
    }
    .role-options input[type="radio"] {
      display: none;
    }
    .role-options label {
      display: flex;
      align-items: center;
      padding: 10px;
      cursor: pointer;
      border: 1px solid #555;
      border-radius: 4px;
      background-color: #222;
      color: white;
      margin-bottom: 10px;
      transition: background-color 0.3s;
    }
    .role-options input[type="radio"] + label::before {
      content: '';
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 2px solid white;
      border-radius: 50%;
      margin-right: 10px;
    }
    .role-options input[type="radio"]:checked + label {
      background-color: green;
    }
    .role-options input[type="radio"]:checked + label::before {
      background-color: white;
      border-color: white;
    }
    .role-container button {
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
  </style>
</head>
<body>
  <header>
    <img class="logo" src="img/logo.png" alt="Logo">
  </header>

  <!-- Role Selection Form -->
  <div class="role-container">
    <h2>Select Your Role</h2>
    <form action="register.php" method="post">
      <div class="input-container">
        <input type="text" name="fullname" placeholder="Full Name" required>
      </div>
      <div class="input-container">
        <input type="text" name="username" placeholder="Username" required>
      </div>
      <div class="role-options">
        <input type="radio" id="tourist" name="role" value="tourist" required>
        <label for="tourist">Tourist</label>
        <input type="radio" id="business-owner" name="role" value="business-owner" required>
        <label for="business-owner">Business Owner</label>
      </div>
      <button type="submit">Register</button>
    </form>
  </div>
</body>
</html>
