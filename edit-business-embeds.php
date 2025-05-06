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
    $_SESSION['business_id'] = $business_id;
} elseif (isset($_SESSION['business_id'])) {
    $business_id = $_SESSION['business_id'];
} else {
    // Exit if business ID is not found
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
$profile_photo = !empty($user['profile_photo']) ? $user['profile_photo'] : 'img/default-profile.jpg';
$stmt->close();

// Initialize variables for error/success messages
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile_no = $_POST['contact-no'];
    $email = $_POST['email'];
    $facebook = $_POST['facebook'];
    $instagram = $_POST['instagram'];
    $x_twitter = $_POST['x-twitter'];
    $linkedin = $_POST['linkedin'];
    $maps_link = $_POST['maps-link'];

    // Check if a record for the business_id already exists
    $check_query = "SELECT COUNT(*) as count FROM business_embeds_tbl WHERE business_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $business_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    // Record exists -> update; no record -> insert
    if ($row['count'] > 0) {
        $query = "UPDATE business_embeds_tbl 
                  SET contact_no = ?, email = ?, facebook = ?, instagram = ?, x_twitter = ?, linkedin = ?, google_maps_link = ?, updated_by = ? 
                  WHERE business_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssii", $mobile_no, $email, $facebook, $instagram, $x_twitter, $linkedin, $maps_link, $user_id, $business_id);
    } else {
        $query = "INSERT INTO business_embeds_tbl (business_id, contact_no, email, facebook, instagram, x_twitter, linkedin, google_maps_link, updated_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssssssi", $business_id, $mobile_no, $email, $facebook, $instagram, $x_twitter, $linkedin, $maps_link, $user_id);
    }

    // Execute the query
    if ($stmt->execute()) {
        // Redirect to the business profile page
        header("Location: user-store-profile.php?business_id=$business_id");
        exit();
    } else {
        $error_message = "Failed to update business profile.";
    }
    $stmt->close();
}

// Fetch existing business information if a record exists
$query = "SELECT contact_no, email, facebook, instagram, x_twitter, linkedin, google_maps_link 
          FROM business_embeds_tbl WHERE business_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $business_id);
$stmt->execute();
$result = $stmt->get_result();
$business = $result->fetch_assoc();

// Fallback values to handle empty results
$mobile_no = $business['contact_no'] ?? '';
$email = $business['email'] ?? '';
$facebook = $business['facebook'] ?? '';
$instagram = $business['instagram'] ?? '';
$x_twitter = $business['x_twitter'] ?? '';
$linkedin = $business['linkedin'] ?? '';
$maps_link = $business['google_maps_link'] ?? '';
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
      margin-bottom: 10px
    }
    .input-field-category {
      display: block;
      font-size: 1em;
      color: #ccc;
      margin-bottom: 0;
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
    Edit Embedded Links
    </h2>
    <form action="edit-business-embeds.php" method="post">
    <div class="input-field-category">
        <h4>Contact Information</h4>
        <div class="input-group">
            <input type="text" id="contact-no" name="contact-no" class="input-field" placeholder="Mobile No." value="<?php echo htmlspecialchars($mobile_no); ?>">
        </div>
        <div class="input-group">
            <input type="email" id="email" name="email" class="input-field" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>">
        </div>
    </div>
    
    <div class="input-field-category">
        <h4>Social Links</h4>
        <div class="input-group">
            <input type="text" id="facebook" name="facebook" class="input-field" placeholder="Facebook Link" value="<?php echo htmlspecialchars($facebook); ?>">
        </div>
        <div class="input-group">
            <input type="text" id="instagram" name="instagram" class="input-field" placeholder="Instagram Link" value="<?php echo htmlspecialchars($instagram); ?>">
        </div>
        <div class="input-group">
            <input type="text" id="x-twitter" name="x-twitter" class="input-field" placeholder="X Link" value="<?php echo htmlspecialchars($x_twitter); ?>">
        </div>
        <div class="input-group">
            <input type="text" id="linkedin" name="linkedin" class="input-field" placeholder="LinkedIn Link" value="<?php echo htmlspecialchars($linkedin); ?>">
        </div>
    </div>

    <div class="input-field-category">
        <h4>Google Maps</h4>
        <div class="input-group">
            <input type="text" id="maps-link" name="maps-link" class="input-field" placeholder="Google Maps Link" value="<?php echo htmlspecialchars($maps_link); ?>">
        </div>
    </div>

    <div class="button-group">
        <button type="submit" class="update-button">Update Information</button>
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
