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

// Fetch business details based on the business ID (this can be passed via GET request)
$business_id = isset($_GET['business_id']) ? $_GET['business_id'] : null;
$show_owners_button = false; // Initialize a variable to control the button visibility

if ($business_id) {
    $business_query = "SELECT name, business_profile_photo, address, opening_time, closing_time, description, business_owner, business_permit_no, business_permit_photo, category, created_by FROM businesses_tbl WHERE id = ?";
    $business_stmt = $conn->prepare($business_query);
    $business_stmt->bind_param("i", $business_id);
    $business_stmt->execute();
    $business_result = $business_stmt->get_result();
    $business = $business_result->fetch_assoc();
    
    // Set default business profile photo if none exists
    $business_profile_photo = !empty($business['business_profile_photo']) ? $business['business_profile_photo'] : 'img/default-store-profile.jpg';

    // Check if the logged-in user is the owner of the business
    if ($business['created_by'] == $user_id) {
        $show_owners_button = true; // User owns the business, show the manage button
    }

    // Fetch opening and closing times
    $opening_time = $business['opening_time'];
    $closing_time = $business['closing_time'];
    $category = $business['category'];

    $business_stmt->close();
} else {
    // Handle the case where no business is selected (optional)
    $business = ['name' => 'N/A', 'address' => 'N/A', 'description' => 'No description available.'];
    $business_profile_photo = 'img/default-store-profile.jpg'; // Default if no business is selected
    $opening_time = '08:00:00'; // Set default opening time if needed
    $closing_time = '18:00:00'; // Set default closing time if needed
    $category = 4; // Default category (optional)
}

if ($business_id) {
  // Fetch business contact and social media information
  $embed_query = "SELECT contact_no, email, facebook, instagram, x_twitter, linkedin, google_maps_link FROM business_embeds_tbl WHERE business_id = ?";
  $embed_stmt = $conn->prepare($embed_query);
  $embed_stmt->bind_param("i", $business_id);
  $embed_stmt->execute();
  $embed_result = $embed_stmt->get_result();
  $embed_data = $embed_result->fetch_assoc();
  $embed_stmt->close();
}


if ($business_id) {
  // Fetch business photos dynamically
  $photo_query = "SELECT photo_path FROM business_photos_tbl WHERE business_id = ?";
  $photo_stmt = $conn->prepare($photo_query);
  $photo_stmt->bind_param("i", $business_id);
  $photo_stmt->execute();
  $photo_result = $photo_stmt->get_result();
  
  $photos = [];
  while ($row = $photo_result->fetch_assoc()) {
      $photos[] = $row['photo_path'];
  }
  $photo_stmt->close();
}

// Check if $photos is an array before using foreach
$photo_count = 0;
$photo_urls = [];
if (is_array($photos)) {
    foreach ($photos as $photo) {
        if (!empty($photo)) {
            $photo_count++;
            $photo_urls[] = $photo; // Store valid photos
        }
    }
}

// Default grid layout based on the number of photos
if ($photo_count === 1) {
    $columns = "repeat(1, 0.5fr)";
    $rows = "repeat(1, 1fr)";
} elseif ($photo_count === 2) {
    $columns = "repeat(2, 1fr)";
    $rows = "repeat(1, 1fr)";
} elseif ($photo_count <= 4) {
    $columns = "repeat(3, 1fr)";
    $rows = "repeat(1, 1fr)";
} elseif ($photo_count <= 6) {
    $columns = "repeat(3, 1fr)";
    $rows = "repeat(2, 1fr)";
} else {
    $columns = "repeat(4, 1fr)";
    $rows = "repeat(3, 1fr)";
}


// Initialize variables to prevent undefined variable warnings
$adults = 0; 
$children = 0; 
$rooms = 0;

// Check if relevant GET parameters are set and category is 3 (room query)
if ($category === 3 && isset($_GET['check_in'], $_GET['check_out'], $_GET['adults'], $_GET['children'], $_GET['rooms'])) {
    $check_in = htmlspecialchars($_GET['check_in']);
    $check_out = htmlspecialchars($_GET['check_out']);
    $adults = intval($_GET['adults']);
    $children = intval($_GET['children']);
    $rooms_requested = intval($_GET['rooms']);
}

if ($category === 3) {
    // Calculate the total number of people (adults + children) if category is 3
    $total_people = $adults + $children;

    // Query to fetch room types and availability
    $room_query = "
        SELECT 
            r.id, 
            r.room_type, 
            (r.total_rooms - COALESCE(SUM(hr.num_rooms), 0)) AS available_rooms
        FROM rooms_tbl r
        LEFT JOIN reservations_tbl hr 
            ON r.id = hr.room_id
            AND NOT (
                hr.check_out <= ? OR hr.check_in >= ?
            )
        WHERE r.business_id = ?
        AND r.max_occupancy >= ?  -- Ensure room can accommodate total people
        GROUP BY r.room_type, r.total_rooms
        HAVING available_rooms >= ?"; // Ensure enough rooms are available

    // Prepare the query
    $room_stmt = $conn->prepare($room_query);

    // Bind parameters: check_in, check_out (string), business_id (int), total_people (int), rooms (int)
    $room_stmt->bind_param("ssiii", $check_in, $check_out, $business_id, $total_people, $rooms_requested);

    // Execute the query
    $room_stmt->execute();

    // Get the result
    $room_result = $room_stmt->get_result();
    $rooms = [];
    while ($row = $room_result->fetch_assoc()) {
        $rooms[] = $row; // Store room type and availability
    }

    // Close the statement
    $room_stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tourist Info Page</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

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
    .info-container {
      width: 90%;
      margin: 20px auto;
      background-color: #333;
      padding: 20px;
      border-radius: 8px;
    }
    .details-container {
      width: 30%;
      float: left;
      margin-top: 10px;
    }
    .photo-display-container {
      width: 65%;
      float: right;
      margin-bottom: 20px;
    }
    .description {
      margin-bottom: 20px;
      font-size: 16px;
    }
    .google-maps-container {
      width: 100%; /* Set the container to full width or adjust as needed */
      max-width: 800px; /* Optional: limit the maximum width */
      height: 300px; /* Set a fixed height or adjust as needed */
      border: 1px solid #444; /* Optional: add a border for visibility */
      border-radius: 5px;
      padding: 0; /* Optional: add padding inside the container */
      margin: 20px 0; /* Optional: add margin to separate from other elements */
      background-color: #333; /* Optional: background color for the container */
      overflow: hidden; /* Optional: hides overflow content */
      display: flex; /* Use flexbox to center content */
      justify-content: center; /* Horizontally center */
      align-items: center; /* Vertically center */
      text-align: center; /* Center text inside */
     }

    .buttons-container {
      display: flex;
      gap: 10px;
      margin-top: 20px;
      margin-bottom: 20px;
    }
    .manage-reservations-btn {
      background-color: green;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      flex-grow: 1;
      text-align: center;
      font-size: 14px;
    }
    .manage-reservations-btn:hover {
      background-color: gray;
      color: white;
    }
    .settings-btn, .share-btn{
      background-color: #4f4f4f;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      flex-grow: 1;
      text-align: center;
      font-size: 14px;
    }
    .settings-btn:hover,
    .contact-btn:hover{
      background-color: gray;
    }
    .edit-embeds-btn {
      background-color: #4f4f4f; /* Gray background to match the other buttons */
      color: white; /* White text */
      padding: 10px 20px; /* Add padding for size */
      border: none; /* No border */
      border-radius: 4px; /* Rounded corners */
      cursor: pointer; /* Change cursor to pointer on hover */
      text-align: center; /* Center the text */
      font-size: 14px; /* Adjust the font size */
      transition: background-color 0.3s ease; /* Smooth transition for background color */
    }

    .edit-embeds-btn:hover {
      background-color: gray; /* Change background color on hover */
    }
    .photo-display {
      width: 100%;
    }

    /* Update the .photo-display-container to position the edit button */
    .photo-display-container {
      position: relative; /* Add this line */
      width: 65%;
      float: right;
      margin-bottom: 20px;
    }

    /* Style the photo gallery header with the "Photos" text */
    .photo-gallery-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 10px;
      margin-bottom: 10px; /* Adjust the margin as needed */
    }

    .photo {
      border-radius: 5px;
      cursor: pointer;
    }
    /* Align the edit button on the right */
    .edit-photos-btn {
      background-color: #4f4f4f;
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }

    .edit-photos-btn:hover {
      background-color: gray;
    }

    /* Update the .photo-gallery to dynamically adjust based on the photo count */
    .photo-gallery {
    display: grid;
    gap: 10px;
    grid-template-columns: <?php echo $columns; ?>;
    grid-template-rows: <?php echo $rows; ?>;
  }
    .photo {
      position: relative;
      width: 100%;
      padding-top: 100%; /* 1:1 Aspect Ratio */
      overflow: hidden;
      border-radius: 5px;
    }
    .photo img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .store-profile h2 {
      margin-bottom: 0; /* Reduce this value to minimize space */
    }
    .store-profile {
      display: flex;
      align-items: flex-start;
      margin-bottom: 20px;
      position: relative;
    }

    .store-profile-photo {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin-right: 20px;
      object-fit: cover;
      flex-shrink: 0;
    }

    .h2-container {
      position: relative;
      display: flex;
      align-items: flex-start;
      width: 100%;
    }

    .business-name {
      margin: 0;
      flex-grow: 1;
      word-break: break-word;
      white-space: normal;
      max-width: 90%; /* Ensures the name takes up most of the width */
    }

    .edit-profile {
      position: absolute;
      right: 0;
      top: 0;
      cursor: pointer;
      margin-left: 10px;
    }
    .edit-profile a {
      color: white; /* Change the icon color to white */
      transition: transform 0.2s; /* Smooth transition for the hover effect */
    }

    .edit-profile i:hover {
      transform: scale(1.1); /* Enlarge the icon on hover */
    }

    .address {
      display: flex;
      align-items: center;
    }
    .address i {
      margin-right: 10px;
    }
    .separator {
      height: 1px;
      background-color: #444;
      width: 100%;
      margin: 20px 0;
      clear: both;
    }
    .owners-separator-design {
      margin: 0;
      background-color: transparent;
    }
    .business-permit, .delete-section {
      margin: 0;
      padding: 0 0;
    }
    .business-permit-header, .delete-section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      margin: 0;
    }
    .business-permit-header h3, .delete-section-header h3 {
      margin: 0;
    }
    .business-permit p, .delete-section p {
      margin: 5px 0;
      line-height: 1.5;
    }
    .edit-permit-btn, .delete-button {
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      text-align: center;
      font-size: 14px;
      color: white;
    }
    .edit-permit-btn {
      background-color: #4f4f4f;
    }
    .edit-permit-btn:hover {
      background-color: gray;
    }
    .delete-button {
      background-color: red;
    }
    .delete-button:hover {
      background-color: darkred;
    }

    /* Contact and Social Icons */
    .embedded-links-container {
        margin-top: 0;
        margin-bottom: 20px;
        padding-top: 10px;
        display: flex;
        justify-content: center; /* Center them horizontally */
        gap: 10px; /* Space between icons */
    }

    /* Style for both Contact Info and Social Media Icons */
    .embedded-links-container a {
        padding-top: 5px;
        color: white; /* Set icon color to white */
        font-size: 24px; /* Icon size */
        text-decoration: none;
        transition: color 0.1s; /* Smooth transition for hover effect */
    }
    .embedded-links-container p {
        font-size: 15px; /* Icon size */
        padding-top: 10px;
        margin: 0;
        transition: color 0.1s; /* Smooth transition for hover effect */
    }

    /* Hover effect to change the color */
    .embedded-links-container a:hover {
        color: gray; /* Change icon color to gray on hover */
    }

    .permit-content {
        display: flex;
        align-items: center; /* Align the image and text vertically */
    }

    .permit-photo img {
        width: 50px;
        height: 50px; /* Make the image square */
        object-fit: cover; /* Ensure the image maintains aspect ratio */
        margin-right: 20px;
        border-radius: 5px;
    }

    .permit-details p {
        margin: 0; /* Remove default paragraph margins */
        font-size: 16px;
        color: #fff; /* Adjust text color */
        line-height: 1.5; /* Adjust line spacing */
    }
    .hours-container {
      display: flex;
      align-items: center;
      gap: 10px; /* Adds some space between the items */
    }

    .hours-container i {
      font-size: 1.2rem; /* Adjust the icon size */
    }

    .hours-container p {
      margin: 0;
    }

    .store-status {
      margin-left: 10px; /* Adds space between the time range and the "Open/Closed" sign */
    }

    .open-sign {
      color: green;
      font-weight: bold;
    }

    .closed-sign {
      color: red;
      font-weight: bold;
    }
    .reservation-label {
    font-size: 18px;
    font-weight: bold;
    margin: 10px 0 10px 0;
    display: block;
}

.reservation-form {
    display: flex; /* Use flexbox for horizontal alignment */
    align-items: center; /* Center items vertically */
    gap: 15px; /* Space between inputs */
    flex-wrap: wrap; /* Allow wrapping on smaller screens */
    width: 100%; /* Ensure the form takes up the full width */
}

/* General input styles */
.reservation-form input[type="text"] {
    background-color: #444; /* Dark background */
    color: #fff; /* White text */
    border: 1px solid #666; /* Light border for contrast */
    border-radius: 5px; /* Rounded corners */
    height: 40px; /* Consistent height */
    width: 100%; /* Allow it to scale with container */
    padding: 10px; /* Padding for better spacing */
    box-sizing: border-box; /* Include padding and border in total width */
    flex-grow: 1; /* Make input fields grow to fill available space */
}

/* Specific styles for input with icons */
.time-input-wrapper,
.date-input-wrapper,
.people-input-wrapper,
.bed-room-input-wrapper {
    position: relative; /* Position for absolute icon */
    flex-grow: 1; /* Make wrappers grow equally */
    flex-basis: 0; /* Allow them to shrink equally */
}

.time-input-wrapper input,
.date-input-wrapper input,
.people-input-wrapper input,
.bed-room-input-wrapper input {
    padding-right: 30px; /* Space for icon */
}

/* Icons styling */
.time-icon,
.people-icon, 
.calendar-icon,
.bed-icon{
    position: absolute;
    right: 10px; /* Space from the right */
    top: 50%; /* Center vertically */
    transform: translateY(-50%); /* Adjust for vertical alignment */
    color: #fff; /* White color for icons */
    pointer-events: none; /* Prevent clicks on icons */
}

/* Reserve button styling */
.reserve-btn {
    background-color: #28a745; /* Green background */
    color: #fff; /* White text */
    border: none; /* No border */
    border-radius: 5px; /* Rounded corners */
    height: 40px; /* Match height */
    padding: 10px; /* Padding inside the button */
    flex-grow: 1; /* Make the button grow equally */
    flex-basis: 0; /* Ensure it shrinks or grows equally with inputs */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s; /* Smooth background transition */
}

.reserve-btn:hover {
    background-color: #218838; /* Darker green on hover */
}


/* Modal styling */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1000; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgba(0, 0, 0, 0.8); /* Dark background with some transparency */
}

/* Modal content */
.modal-content {
    background-color: #333; /* Dark background for content */
    margin: 2% auto; /* Reduced margin from 15% to 5% or change to 0 for no margin */
    padding: 20px;
    border-radius: 5px;
    width: 80%; /* Could be more or less, depending on screen size */
    color: #fff; /* Light text for contrast */
}

/* Close button */
.modal-content .close {
    color: #fff;
    float: right;
    right: 10px;
    font-size: 25px;
}

.modal-content .close:hover,
.modal-content .close:focus {
    color: #ccc;
    text-decoration: none;
    cursor: pointer;
}

/* Modal option list */
.modal-content ul {
    list-style-type: none;
    padding: 0;
}

.modal-content ul li {
    display: flex;
    align-items: center;
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #444;
    color: #fff;
}

.modal-content ul li:hover {
    background-color: #555; /* Highlight on hover */
}

/* Circle styles for options */
.modal-content ul li .circle {
    width: 20px; 
    height: 20px; 
    border-radius: 50%; 
    border: 2px solid #fff; /* Circle border */
    margin-right: 10px; 
    display: inline-block; 
}

.modal-content ul li.selected .circle {
    background-color: #fff; /* Fill color for selected */
}

/* Additional style for indicating the currently selected value */
.modal-content ul li .circle.selected {
    background-color: #fff; /* Fill color for selected circle */
}


.date-input-wrapper {
    position: relative;
}

.date-input-wrapper input {
    background-color: #444;
    color: #fff;
    border: 1px solid #666;
    border-radius: 5px;
    height: 40px;
    width: 150px;
    padding: 10px;
    padding-right: 30px; /* Space for icon */
}

.calendar-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #fff;
    pointer-events: none;
}
.room-option {
    display: flex;
    align-items: center;
    padding: 10px;
    cursor: pointer;
    transition: background-color;
}

.room-option:hover {
    background-color: #f0f0f0;
}

.room-option .circle {
    width: 16px;
    height: 16px;
    border: 2px solid white;
    border-radius: 50%;
    margin-right: 10px;
    transition: background-color, border-color;
}

.room-option.selected .circle {
    background-color: white;
    border-color: white;
}
.floating-home {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
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


    @media only screen and (max-width: 767px) {
      .logo {
        width: 250px;
      }
      .info-container {
        width: 100%;
      }
      .details-container, .photo-display-container {
        float: none;
        width: 100%;
      }
      .photo-gallery {
        grid-template-columns: repeat(2, 1fr);
      }
      .photo {
        padding-top: 100%; /* Maintain 1:1 aspect ratio */
      }
    }
  </style>
  <script>
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

    function openLightbox(index) {
      const lightbox = document.getElementById('lightbox');
      const lightboxImage = document.getElementById('lightbox-image');
      const images = document.querySelectorAll('.photo img');
      lightboxImage.src = images[index].src;
      lightbox.style.display = 'flex';
      lightbox.dataset.currentIndex = index;
    }

    function closeLightbox() {
      const lightbox = document.getElementById('lightbox');
      lightbox.style.display = 'none';
    }

    function showNextImage(event) {
      event.stopPropagation();
      const lightbox = document.getElementById('lightbox');
      let currentIndex = parseInt(lightbox.dataset.currentIndex);
      const images = document.querySelectorAll('.photo img');
      currentIndex = (currentIndex + 1) % images.length;
      document.getElementById('lightbox-image').src = images[currentIndex].src;
      lightbox.dataset.currentIndex = currentIndex;
    }

    function showPrevImage(event) {
      event.stopPropagation();
      const lightbox = document.getElementById('lightbox');
      let currentIndex = parseInt(lightbox.dataset.currentIndex);
      const images = document.querySelectorAll('.photo img');
      currentIndex = (currentIndex - 1 + images.length) % images.length;
      document.getElementById('lightbox-image').src = images[currentIndex].src;
      lightbox.dataset.currentIndex = currentIndex;
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
<div class="info-container">
  <div class="details-container">
    <div class="store-profile">
      <img src="<?php echo htmlspecialchars($business_profile_photo); ?>" alt="Store Profile Photo" class="store-profile-photo">
      <div>
      <div class="h2-container">
        <h2 class="business-name"><?php echo htmlspecialchars($business['name']); ?></h2>
        <?php if ($show_owners_button && $business_id): ?>
        <div class="edit-profile">
            <a href="edit-business-profile.php?business_id=<?= $business_id ?>" title="Edit Business Profile">
              <i class="fas fa-user-edit"></i>
            </a>
        </div>
        <?php endif; ?>
      </div>
        <div class="address">
          <i class="fas fa-map-marker-alt"></i>
          <p><?php echo htmlspecialchars($business['address']); ?></p>
        </div>
        
        <div class="business-hours">
          <div class="hours-container">
            <i class="fas fa-clock"></i>
            <p>
              <?php
                if (!empty($business['opening_time'])) {
                  $openingTime = new DateTime($business['opening_time']);
                  echo $openingTime->format('h:i A');
                } else {
                  echo 'N/A';
                }
              ?> 
              - 
              <?php
                if (!empty($business['closing_time'])) {
                  $closingTime = new DateTime($business['closing_time']);
                  echo $closingTime->format('h:i A');
                } else {
                  echo 'N/A';
                }
              ?>
            </p>
            <p class="store-status">
            <?php
              // Get current time in the Philippines (Asia/Manila timezone)
              $timezone = new DateTimeZone('Asia/Manila');
              $currentTime = new DateTime('now', $timezone);

              if (!empty($business['opening_time']) && !empty($business['closing_time'])) {
                // Initialize the opening and closing times
                $openingTime = new DateTime($business['opening_time'], $timezone);
                $closingTime = new DateTime($business['closing_time'], $timezone);

                // Check if the current time is within the opening and closing times
                if ($currentTime >= $openingTime && $currentTime <= $closingTime) {
                  echo '<span class="open-sign">Open</span>';
                } else {
                  echo '<span class="closed-sign">Closed</span>';
                }
              } else {
                echo '<span class="closed-sign">Closed</span>';
              }
            ?>
          </p>
          </div>
        </div>
      </div>
    </div>

    <p class="description"><?php echo nl2br(htmlspecialchars($business['description'])); ?></p>
    
    <div class="google-maps-container">
      <?php 
      // Check if google_maps_link exists before displaying it
      if (!empty($embed_data['google_maps_link'])) {
          echo $embed_data['google_maps_link'];
      } else {
          // Optionally, display a placeholder or nothing at all
          echo "No Google Maps link attached.";
      }
      ?>
    </div>
    <div class="buttons-container">
            <?php if ($show_owners_button): ?>
              <button class="manage-reservations-btn" onclick="location.href='manage-reservations.php?business_id=<?= $business_id ?>'">Manage Reservations</button>
              <button class="settings-btn" onclick="location.href='user-settings.php?business_id=<?= $business_id ?>'">Business Settings</button>
            <?php else: ?>
              <button class="share-btn" onclick="location.href='contact-business.php?business_id=<?= $business_id ?>'">Share</button>
            <?php endif; ?>
        </div>

        <?php if ($show_owners_button && $business_id): ?>
    <div class="embedded-links-container">
            <?php if (!empty($embed_data['contact_no'])): ?>
                <a href="tel:<?php echo htmlspecialchars($embed_data['contact_no']); ?>">
                    <i class="fas fa-phone"></i> <!-- Only icon, no text -->
                </a>
            <?php endif; ?>

            <?php if (!empty($embed_data['email'])): ?>
                <a href="mailto:<?php echo htmlspecialchars($embed_data['email']); ?>">
                    <i class="fas fa-envelope"></i> <!-- Only icon, no text -->
                </a>
            <?php endif; ?>

            <?php if (!empty($embed_data['facebook'])): ?>
                <a href="<?php echo htmlspecialchars($embed_data['facebook']); ?>" target="_blank"><i class="fab fa-facebook"></i></a>
            <?php endif; ?>

            <?php if (!empty($embed_data['instagram'])): ?>
                <a href="<?php echo htmlspecialchars($embed_data['instagram']); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
            <?php endif; ?>

            <?php if (!empty($embed_data['x_twitter'])): ?>
                <a href="<?php echo htmlspecialchars($embed_data['x_twitter']); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
            <?php endif; ?>

            <?php if (!empty($embed_data['linkedin'])): ?>
                <a href="<?php echo htmlspecialchars($embed_data['linkedin']); ?>" target="_blank"><i class="fab fa-linkedin"></i></a>
            <?php endif; ?>
            
            <!-- Check if no social media or contact information is available -->
            <?php if (empty($embed_data['facebook']) && empty($embed_data['instagram']) && empty($embed_data['x_twitter']) && empty($embed_data['linkedin']) && empty($embed_data['contact_no']) && empty($embed_data['email'])): ?>
                <p>No Embedded Links attached.</p>
            <?php endif; ?>
          
        </div>
<?php else: ?>
  <div class="embedded-links-container">
            <?php if (!empty($embed_data['contact_no'])): ?>
                <a href="tel:<?php echo htmlspecialchars($embed_data['contact_no']); ?>">
                    <i class="fas fa-phone"></i> <!-- Only icon, no text -->
                </a>
            <?php endif; ?>

            <?php if (!empty($embed_data['email'])): ?>
                <a href="mailto:<?php echo htmlspecialchars($embed_data['email']); ?>">
                    <i class="fas fa-envelope"></i> <!-- Only icon, no text -->
                </a>
            <?php endif; ?>

            <?php if (!empty($embed_data['facebook'])): ?>
                <a href="<?php echo htmlspecialchars($embed_data['facebook']); ?>" target="_blank"><i class="fab fa-facebook"></i></a>
            <?php endif; ?>

            <?php if (!empty($embed_data['instagram'])): ?>
                <a href="<?php echo htmlspecialchars($embed_data['instagram']); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
            <?php endif; ?>

            <?php if (!empty($embed_data['x_twitter'])): ?>
                <a href="<?php echo htmlspecialchars($embed_data['x_twitter']); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
            <?php endif; ?>

            <?php if (!empty($embed_data['linkedin'])): ?>
                <a href="<?php echo htmlspecialchars($embed_data['linkedin']); ?>" target="_blank"><i class="fab fa-linkedin"></i></a>
            <?php endif; ?>
      </div>
<?php endif; ?>
  </div>

  <div class="photo-display-container">

  <?php if ($category == 1): ?>
    <!-- Boat Reservation Form (Category 1) -->
    <form action="user-boat-reservation-form.php" method="POST">
        <input type="hidden" name="business_id" value="<?php echo $business_id; ?>">
        <label for="reservation-form" class="reservation-label">Reserve a Boat</label>

        <!-- Input fields for date and number of people (no time input) -->
        <div class="reservation-form">
            <div class="date-input-wrapper">
                <input type="text" id="date-input-boat" name="reservation_date" placeholder="Select Date" readonly required>
                <i class="fa fa-calendar calendar-icon"></i>
            </div>

            <!-- Number of people input with icon -->
            <div class="people-input-wrapper">
                <input type="text" id="num-people-input-boat" name="num_people" placeholder="No. of People" readonly required>
                <i class="fa fa-users people-icon"></i>
            </div>

            <!-- Submit button -->
            <button type="submit" class="reserve-btn">Reserve Boat</button>
        </div>
    </form>
<?php endif; ?>

<?php if ($category == 2): ?>
    <!-- Eco Attraction Reservation Form (Category 2) -->
    <form action="user-eco-attraction-reservation-form.php" method="POST">
        <input type="hidden" name="business_id" value="<?php echo $business_id; ?>">
        <label for="reservation-form" class="reservation-label">Reserve Eco Attraction</label>

        <!-- Input fields for date and number of people (no time input) -->
        <div class="reservation-form">
            <div class="date-input-wrapper">
                <input type="text" id="date-input-eco" name="reservation_date" placeholder="Select Date" readonly required>
                <i class="fa fa-calendar calendar-icon"></i>
            </div>

            <!-- Number of people input with icon -->
            <div class="people-input-wrapper">
                <input type="text" id="num-people-input-eco" name="num_people" placeholder="No. of People" readonly required>
                <i class="fa fa-users people-icon"></i>
            </div>

            <!-- Submit button -->
            <button type="submit" class="reserve-btn">Reserve Attraction</button>
        </div>
    </form>
<?php endif; ?>

    
<?php if ($category == 3): ?>
    <!-- Add form with POST method to pass reservation data -->
    <form action="user-reserve-room.php" method="POST">
        <input type="hidden" name="business_id" value="<?php echo $business_id; ?>">
        <input type="hidden" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>">
        <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>">
        <input type="hidden" name="adults" value="<?php echo intval($adults); ?>">
        <input type="hidden" name="children" value="<?php echo intval($children); ?>">
        <input type="hidden" name="rooms" value="<?php echo intval($rooms_requested); ?>">
        <input type="hidden" id="room-type" name="room_type" value="">
        <input type="hidden" id="room-id" name="room_id" value="">

        <div class="additional-info"></div>
        <label for="reservation-form" class="reservation-label">Make a reservation</label>
        <div class="reservation-form">
            <p>
                <?php
                $check_in_date = new DateTime($check_in);
                $check_out_date = new DateTime($check_out);
                echo '<strong><i class="fa fa-calendar"></i></strong> ' 
                     . $check_in_date->format('M d, Y') 
                     . ' - ' 
                     . $check_out_date->format('M d, Y');
                ?><br>
                <strong><i class="fas fa-users"></i> <?php echo intval($adults); ?></strong> 
                <?php echo (intval($adults) === 1) ? "Adult" : "Adults"; ?> ·
                <strong><?php echo intval($children); ?></strong> 
                <?php echo (intval($children) === 1) ? "Child" : "Children"; ?> ·
                <strong><?php echo intval($rooms_requested); ?></strong> 
                <?php echo (intval($rooms_requested) === 1) ? "Room" : "Rooms"; ?>
            </p>
            <div class="bed-room-input-wrapper">
                <input type="text" id="room-input" name="room_reservation_display" placeholder="Select Room" readonly required>
                <i class="fa fa-bed bed-icon"></i>
            </div>
            <button type="submit" class="reserve-btn">Reserve Room/s</button>
        </div>
    </form>
<?php endif; ?>

    <?php if ($category == 4): ?>
    <!-- Boat Reservation Form (Category 1) -->
    <!-- Add form with POST method to pass reservation data for Category 4 -->
    <form action="user-reserve-table.php" method="POST">
        <input type="hidden" name="business_id" value="<?php echo $business_id; ?>">
        <label for="reservation-form" class="reservation-label">Make a reservation</label>

        <!-- Date input -->
        <div class="reservation-form">
            <div class="date-input-wrapper">
                <input type="text" id="date-input" name="reservation_date" placeholder="Select Date" readonly required>
                <i class="fa fa-calendar calendar-icon"></i>
            </div>

            <!-- Time input -->
            <div class="time-input-wrapper">
                <input type="text" id="time-input" name="reservation_time" placeholder="Select Time" readonly required>
                <i class="fa fa-clock time-icon"></i>
            </div>

            <!-- Number of people input -->
            <div class="people-input-wrapper">
                <input type="text" id="num-people-input" name="num_people" placeholder="No. of People" readonly required>
                <i class="fa fa-users people-icon"></i>
            </div>

            <!-- Submit button -->
            <button type="submit" class="reserve-btn">Reserve Table</button>
        </div>
    </form>
<?php endif; ?>


    <div class="photo-gallery-header">
      <h3>Photos</h3>
      <?php if ($show_owners_button && $business_id): ?>
        <button class="edit-photos-btn" onclick="location.href='edit-photos.php?business_id=<?= $business_id ?>'">Edit Photos</button>
      <?php endif; ?>
    </div>

    <!-- Photo gallery display -->
    <?php if ($photo_count > 0): ?>
      <div class="photo-gallery" style="grid-template-columns: <?php echo $columns; ?>; grid-template-rows: <?php echo $rows; ?>;">
        <?php foreach ($photo_urls as $index => $photo_url): ?>
          <div class="photo" onclick="openLightbox(<?php echo $index; ?>)">
            <img src="<?php echo htmlspecialchars($photo_url); ?>" alt="Photo">
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p style="color: white;">No photos available. Please upload or edit.</p>
    <?php endif; ?>
  </div>

    <div class="separator <?php if ($business['created_by'] != $user_id) { echo 'owners-separator-design'; } ?>"></div>

    <?php if ($show_owners_button && $business_id): ?>
      <div class="business-permit">
    <div class="business-permit-header">
        <h3>Business Permit</h3>
        <button class="edit-permit-btn" onclick="location.href='edit-business-permit.php?business_id=<?= $business_id ?>'">
            Edit Permit
        </button>
    </div>

    <!-- Business Permit Photo and Info Container -->
    <div class="permit-content">
        <!-- Business Permit Image -->
        <div class="permit-photo">
            <img src="<?php echo htmlspecialchars($business['business_permit_photo']); ?>" alt="Business Permit">
        </div>

        <!-- Business Permit No and Owner Info -->
        <div class="permit-details">
            <p>Permit No.: <?php echo htmlspecialchars($business['business_permit_no']); ?></p>
            <p>Business Owner: <?php echo htmlspecialchars($business['business_owner']); ?></p>
        </div>
    </div>
</div>
    <?php endif; ?>

    <?php if ($show_owners_button): ?>
      <div class="separator"></div>
    <?php endif; ?>

    <?php if ($show_owners_button): ?>
    <!-- Delete Section -->
    <div class="delete-section">
      <div class="delete-section-header">
        <h3>Delete Store</h3>
      </div>
      <p>If you delete the store, all data related to it will be permanently removed. This action cannot be undone.</p>
      <button class="delete-button" onclick="confirmDelete()">Delete Store</button>
      <?php endif; ?>
    </div>

        <!-- Time selection modal -->
        <div id="timeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('timeModal')">&times;</span>
            <h2>Select a Time</h2>
            <ul id="time-options">
                <!-- Time options will be populated by JavaScript -->
            </ul>
        </div>
    </div>

    <!-- Number of people selection modal -->
    <div id="peopleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('peopleModal')">&times;</span>
            <h2>Select Number of People</h2>
            <ul id="people-options">
                <!-- Number options will be populated by JavaScript -->
            </ul>
        </div>
    </div>

    <!-- Room selection modal -->
    <div id="roomModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('roomModal')">&times;</span>
            <h2>Select a Room</h2>
            <ul id="room-options">
              <?php if (!empty($rooms)): ?>
                  <?php foreach ($rooms as $room): ?>
                      <li class="room-option" data-room-id="<?php echo intval($room['id']); ?>" data-room-type="<?php echo htmlspecialchars($room['room_type']); ?>">
                          <span class="circle"></span>
                          <?php echo htmlspecialchars($room['room_type']); ?> (<?php echo intval($room['available_rooms']); ?> available)
                      </li>
                  <?php endforeach; ?>
              <?php else: ?>
                  <li>No rooms available for the selected criteria.</li>
              <?php endif; ?>
          </ul>
        </div>
    </div>
    </div>

    <div id="lightbox" onclick="closeLightbox()" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); justify-content: center; align-items: center; z-index: 1000;">
      <img id="lightbox-image" src="" alt="Lightbox" style="max-width: 80%; max-height: 80%;">
      <button onclick="showPrevImage(event)" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: white; font-size: 2rem; cursor: pointer;">&#10094;</button>
      <button onclick="showNextImage(event)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: white; font-size: 2rem; cursor: pointer;">&#10095;</button>
    </div>

    <div class="floating-home" onclick="location.href='user-login-index.php';">
      <i class="fas fa-home"></i>
    </div>
<script>
    const rooms = <?php echo json_encode($rooms); ?>;
</script>


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

        function redirectToReservationForm(category, businessId) {
        let reservationFormUrl = '';

        switch (category) {
            case 1:
                reservationFormUrl = 'boat-reservation-form.php?business_id=' + businessId;
                break;
            case 2:
                reservationFormUrl = 'ecotourism-reservation.php?business_id=' + businessId;
                break;
            case 3:
                reservationFormUrl = 'hotel-reservation.php?business_id=' + businessId;
                break;
            case 4:
                reservationFormUrl = 'business-owner-table-reservation-form.php?business_id=' + businessId;
                break;
            default:
                alert('No reservation form available for this category.');
                return; // Exit the function if no valid category is found
        }

        // Redirect to the selected reservation form
        location.href = reservationFormUrl;
        }

    // Pass PHP variables to JavaScript
    const openingTime = "<?php echo $opening_time; ?>";
    const closingTime = "<?php echo $closing_time; ?>";

        document.addEventListener('DOMContentLoaded', function() {
    // Determine the category and apply the appropriate date picker and event listeners
    const category = "<?php echo $category; ?>"; // Get the category from PHP

    let dateInputId, numPeopleInputId, timeInputId, roomInputId;

    if (category == 1) {
        dateInputId = 'date-input-boat';
        numPeopleInputId = 'num-people-input-boat';
        timeInputId = null;
    } else if (category == 2) {
        dateInputId = 'date-input-eco';
        numPeopleInputId = 'num-people-input-eco';
        timeInputId = null;
    } else if (category == 3) {
        dateInputId = null;
        numPeopleInputId = null;
        timeInputId = null; 
        roomInputId ='room-input';
    } else if (category == 4) {
        dateInputId = 'date-input';
        numPeopleInputId = 'num-people-input';
        timeInputId = 'time-input'; 
    }

    // Initialize Flatpickr for the date input
    if (dateInputId) {
        flatpickr(`#${dateInputId}`, {
            enableTime: false,
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr, instance) {
                console.log(dateStr); // Log the selected date
            }
        });
    }

    // Event listener for number of people modal
    if (numPeopleInputId) {
        document.getElementById(numPeopleInputId).addEventListener('click', function() {
            openModal('peopleModal');
            populatePeopleOptions(numPeopleInputId);
        });
    }

    // Initialize time modal for categories 3 and 4
    if (timeInputId) {
        document.getElementById(timeInputId).addEventListener('click', function() {
            openModal('timeModal');
            populateTimeOptions(timeInputId);
        });
    }

    if (roomInputId) {
        document.getElementById(roomInputId).addEventListener('click', function() {
            openModal('roomModal');
            populatePeopleOptions(roomInputId);
        });
    }

    

// Modal open function with selection logic
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'block';
    
    // Check which input is associated with the modal and mark the selected option
    if (modalId === 'timeModal') {
        const inputValue = document.getElementById(timeInputId).value;
        highlightSelectedOptionByValue('time-options', inputValue);
    } else if (modalId === 'peopleModal') {
        const inputValue = document.getElementById(numPeopleInputId).value;
        highlightSelectedOptionByValue('people-options', inputValue);
    }

    // Add event listener to close modal when clicking outside of modal content
    window.addEventListener('click', outsideClickListener);

    // Make sure to close modal when 'X' button is clicked
    const closeButton = modal.querySelector('.close');
    closeButton.addEventListener('click', function() {
        closeModal(modalId);
    });
}

// Close modal function
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';

    // Remove the event listener for outside clicks when modal closes
    window.removeEventListener('click', outsideClickListener);
}

// Function to handle closing the modal if clicked outside content
function outsideClickListener(event) {
    const modals = document.getElementsByClassName('modal');
    for (let modal of modals) {
        if (event.target === modal) {
            closeModal(modal.id);
        }
    }
}


// Highlight the selected option by value (existing data)
function highlightSelectedOptionByValue(listId, value) {
    const options = document.getElementById(listId).getElementsByTagName('li');
    for (let option of options) {
        option.classList.remove('selected'); // Remove selected class from all options
        option.querySelector('.circle').style.backgroundColor = 'transparent'; // Reset circle background
        if (option.innerText.trim() === value) { // Check if the option matches the value
            option.classList.add('selected'); // Add selected class to the matched option
            option.querySelector('.circle').style.backgroundColor = '#fff'; // Fill color for selected circle
        }
    }
}

// Function to handle selection and fill the circle when clicked
function highlightSelectedOption(list, selectedLi) {
    const options = list.getElementsByTagName('li');
    for (let option of options) {
        option.classList.remove('selected'); // Remove selected class from all options
        option.querySelector('.circle').style.backgroundColor = 'transparent'; // Reset circle background
    }
    selectedLi.classList.add('selected'); // Add selected class to the clicked option
    selectedLi.querySelector('.circle').style.backgroundColor = '#fff'; // Fill color for selected circle
}


function populateTimeOptions(inputId) {
    const timeOptionsList = document.getElementById('time-options');
    timeOptionsList.innerHTML = ''; // Clear existing options
    const timeOptions = generateTimeOptions(openingTime, closingTime);
    
    // Populate the time options list
    timeOptions.forEach(time => {
        const li = document.createElement('li');
        li.innerHTML = `<span class="circle"></span>${time}`; // Include circle span
        li.addEventListener('click', function() {
            document.getElementById(inputId).value = time; // Update the input field with the selected time
            highlightSelectedOption(timeOptionsList, li); // Highlight the selected option
            closeModal('timeModal'); // Close the modal
        });
        timeOptionsList.appendChild(li);
    });

    // Check if the input field exists and has a value
    const inputElement = document.getElementById(inputId);
    if (inputElement && inputElement.value) {
        const currentTimeValue = inputElement.value; // Get the current value of the input
        highlightSelectedOptionByValue('time-options', currentTimeValue); // Highlight the current value in the modal
    }
}


function populatePeopleOptions(inputId) {
    const peopleOptionsList = document.getElementById('people-options');
    peopleOptionsList.innerHTML = ''; // Clear existing options
    for (let i = 1; i <= 20; i++) {
        const li = document.createElement('li');
        li.innerHTML = `<span class="circle"></span>${i}`; // Include circle span
        li.addEventListener('click', function() {
            document.getElementById(inputId).value = i; // Update the input field with the selected number of people
            highlightSelectedOption(peopleOptionsList, li); // Highlight the selected option
            closeModal('peopleModal'); // Close the modal
        });
        peopleOptionsList.appendChild(li);
    }

    // Check if the input field exists and has a value
    const inputElement = document.getElementById(inputId);
    if (inputElement && inputElement.value) {
        const currentPeopleValue = inputElement.value; // Get the current value of the input
        highlightSelectedOptionByValue('people-options', currentPeopleValue); // Highlight the current value in the modal
    }
}


function generateTimeOptions(openingTime, closingTime) {
    const times = [];
    let currentTime = new Date(0, 0, 0, ...openingTime.split(':').map(Number));
    const closingDateTime = new Date(0, 0, 0, ...closingTime.split(':').map(Number));
    
    while (currentTime <= closingDateTime) {
        times.push(convertToAmPm(currentTime.getHours() + ':' + currentTime.getMinutes()));
        currentTime.setMinutes(currentTime.getMinutes() + 60); // Increment by 60 minutes
    }
    return times;
}


// Increment time by 30 minutes
function incrementTime(time, minutesToAdd) {
    const [hours, minutes] = time.split(':').map(Number);
    const newTime = new Date(0, 0, 0, hours, minutes + minutesToAdd);
    const newHours = newTime.getHours().toString().padStart(2, '0');
    const newMinutes = newTime.getMinutes().toString().padStart(2, '0');
    return `${newHours}:${newMinutes}`;
}

// Convert time to 12-hour format with AM/PM
function convertToAmPm(time) {
    let [hours, minutes] = time.split(':').map(Number);
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // 12-hour format
    return `${hours}:${minutes.toString().padStart(2, '0')} ${ampm}`;
}

});

document.addEventListener('DOMContentLoaded', function () {
    const roomOptions = document.querySelectorAll('#room-options .room-option');
    const roomInput = document.getElementById('room-input');
    const roomTypeHiddenInput = document.getElementById('room-type');
    const roomIdHiddenInput = document.getElementById('room-id'); // Hidden input for room_id

    roomOptions.forEach(option => {
        option.addEventListener('click', function () {
            // Remove the 'selected' class from all options
            roomOptions.forEach(opt => opt.classList.remove('selected'));

            // Add the 'selected' class to the clicked option
            this.classList.add('selected');

            // Get the selected room type and ID
            const selectedRoomType = this.getAttribute('data-room-type');
            const selectedRoomId = this.getAttribute('data-room-id'); // Get room_id

            // Update inputs with selected values
            roomInput.value = selectedRoomType; // Display the room type in the input field
            roomTypeHiddenInput.value = selectedRoomType; // Set the value for hidden input
            roomIdHiddenInput.value = selectedRoomId; // Set the room_id for hidden input
            closeModal('roomModal'); // Close the modal after selection
        });
    });
});

// Close modal function
function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
}

</script>
</body>
</html>
