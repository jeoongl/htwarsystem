<?php
session_start();
include 'includes/dbconnection.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data from the database
$query = "SELECT fullname, profile_photo, username FROM users_tbl WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Close the initial statement
$stmt->close();

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $profile_photo = $user['profile_photo']; // Default to the current profile photo

    // Handle profile photo upload if a new one is provided
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        $tmp_name = $file['tmp_name'];
        $name = basename($file['name']);
        $upload_dir = 'uploads/';
        $file_path = $upload_dir . $name;
        move_uploaded_file($tmp_name, $file_path);

        // Update the profile photo path
        $profile_photo = $file_path;
    }

    // Update user information (fullname, username, profile_photo) in the database
    $update_query = "UPDATE users_tbl SET fullname = ?, username = ?, profile_photo = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssi", $fullname, $username, $profile_photo, $user_id);
    $update_stmt->execute();

    // Close the update statement after updating user info
    $update_stmt->close();

    // Update user data in the session
    $user['fullname'] = $fullname;
    $user['username'] = $username;
    $user['profile_photo'] = $profile_photo;

    // Redirect to the profile page after the update
    header("Location: user-profile-page.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile</title>
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
      font-size: 20px;
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
      z-index: 1000;
    }
    .dropdown-menu div {
      text-align: center;
      padding: 10px 0;
      background-color: #444;
    }
    .dropdown-menu div p {
      margin: 5px 0;
    }
    .dropdown-menu div img {
      margin-top: 5px;
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
    .container {
      background-color: #222;
      padding: 20px;
      margin: 20px;
      border-radius: 10px;
      max-width: 800px;
      margin-left: auto;
      margin-right: auto;
      text-align: left;
    }
    .profile-photo {
      width: 150px;
      height: 150px;
      position: relative;
      border-radius: 50%;
      margin: 0 auto 10px;
      overflow: hidden;
    }
    .profile-photo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      border-radius: 50%;
      z-index: 1;
    }
    .edit-button {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background-color: transparent;
      border: none;
      color: white;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 16px;
      z-index: 2;
    }
    .input-group {
      margin-bottom: 20px;
    }
    .input-label {
      display: block;
      font-size: 0.8em;
      color: #ccc;
      margin-bottom: 5px;
    }
    .input-field {
      width: 100%;
      padding: 10px;
      border: 1px solid #444;
      border-radius: 5px;
      background-color: #333;
      color: white;
      box-sizing: border-box;
    }
    .input-field::placeholder {
      color: #888;
    }
    .description-field {
      height: 100px;
      text-align: justify;
      vertical-align: top;
      padding-top: 10px;
      resize: none;
    }
    .container h2 {
      margin-left: -5px;
      margin-top: 0;
      font-size: 1.5em;
      color: #ccc;
      display: flex;
      align-items: center;
    }
    .back-button {
      margin-right: 30px;
      font-size: 1em;
      cursor: pointer;
      color: white;
      background: none;
      border: none;
    }
    .change-label {
      color: #888;
      font-size: 0.9em;
      text-align: center;
      margin-bottom: 20px;
    }
    .button-group {
      margin-top: 20px;
    }
    .button-group .update-button {
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      width: 100%; /* Full width */
      background-color: green;
      color: white;
      text-align: center;
    }
    .button-group .update-button:hover {
      background-color: darkgreen;
    }
    #file-input {
      display: none;
    }
  </style>
  <script>
    function openFileDialog() {
      document.getElementById('file-input').click();
    }

    function handleFileSelect(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const profilePhoto = document.querySelector('.profile-photo img');
          profilePhoto.src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    }

    function goBack() {
      window.history.back(); // Go to the last page when back button is clicked
    }
  </script>
</head>
<body>
<header>
  <img class="logo" src="img/logo.png" alt="Logo">
  <div class="profile-icon" onclick="toggleDropdown()">
    <img src="<?php echo $user['profile_photo'] ? $user['profile_photo'] : 'img/default-profile.jpg'; ?>" style="width: 40px; height: 40px; border-radius: 50%;">
    <div class="dropdown-menu" id="dropdown-menu">
      <div>
        <img src="<?php echo $user['profile_photo'] ? $user['profile_photo'] : 'img/default-profile.jpg'; ?>" style="width: 60px; height: 60px; border-radius: 50%;">
        <p><?php echo htmlspecialchars($user['fullname']); ?></p>
      </div>
      <a href="user-login-index.php"><i class="fas fa-home"></i> Home</a>
      <a href="user-profile-page.php"><i class="fas fa-user"></i> My Account</a>
      <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
</header>

<div class="container">
  <h2>
    <button class="back-button" onclick="goBack()">
      <i class="fa fa-angle-left"></i>
    </button> 
    Edit Profile
  </h2>
  <form action="edit-user-profile.php" method="post" enctype="multipart/form-data">
    <div class="profile-photo">
      <img src="<?php echo $user['profile_photo'] ? $user['profile_photo'] : 'img/default-profile.jpg'; ?>">
      <div class="overlay"></div> <!-- Dark transparent overlay -->
      <button type="button" class="edit-button" onclick="openFileDialog()">
        <i class="fas fa-pencil"></i>
      </button>
      <input type="file" id="file-input" name="profile_photo" accept="image/*" onchange="handleFileSelect(event)">
    </div>
    <div class="change-label">Change Profile</div>

    <div class="input-group">
      <label for="fullname" class="input-label">Full Name</label>
      <input type="text" id="full-name" name="fullname" class="input-field" placeholder="Full Name" value="<?php echo htmlspecialchars($user['fullname']); ?>">
    </div>

    <div class="input-group">
      <label for="username" class="input-label">Username</label>
      <input type="text" id="username" name="username" class="input-field" placeholder="Username" value="<?php echo htmlspecialchars($user['username']); ?>">
    </div>

    <div class="button-group">
      <button type="submit" class="update-button">Update</button>
    </div>
  </form>
</div>

<script>
  function toggleDropdown() {
    const dropdown = document.getElementById("dropdown-menu");
    if (dropdown.style.display === "block") {
      dropdown.style.display = "none";
    } else {
      dropdown.style.display = "block";
    }
  }

  window.onclick = function(event) {
    const dropdown = document.getElementById("dropdown-menu");
    if (!event.target.closest('.profile-icon') && !event.target.closest('.dropdown-menu')) {
      if (dropdown.style.display === "block") {
        dropdown.style.display = "none";
      }
    }
  };

  document.getElementById("dropdown-menu").addEventListener("click", function(event) {
    event.stopPropagation();
  });
</script>
</body>
</html>
