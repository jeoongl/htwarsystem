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

// Fetch contact information from hinunangan_info_tbl
$query_contact = "SELECT contact_no, email, facebook, instagram, x_twitter, linkedin 
                  FROM hinunangan_info_tbl 
                  WHERE user_id = ?";
$stmt_contact = $conn->prepare($query_contact);
$stmt_contact->bind_param("i", $user_id);
$stmt_contact->execute();
$result_contact = $stmt_contact->get_result();
$contact_info = $result_contact->fetch_assoc();

// Default values if no data exists
$contact_no = $contact_info['contact_no'] ?? 'N/A';
$email = $contact_info['email'] ?? 'N/A';
$facebook = $contact_info['facebook'] ?? 'N/A';
$instagram = $contact_info['instagram'] ?? 'N/A';
$x_twitter = $contact_info['x_twitter'] ?? 'N/A';
$linkedin = $contact_info['linkedin'] ?? 'N/A';

// Close statement
$stmt_contact->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Business Settings</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      margin: 0;
      padding: 0;
      background-color: black;
      color: black;
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
    header {
      background-color: black;
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
            width: 180px;
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
    .container-wrapper {
      display: flex;
      justify-content: center;
      height: 500px;
      margin: 40px;
    }

    .container {
      background-color: #222;
      padding: 0 0 20px 0;
      border-radius: 5px;
      display: flex;
      flex-direction: column;
      width: 100%;
      max-width: 1200px;
    }

    .content-wrapper {
      display: flex;
      height: 100%;
      overflow: hidden; /* Added to control overflow */
    }

    .sidebar {
      flex-shrink: 0;
      width: 250px; /* Fixed width */
      background-color: #222;
      padding: 20px;
      overflow-y: auto;
    }

    .content {
      flex-grow: 1;
      padding: 20px;
      background-color: #222;
      overflow-y: auto;
      color: white; /* Ensures white text across these sections */
    }
    .container h1 {
    font-size: 28px;
    text-align: left;
    margin-left: 15px;
    margin-bottom: 15px; /* Reduce spacing */
    color: white;
}



    .sidebar ul {
      list-style-type: none;
      padding: 0;
      margin: 0;
    }

    .sidebar ul li {
      position: relative;
      margin-bottom: 10px;
    }

    .sidebar ul li a {
      color: white;
      text-decoration: none;
      padding: 10px;
      display: block;
      border-radius: 7.5px;
      background-color: #333;
      position: relative;
    }

    .sidebar ul li a:hover,
    .sidebar ul li a.active {
      background-color: #555;
    }

    .content h2 {
      margin-top: 0;
      color: white; /* Ensures white text across these sections */
    }

    .edit-container {
      position: relative;
    }


    .contact-info {
      margin: 20px;
    }

    .contact-info div {
      margin-bottom: 10px;
    }

    .contact-info i {
      margin-right: 10px;
    }
    .profile img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      border: 2px solid white; /* Optional, to add a border around the photo */
      margin-bottom: 10px;
    }

    .profile span {
      display: block;
      color: white;
      font-size: 16px;
      font-weight: bold;
    }
    .google-maps-link-container {
      max-width: 100%;
      word-wrap: break-word;
      word-break: break-all; /* Ensures long URLs wrap */
      white-space: normal;
      overflow: hidden;
      color: white; /* Ensures white text across these sections */
    }

      /* Modal styles */
      .modal {
      display: none;
      position: fixed;
      z-index: 1;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.7);
    }
    .modal-content {
      background-color: #222;
      margin: 2% auto;
      padding: 20px;
      border-radius: 5px;
      width: 80%;
      max-width: 500px;
      color: white;
    }
    .close {
      color: white;
      float: right;
      font-size: 28px;
      cursor: pointer;
    }
    .close:hover, .close:focus {
      color: whitesmoke;
    }
    .modal-content label {
      display: block;
      margin: 5px 0 5px;
      font-size: 15px;
    }
    .modal-content input[type="time"], .modal-content input[type="text"], 
    .modal-content input[type="email"], .modal-content input[type="number"],
    .modal-content textarea, .modal-content select {
      width: 100%;
      padding: 8px;
      margin-bottom: 15px;
      background-color: #333;
      color: white;
      border: none;
      border-radius: 3px;
    }

  .edit-button, .delete-button {
      margin-top: 10px;
      padding: 8px 12px;
      background-color: green;
      color: white;
      border: none;
      border-radius: 2.5px;
      cursor: pointer;
    }
    .delete-button:hover {
    background-color: darkred;
    }
    .edit-button:hover {
      background-color: darkgreen;
    }
    * {
    box-sizing: border-box;
  }
    .back-button {
      margin-right: 10px;
      margin-left: 0; /* Optional: adjust if you want spacing from the left */
      font-size: .9em;
      cursor: pointer;
      color: white;
      background: none;
      border: none;
    }
    .image-grid {
    width: 600px;
    height: 350px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.image-card {
    position: relative;
    overflow: hidden;
    border-radius: 5px;
    background-color: #333;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 5px;
}

.image-overlay {
    position: absolute;
    bottom: 10px;
    left: 10px;
    right: 10px;
    text-align: center;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.image-overlay button {
    background-color: green;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 3px;
    cursor: pointer;
}

.image-overlay button:hover {
    background-color: darkgreen;
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
        <a href="admin-dashboard.php"><i class="fa fa-bar-chart"></i>Dashboard</a>
        <a href="businesses.php"><i class="fas fa-users"></i>Businesses</a>
        <a href="admin-settings.php"><i class="fas fa-cogs"></i>Settings</a>
        <a href="index.php"><i class="fas fa-sign-out-alt"></i>Log out</a>
      </div>
    </div>
</header>


<div class="container-wrapper">
    <div class="container">
        <h1>
            <button class="back-button" onclick="goBack()">
                <i class="fa fa-angle-left"></i>
            </button>
            Settings
        </h1>
        <div class="content-wrapper">
            <div class="sidebar">
            <ul>
                <li><a href="javascript:void(0);" onclick="showContent('profile')">Profile</a></li>
                <li><a href="javascript:void(0);" onclick="showContent('contact-info')">Contact Information</a></li>
                <li><a href="javascript:void(0);" onclick="showContent('personalize-website')">Website</a></li>
                <li><a href="javascript:void(0);" onclick="showContent('password')">Change Password</a></li>
            </ul>
            </div>

            <div class="content" id="content">

            </div>
        </div>
    </div>
</div>

<div id="contactInfoModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('contactInfoModal')">&times;</span>
    <h2>Edit Contact Info</h2>
    <form id="contactInfoForm" method="POST" action="update-admin-contact-info.php">
      <label for="contact-no">Contact Number</label>
      <input type="text" id="contact-no" name="contact_no" value="<?php echo htmlspecialchars($contact_no); ?>">

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">

      <label for="facebook">Facebook</label>
      <input type="text" id="facebook" name="facebook" value="<?php echo htmlspecialchars($facebook); ?>">

      <label for="instagram">Instagram</label>
      <input type="text" id="instagram" name="instagram" value="<?php echo htmlspecialchars($instagram); ?>">

      <label for="x-twitter">X</label>
      <input type="text" id="x-twitter" name="x_twitter" value="<?php echo htmlspecialchars($x_twitter); ?>">

      <label for="linkedin">LinkedIn</label>
      <input type="text" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($linkedin); ?>">

      <button type="submit" class="edit-button">Save Changes</button>
    </form>
  </div>
</div>

  <script>
  // Function to toggle the dropdown menu
  function toggleDropdown() {
    const dropdown = document.getElementById('dropdown-menu');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
  }

  // Close dropdown when clicking outside
  window.onclick = function(event) {
    if (!event.target.matches('.profile-icon, .profile-icon *')) {
      const dropdown = document.getElementById('dropdown-menu');
      if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
      }
    }
  };

  // Function to display content based on the section clicked
  function showContent(section) {
    const content = document.getElementById('content');
    let html = '';

    switch (section) {
      case 'profile':
            html = `
                <div class="edit-container">
                    <h2>Profile</h2>
                    <button class="edit-button" onclick="showModal('profileModal')">Edit Profile</button>
                </div>`;
            break;
        case 'contact-info':
            html = `
              <div id="contact-info" class="contact-info">
                <h2>Contact Information</h2>
                <div><i class="fa fa-phone"></i> Contact Number: <span><?php echo htmlspecialchars($contact_no); ?></span></div>
                <div><i class="fa fa-envelope"></i> Email: <span><?php echo htmlspecialchars($email); ?></span></div>
                <div><i class="fab fa-facebook"></i> Facebook: <span><?php echo htmlspecialchars($facebook); ?></span></div>
                <div><i class="fab fa-instagram"></i> Instagram: <span><?php echo htmlspecialchars($instagram); ?></span></div>
                <div><i class="fab fa-x-twitter"></i> X: <span><?php echo htmlspecialchars($x_twitter); ?></span></div>
                <div><i class="fab fa-linkedin"></i> LinkedIn: <span><?php echo htmlspecialchars($linkedin); ?></span></div>
                <button class="edit-button" onclick="showModal('contactInfoModal')">Edit Contact Info</button>
                </div>`;
            break;
        case 'personalize-website':
            html = `
                <div class="edit-container">
                <h2>Personalize Website</h2>
                <div class="image-grid">
                    <?php
                    $query_categories = "SELECT thumbnail, category_name FROM categories_tbl";
                    $result_categories = $conn->query($query_categories);

                    if ($result_categories->num_rows > 0) {
                        while ($category = $result_categories->fetch_assoc()) {
                            echo '
                            <div class="image-card">
                                <img src="' . htmlspecialchars($category['thumbnail']) . '" alt="Thumbnail">
                                <div class="image-overlay">
                                    <span>' . htmlspecialchars($category['category_name']) . '</span>
                                    <button class="edit-button" onclick="editCategory(\'' . htmlspecialchars($category['category_name']) . '\')">Edit</button>
                                </div>
                            </div>';
                        }
                    } else {
                        echo '<p>No categories available.</p>';
                    }
                    ?>
                  </div>
              </div>
          </div>`;
            break;
        case 'password':
            html = `
                <div class="edit-container">
                    <h2>Password</h2>
                    <button class="edit-button" onclick="showModal('passwordModal')">Change Password</button>
                </div>`;
            break;
    }

    // Update the content and save the selected section to localStorage
    content.innerHTML = html;
    localStorage.setItem('lastSelectedSection', section);
  }

  // On page load, display the last selected section from localStorage or default to 'services'
  document.addEventListener('DOMContentLoaded', function() {
    const lastSelectedSection = localStorage.getItem('lastSelectedSection') || 'services';
    showContent(lastSelectedSection);

    // Set active class on the last selected sidebar item
    document.querySelectorAll('.sidebar ul li a').forEach(link => {
      link.classList.remove('active');
    });
    document.querySelector(`.sidebar ul li a[onclick="showContent('${lastSelectedSection}')"]`).classList.add('active');
  });

  // Update the active class on the sidebar links
  document.querySelectorAll('.sidebar ul li a').forEach(link => {
    link.addEventListener('click', function() {
      document.querySelectorAll('.sidebar ul li a').forEach(link => link.classList.remove('active'));
      this.classList.add('active');
    });
  });

  function showModal(modalId) {
      document.getElementById(modalId).style.display = "block";
    }
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = "none";
    }
    window.onclick = function(event) {
      if (event.target.className === 'modal') {
        event.target.style.display = "none";
      }
    };

    

</script>
</body>
</html>
