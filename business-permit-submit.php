<?php
// Start session and include database connection
session_start();
include 'includes/dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: log-in.php');
    exit();
}

// Retrieve the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

// Prepare and execute the query to fetch user information
$query = "SELECT fullname, username, email, profile_photo, role_id FROM users_tbl WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Set default profile photo if none exists
$profile_photo = !empty($user['profile_photo']) ? $user['profile_photo'] : 'img/default-profile.jpg';

// Close the user query statement
$stmt->close();

// Initialize success flag
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate the input data
    if (isset($_POST['business-permit-no']) && isset($_FILES['business-permit-photo']) && $_FILES['business-permit-photo']['error'] == 0) {
        // Retrieve session data for business information
        
        $business_name = $_SESSION['business_name'];
        $business_address = $_SESSION['business_address'];
        $opening_time = $_SESSION['opening_time']; // Added to handle opening time
        $closing_time = $_SESSION['closing_time']; // Added to handle closing time
        $business_category = $_SESSION['business_category'];
        $business_permit_no = $_POST['business-permit-no'];

        // Handle file upload
        $target_dir = "uploads/"; // Directory to store the uploaded files
        $target_file = $target_dir . basename($_FILES["business-permit-photo"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is an actual image or fake image
        $check = getimagesize($_FILES["business-permit-photo"]["tmp_name"]);
        if ($check === false) {
            echo "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size (limit to 5MB for example)
        if ($_FILES["business-permit-photo"]["size"] > 5000000) {
            echo "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
        } else {
            // If everything is ok, try to upload file
            if (move_uploaded_file($_FILES["business-permit-photo"]["tmp_name"], $target_file)) {
                // Prepare to insert data into the database
                $query = "INSERT INTO businesses_tbl (name, address, opening_time, closing_time, category, created_by, business_owner, business_permit_no, business_permit_photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                
                // Use the logged-in user's ID for created_by and bind all data including opening and closing times
                $business_owner = $_POST['business-owner'];
                $stmt->bind_param("sssssssss", $business_name, $business_address, $opening_time, $closing_time, $business_category, $user_id, $business_owner, $business_permit_no, $target_file);
                
                // Execute the statement
                if ($stmt->execute()) {
                    $success = true; // Set success flag to true
                } else {
                    echo "Error: " . $stmt->error;
                }

                // Close the statement
                $stmt->close();
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    } else {
        echo "Business permit number or photo is missing.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Header Only</title>
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

        .permit-container {
            background-color: black;
            padding: 20px;
            border-radius: 8px;
            width: 300px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            text-align: center;
            margin: 50px auto;
        }
        .permit-container h2 {
            margin-top: 0;
            font-size: 32px;
            text-align: left;
        }
        .permit-container .input-container {
            position: relative;
            width: 100%;
            margin: 25px 0;
        }
        .permit-container input {
            width: 89.5%;
            padding: 15px;
            border: 1px solid #555;
            border-radius: 4px;
            background-color: #333;
            color: white;
        }
        .permit-container button {
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
        .modal {
      display: none;
      position: fixed;
      z-index: 1;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: #333;
      color: white;
      padding: 20px;
      border-radius: 8px;
      text-align: center;
    }

    .modal-content h2 {
      font-size: 24px;
    }
  </style>
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
  

    <div class="permit-container">
    <h2>Business Permit</h2>
    <form action="" method="post" enctype="multipart/form-data">
        <div class="input-container">
            <input name="business-permit-no" placeholder="Business Permit Number" required>
        </div>
        <div class="input-container">
            <input name="business-owner" placeholder="Registered Owner's Name" required>
        </div>
        <div class="input-container">
            <input type="file" name="business-permit-photo" required>
        </div>
        <button type="submit">Submit</button>
    </form>
</div>

<div id="success-modal" class="modal">
  <div class="modal-content">
    <h2>Business registered successfully!</h2>
    <p>Redirecting to your profile page...</p>
  </div>
</div>

  <script>
    // Check if PHP set success flag to true
    <?php if ($success): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Display the modal
        const modal = document.getElementById('success-modal');
        modal.style.display = 'flex';

        // Redirect after 3 seconds
        setTimeout(function() {
            window.location.href = 'user-profile-page.php';
        }, 1000); // 1 second delay
    });
    <?php endif; ?>

    function toggleDropdown() {
      const dropdown = document.getElementById('dropdown-menu');
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    window.onclick = function(event) {
      if (!event.target.matches('.profile-icon, .profile-icon *')) {
        const dropdown = document.getElementById('dropdown-menu');
        if (dropdown.style.display === 'block') {
          dropdown.style.display = 'none';
        }
      }
    }

    // Script to trigger the file input when the label is clicked
    document.querySelector('.upload-photo-label').addEventListener('click', function() {
      document.getElementById('upload-photo').click();
    });

    // Update the input field with the selected file name
    document.getElementById('upload-photo').addEventListener('change', function() {
      const fileName = this.files[0].name;
      document.getElementById('photo-name').value = fileName;
    });
  </script>
</body>
</html>
