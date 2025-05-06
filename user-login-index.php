<?php
// Start session
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: log-in.php");
    exit();
}

// Include database connection
include('includes/dbconnection.php');

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Query the database for the user data
$query = "SELECT fullname, profile_photo FROM users_tbl WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if the user exists
if (!$user) {
    // User not found, destroy session and redirect to login
    session_destroy();
    header("Location: log-in.php");
    exit();
}

// Set default profile photo if none exists
$profile_photo = !empty($user['profile_photo']) ? $user['profile_photo'] : 'img/default-profile.jpg';

// Fetch contact information and social media links
$contact_query = "SELECT contact_no, email, facebook, instagram, x_twitter, linkedin FROM hinunangan_info_tbl LIMIT 1";
$contact_result = $conn->query($contact_query);
$contact_info = $contact_result->fetch_assoc();

// Fetch categories including category_id
$categories_query = "SELECT id, category_name, thumbnail FROM categories_tbl";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories_result = $categories_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hinunangan Tourism Website and Reservation System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="css/footer.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
      #title-container {
        display: flex;
        flex-direction: column;
        padding-left: 10%;
        padding-top: 50px;
        align-items: flex-start; /* Aligns content to the left */
        max-width: 600px; /* Ensures consistency with the image's max width */
      }

      #title-container img {
        width: 100%; /* Keeps the original width setting */
      }

      #sub-title {
        font-size: 20px;
        padding-left: 3%;
        margin-top: 5px; /* Adds space between the image and subtext */
      }
    .container {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      padding: 5% 5%;
    }
    .slideshow-container {
      position: relative;
      width: calc(50% - 20px);
      height: 340px;
      margin: 10px 10px;
      overflow: hidden;
      border-radius: 10px;
      box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.3);
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .slideshow-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      margin: 0;
      padding: 0;
      transition: transform 0.3s ease;
    }
    .slideshow-container:hover img {
      transform: scale(1.1);
    }


    @media only screen and (max-width: 767px) {
      .slideshow-container:active img {
        transform: scale(1.1);
      }
    }
    .label-container {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 10px;
      box-sizing: border-box;
      background-image: linear-gradient(to top, rgba(0, 0, 0, 0.5) 0%, rgba(0, 0, 0, 0) 100%);
      color: white;
    }
    .label {
      font-size: 40px;
      font-family: Helvetica;
      font-weight: bold;
      position: relative;
      text-align: left;
      cursor: pointer;
      margin: 3px 15px;
    }

    @media only screen and (max-width: 767px) {
      .container {
        flex-direction: column;
        align-items: center;
        padding: 4% 0;
      }
      .slideshow-container {
        width: 92%;
        max-width: none;
        margin: 10px 0;
        height: 200px;
        cursor: pointer;
      }
      .slideshow-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        margin: 0;
        padding: 0;
        transition: transform 0.5s ease;
      }
      .label {
        width: 80%;
        font-size: 25px;
        margin: 0 5px;
      }
      .logo {
        width: 250px;
      }
      .login-btn, .contact-btn {
        background-color: transparent;
        border: none;
        font-size: 15px;
        cursor: pointer;
        color: white;
      }
      #title-container {
        padding-top: 5%;
        padding-left: 2%;
        width: 85%;
      }

      #title-container img {
        width: 90%; /* Keeps the original width setting */
      }

      #sub-title {
        padding-left: 3%;
      }
    }
    .slideshow-container a {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
      text-decoration: none;
    }
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
    }
    .modal-content {
      background-color: #333;
      padding: 20px;
      border-radius: 8px;
      width: 300px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
      text-align: center;
    }
    .modal-content h2 {
      margin-top: 0;
      color: white;
    }
    .modal-content .input-container {
      position: relative;
      width: 100%;
      margin: 10px 0;
    }
    .modal-content input {
      width: 92%;
      padding: 10px;
      border: 1px solid #555;
      border-radius: 4px;
      background-color: #222;
      color: white;
    }
    .modal-content button {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      background-color: green;
      color: white;
      font-size: 16px;
    }
    .signup-text {
      color: white;
      margin-top: 10px;
      text-align: center;
    }
    .signup-link {
      color: green;
      cursor: pointer;
      text-decoration: underline;
    }
    .password-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: white;
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
  </style>
</head>
<body>
<header>
    <img class="logo" src="img/logo.png" alt="Logo">
    
    <!-- Profile Section with Photo and User Name -->
    <div class="profile-icon" onclick="toggleDropdown()">
      <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Photo" style="width: 40px; height: 40px; border-radius: 50%;">
      <div class="dropdown-menu" id="dropdown-menu">
        <div>
          <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Photo" style="width: 60px; height: 60px; border-radius: 50%;">
          <p><?php echo htmlspecialchars($user['fullname']); ?></p> <!-- Display user name from the database -->
        </div>
        <a href="user-login-index.php"><i class="fas fa-home"></i> Home</a>
        <a href="user-profile-page.php"><i class="fas fa-user"></i> My Account</a>
        <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a> <!-- Link to logout -->
      </div>
    </div>
</header>
<div id="title-container">
    <img src="img/love-hn.png" alt="Welcome to Hinunangan" style="width: 100%; max-width: 600px; display: block; margin: 0 auto;">
    <span id="sub-title">AJAW NAG LANGAN, ARI NAS NANGAN.</span>
</div> 
<div class="container">
    <?php
    // Loop through the categories and create slideshow containers
    while ($category = $categories_result->fetch_assoc()) {
        ?>
        <div class="slideshow-container">
            <!-- Pass the category_id in the URL as a query parameter -->
            <a href="user-places-of-interest.php?id=<?php echo htmlspecialchars($category['id']); ?>"></a>
            <img src="<?php echo htmlspecialchars($category['thumbnail']); ?>" alt="<?php echo htmlspecialchars($category['category_name']); ?>">
            <div class="label-container">
                <div class="label"><?php echo htmlspecialchars($category['category_name']); ?></div>
            </div>
        </div>
        <?php
    }
    ?>
</div>

<div class="floating-home" onclick="location.href='user-login-index.php';">
  <i class="fas fa-home"></i>
</div>

<style>
    footer {
        background-color: #222;
        color: white;
        text-align: center;
        padding: 0;
    }

    .footer-container {
        display: flex;
        justify-content: space-around;
        flex-wrap: wrap;
        padding: 20px 20px;
    }

    .footer-column {
        flex: 1;
        min-width: 200px;
        margin-bottom: 20px;
    }

    .footer-column img {
        width: 100px;
        margin-bottom: 0;
    }

    .footer-column h3 {
        margin-bottom: 10px;
    }

    .social-icons {
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    .social-icons a {
        color: white;
        text-decoration: none;
    }

    .social-icons i {
        font-size: 30px;
    }
    
</style>

<footer>
    <img src="img/tourism-design.png" id="decorative-line" class="decorative-line">
    <div class="footer-container">
        <div class="footer-column">
            <img class="logo1" src="img/hinunangan.png" alt="Logo">
            <img class="logo2" src="img/love-hn.png" alt="Love HN">
        </div>

        <div class="footer-column">
            <h3>Contact Us</h3>
            <p>Phone: <a href="tel:<?php echo $contact_info['contact_no']; ?>"><?php echo $contact_info['contact_no']; ?></a></p>
            <p>Email: <a href="mailto:<?php echo $contact_info['email']; ?>"><?php echo $contact_info['email']; ?></a></p>
        </div>
        <div class="footer-column">
            <h3>Follow Us</h3>
            <div class="social-icons">
                <?php if (!empty($contact_info['facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($contact_info['facebook']); ?>" target="_blank"><i class="fab fa-facebook"></i></a>
                <?php endif; ?>
                <?php if (!empty($contact_info['instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($contact_info['instagram']); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                <?php endif; ?>
                <?php if (!empty($contact_info['x_twitter'])): ?>
                    <a href="<?php echo htmlspecialchars($contact_info['x_twitter']); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                <?php endif; ?>
                <?php if (!empty($contact_info['linkedin'])): ?>
                    <a href="<?php echo htmlspecialchars($contact_info['linkedin']); ?>" target="_blank"><i class="fab fa-linkedin"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</footer>

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
  </script>
</body>
</html>
