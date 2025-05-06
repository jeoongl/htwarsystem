<?php
// Start session and include database connection
session_start();
include 'includes/dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: log-in.php');
    exit();
}

// After user login or when business is selected
$user_id = $_SESSION['user_id'];

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

// Initialize variables
$photos = [];
$total_photos = 0;

// Fetch the current photos for the business
$photos = []; // Store existing photos
if ($business_id) {
  $photo_query = "SELECT photo_path FROM business_photos_tbl WHERE business_id = ?";
  $photo_stmt = $conn->prepare($photo_query);
  $photo_stmt->bind_param("i", $business_id);
  $photo_stmt->execute();
  $photo_result = $photo_stmt->get_result();

  while ($row = $photo_result->fetch_assoc()) {
      $photos[] = $row['photo_path'];
  }
  $photo_stmt->close();
}

// Calculate total existing photos
$existing_photos_count = count($photos);


// Check if form is submitted to upload a new photo or delete photos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['removed_photos']) && !empty($_POST['removed_photos'])) {
        // Convert the JSON string to an array
        $removed_photos = json_decode($_POST['removed_photos'], true);
        
        foreach ($removed_photos as $photo) {
            // Delete the photo from the database
            $delete_photo_query = "DELETE FROM business_photos_tbl WHERE business_id = ? AND photo_path = ?";
            $delete_stmt = $conn->prepare($delete_photo_query);
            $delete_stmt->bind_param("is", $business_id, $photo);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            // Optionally, delete the photo file from the server
            if (file_exists($photo)) {
                unlink($photo); // Delete the file from the server
            }
        }
    }
    
// Handle new photo uploads
if (isset($_FILES['new_photos']) && !empty($_FILES['new_photos']['name'][0])) {
    $new_photos_count = count($_FILES['new_photos']['name']); // Count of new photos

    // Check if the total exceeds the limit of 12 photos
    if (($existing_photos_count + $new_photos_count) > 12) {
        echo "<script>alert('You can only upload a maximum of 12 photos.'); window.location.href='edit-photos.php?business_id=$business_id';</script>";
        exit();
    }

    // Proceed with uploading photos
    $upload_dir = 'uploads/business_photos/';
    for ($i = 0; $i < $new_photos_count; $i++) {
        $photo_name = $_FILES['new_photos']['name'][$i];
        $tmp_name = $_FILES['new_photos']['tmp_name'][$i];
        $photo_path = $upload_dir . basename($photo_name);

        // Move uploaded file to server
        if (move_uploaded_file($tmp_name, $photo_path)) {
            // Insert new photo into the database
            $insert_photo_query = "INSERT INTO business_photos_tbl (business_id, photo_path, uploaded_by) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_photo_query);
            $insert_stmt->bind_param("isi", $business_id, $photo_path, $user_id);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
    }
}

// Redirect after successful upload
header("Location: user-store-profile.php?business_id=$business_id");
exit();
}

// After form submission for removing and uploading photos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Handling removed photos
  if (isset($_POST['removed_photos']) && !empty($_POST['removed_photos'])) {
      // Convert JSON string to array
      $removed_photos = json_decode($_POST['removed_photos'], true);
      
      foreach ($removed_photos as $photo) {
          // Delete the photo from the database
          $delete_photo_query = "DELETE FROM business_photos_tbl WHERE business_id = ? AND photo_path = ?";
          $delete_stmt = $conn->prepare($delete_photo_query);
          $delete_stmt->bind_param("is", $business_id, $photo);
          $delete_stmt->execute();
          $delete_stmt->close();
          
          // Optionally, delete the photo file from the server
          if (file_exists($photo)) {
              unlink($photo); // Remove the photo file from the server
          }
      }
  }
  
  // Handle new photo uploads
  if (isset($_FILES['new_photos']) && !empty($_FILES['new_photos']['name'][0])) {
      $upload_dir = 'uploads/business_photos/';
      $total_photos = count($_FILES['new_photos']['name']);
      
      for ($i = 0; $i < $total_photos; $i++) {
          $photo_name = $_FILES['new_photos']['name'][$i];
          $tmp_name = $_FILES['new_photos']['tmp_name'][$i];
          $photo_path = $upload_dir . basename($photo_name);
          
          // Move the uploaded file to the server directory
          if (move_uploaded_file($tmp_name, $photo_path)) {
              // Insert new photo into the database
              $insert_photo_query = "INSERT INTO business_photos_tbl (business_id, photo_path, uploaded_by) VALUES (?, ?, ?)";
              $insert_stmt = $conn->prepare($insert_photo_query);
              $insert_stmt->bind_param("isi", $business_id, $photo_path, $user_id);
              $insert_stmt->execute();
              $insert_stmt->close();
          }
      }
  }
  
  // Redirect to refresh the page after updating photos
  header("Location: edit-photos.php?business_id=$business_id");
  exit();
}

// Fetch the current number of photos
$current_photo_count_query = "SELECT COUNT(*) as photo_count FROM business_photos_tbl WHERE business_id = ?";
$current_photo_stmt = $conn->prepare($current_photo_count_query);
$current_photo_stmt->bind_param("i", $business_id);
$current_photo_stmt->execute();
$current_photo_stmt->bind_result($current_photo_count);
$current_photo_stmt->fetch();
$current_photo_stmt->close();

// Check if total photos exceed the maximum limit (12)
if (($current_photo_count + $total_photos) > 12) {
    echo "<script>alert('You can only upload a maximum of 12 photos.'); window.location.href='edit-photos.php?business_id=$business_id';</script>";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Photos</title>
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
    .container h2 {
      margin-top: 0;
      font-size: 1.5em;
      color: #ccc;
    }
    .container p {
      color: #ccc;
      font-size: 1em;
      margin-top: 5px;
    }
    .photo-box {
      background-color: #333;
      padding: 20px;
      border-radius: 10px;
      margin-top: 20px;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      align-items: center;
    }
    .photo-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      width: 100%;
      justify-content: flex-start;
    }
    .photo-item {
      position: relative;
      width: 175px;
      height: 175px;
      border-radius: 10px;
      overflow: hidden;
      margin-bottom: 10px;
    }
    .photo-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .remove-button {
      position: absolute;
      top: 0;
      right: 0;
      background-color: red;
      border: none;
      color: white;
      border-radius: 50%;
      width: 25px;
      height: 25px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }
    .upload-button {
      width: 175px;
      height: 175px;
      border: 2px dashed grey;
      border-radius: 10px;
      background-color: transparent;
      color: grey;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      flex-direction: column;
      margin-bottom: 10px;
    }
    .upload-button:hover {
      background-color: #444;
    }
    .upload-button i {
      font-size: 24px;
      margin-bottom: 5px;
    }
    #file-input {
      display: none;
    }
    .button-group {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }
    .button-group button {
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      width: 150px; /* Set width to 150px to match the previous design */
      text-align: center;
    }
    .cancel-button {
      background-color: #444;
      color: white;
    }
    .cancel-button:hover {
      background-color: #555;
    }
    .upload-button-green {
      background-color: green;
      color: white;
    }
    .upload-button-green:hover {
      background-color: darkgreen;
    }
  </style>
  <script>
    let selectedFiles = [];
    

    function toggleDropdown() {
        const dropdown = document.getElementById("dropdown-menu");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    // Close dropdown when clicking outside
    window.onclick = function(event) {
        const dropdown = document.getElementById("dropdown-menu");
        if (!event.target.closest('.profile-icon') && !event.target.closest('.dropdown-menu')) {
            dropdown.style.display = "none";
        }
    }
    let removedPhotos = []; // Store the paths of removed photos

// Remove photo from display and add to removedPhotos array
function removePhoto(photoItem, photoPath) {
    removedPhotos.push(photoPath); // Add photo path to removed list
    photoItem.remove(); // Remove photo from the grid
}

// Handle file selection for new photos
function handleFileSelect(event) {
    const files = event.target.files;
    const photoGrid = document.querySelector('.photo-grid');
    const maxPhotos = 12;
    const existingPhotos = photoGrid.children.length - 1; // Exclude upload button

    if (existingPhotos + files.length > maxPhotos) {
        alert(`You can only upload a maximum of ${maxPhotos} photos.`);
        return;
    }

    Array.from(files).forEach((file) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const photoItem = document.createElement('div');
            photoItem.classList.add('photo-item');

            const img = document.createElement('img');
            img.src = e.target.result;

            const removeButton = document.createElement('button');
            removeButton.classList.add('remove-button');
            removeButton.innerHTML = '&times;';
            removeButton.onclick = function() {
                photoItem.remove();
            };

            photoItem.appendChild(img);
            photoItem.appendChild(removeButton);
            photoGrid.insertBefore(photoItem, document.querySelector('.upload-button'));
        }
        reader.readAsDataURL(file);
    });
}

// Handle form submission and pass removed photos to the server
function handleSubmit() {
    document.getElementById('removed-photos-input').value = JSON.stringify(removedPhotos);
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
        <h2>Edit Business Photos</h2>
        <form method="POST" enctype="multipart/form-data" onsubmit="handleSubmit()">
            <div class="photo-grid">
                <?php foreach ($photos as $photo): ?>
                    <div class="photo-item">
                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="Photo">
                        <button type="button" class="remove-button" onclick="removePhoto(this.parentElement, '<?php echo htmlspecialchars($photo); ?>')">&times;</button>
                    </div>
                <?php endforeach; ?>

                <div class="upload-button" onclick="document.getElementById('file-input').click()">
                    <i class="fas fa-plus"></i>
                    <p>Upload Photo</p>
                </div>
            </div>

            <input type="file" name="new_photos[]" id="file-input" multiple style="display: none;" onchange="handleFileSelect(event)">
            <input type="hidden" name="removed_photos" id="removed-photos-input">

            <div class="button-group">
                <button type="submit" class="upload-button-green">Save</button>
                <button type="button" class="cancel-button" onclick="window.location.href='user-store-profile.php?business_id=<?php echo $business_id; ?>'">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>
