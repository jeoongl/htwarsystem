<?php
// Start session and include database connection
session_start();
include 'includes/dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: log-in.php');
    exit();
}

// Get the business ID and the logged-in user ID
$user_id = $_SESSION['user_id'];

// Prioritize business_id from the URL (GET), otherwise fallback to session
if (isset($_GET['business_id'])) {
  $business_id = $_GET['business_id'];
  // Optionally, store the business_id in the session if you want to keep it for future requests
  $_SESSION['business_id'] = $business_id;
} elseif (isset($_SESSION['business_id'])) {
  $business_id = $_SESSION['business_id'];
} else {
  echo "Business ID not found.";
  exit();
}

// Fetch user information
$query = "SELECT fullname, username, email, profile_photo, role_id FROM users_tbl WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Set default profile photo if none exists
$profile_photo = !empty($user['profile_photo']) ? $user['profile_photo'] : 'img/default-profile.jpg';
$stmt->close();

// Initialize variables for error/success messages
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Retrieve the business name and address from the form
  $business_name = $_POST['business-name'];
  $business_description = $_POST['business-description'];
  $business_address = $_POST['address'];

  // Check if a new profile photo is uploaded
  if (!empty($_FILES['business_profile_photo']['name'])) {
      // Handle file upload
      $target_dir = "uploads/";
      $target_file = $target_dir . basename($_FILES["business_profile_photo"]["name"]);
      $uploadOk = 1;
      $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

      // Check if image file is a valid image
      $check = getimagesize($_FILES["business_profile_photo"]["tmp_name"]);
      if ($check === false) {
          $error_message = "File is not an image.";
          $uploadOk = 0;
      }

      // Check file size (limit to 5MB)
      if ($_FILES["business_profile_photo"]["size"] > 5000000) {
          $error_message = "Sorry, your file is too large.";
          $uploadOk = 0;
      }

      // Allow certain file formats (JPEG, PNG, JPG)
      if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
          $error_message = "Sorry, only JPG, JPEG, & PNG files are allowed.";
          $uploadOk = 0;
      }

      // Attempt to upload the file
      if ($uploadOk == 1) {
          if (move_uploaded_file($_FILES["business_profile_photo"]["tmp_name"], $target_file)) {
              // Update the database with the new profile photo path
              $query = "UPDATE businesses_tbl SET business_profile_photo = ? WHERE id = ?";
              $stmt = $conn->prepare($query);
              $stmt->bind_param("si", $target_file, $business_id);
              $stmt->execute();
              $stmt->close();

              $success_message = "Profile photo updated successfully.";
          } else {
              $error_message = "Sorry, there was an error uploading your file.";
          }
      }
  }

  // Update business name and address
  $query = "UPDATE businesses_tbl SET name = ?, description = ?, address = ? WHERE id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("sssi", $business_name, $business_description, $business_address, $business_id);
  
  if ($stmt->execute()) {
      // Redirect to business-owner-store-profile.php after a successful update
      header("Location: user-store-profile.php?business_id=$business_id");
      exit();
  } else {
      $error_message = "Failed to update business profile.";
  }
  $stmt->close();
}


// Fetch the updated business information to reflect changes
$query = "SELECT business_profile_photo, name, description, address FROM businesses_tbl WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $business_id);
$stmt->execute();
$result = $stmt->get_result();
$business = $result->fetch_assoc();
$business_profile_photo = !empty($business['business_profile_photo']) ? $business['business_profile_photo'] : 'img/default-store-profile.jpg';
$business_name = $business['name'];
$business_description = $business['description'];
$business_address = $business['address'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Business Profile</title>
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
            /* Adjusted CSS for the name below the profile photo */
        .dropdown-menu div {
            text-align: center;
            padding: 10px 0; /* Reduced padding */
            background-color: #444;
        }
        .dropdown-menu div p {
            font-size: 20px;
            margin: 5px 0; /* Reduced margin for name */
        }
        
        /* Add margin at the top of the profile photo */
        .dropdown-menu div img {
            margin-top: 5px; /* Increased top space for the photo */
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
    .description-field {
      height: 200px; /* Adjust height as needed */
      text-align: justify;
      vertical-align: top;
      padding-top: 10px;
      resize: none; /* Optional: Prevent users from resizing the textarea */
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
      margin-right: 20px;
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
    .description-field {
      height: 200px; /* Adjust height as needed */
      text-align: justify;
      vertical-align: top;
      padding-top: 10px;
      resize: none; /* Optional: Prevent users from resizing the textarea */
      white-space: pre-wrap;
    }
  </style>
  <script>
        function toggleDropdown() {
        const dropdown = document.getElementById("dropdown-menu");
        // Toggle dropdown display
        if (dropdown.style.display === "block") {
            dropdown.style.display = "none";
        } else {
            dropdown.style.display = "block";
        }
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
        const dropdown = document.getElementById("dropdown-menu");
        // If the click is outside the profile-icon and dropdown menu
        if (!event.target.closest('.profile-icon') && !event.target.closest('.dropdown-menu')) {
            if (dropdown.style.display === "block") {
            dropdown.style.display = "none";
            }
        }
        }

        // Prevent dropdown from closing when clicking inside the dropdown menu
        document.getElementById("dropdown-menu").addEventListener("click", function(event) {
        event.stopPropagation();
        });

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
      <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Photo" style="width: 40px; height: 40px; border-radius: 50%;">
      <div class="dropdown-menu" id="dropdown-menu">
        <div>
          <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Photo" style="width: 60px; height: 60px; border-radius: 50%;">
          <p><?php echo htmlspecialchars($user['fullname']); ?></p>
        </div>
        <a href="business-owner-login-index.php"><i class="fas fa-home"></i> Home</a>
        <a href="business-owner-profile-page.php"><i class="fas fa-user"></i> My Account</a>
        <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
</header>

<div class="container">
  <h2>
    <button class="back-button" onclick="goBack()">
      <i class="fa fa-angle-left"></i>
    </button> 
    Edit Business
  </h2>
  <form action="edit-business-profile.php" method="post" enctype="multipart/form-data">
    <div class="profile-photo">
      <img src="<?php echo htmlspecialchars($business_profile_photo); ?>" alt="Business Profile Photo">
      <div class="overlay"></div>
      <button type="button" class="edit-button" onclick="openFileDialog()">
        <i class="fas fa-pencil"></i>
      </button>
      <input type="file" id="file-input" name="business_profile_photo" accept="image/*" onchange="handleFileSelect(event)">
    </div>
    <div class="change-label">Change Profile</div>

    <div class="input-group">
      <label for="business-name" class="input-label">Business Name</label>
      <input type="text" id="business-name" name="business-name" class="input-field" placeholder="Business Name" value="<?php echo htmlspecialchars($business_name); ?>">
    </div>

    <div class="input-group">
      <label for="business-description" class="input-label">Business Description</label>
      <textarea id="business-description" name="business-description" class="input-field description-field" placeholder="Enter business description here..."><?php echo htmlspecialchars($business_description); ?></textarea>
    </div>

    <div class="input-group">
      <label for="address" class="input-label">Address</label>
      <input type="text" id="address" name="address" class="input-field" placeholder="Address" value="<?php echo htmlspecialchars($business_address); ?>">
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
