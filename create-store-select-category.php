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

// Fetch categories from the categories_tbl
$query = "SELECT id, category_name FROM categories_tbl";
$result = mysqli_query($conn, $query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Store the selected category in the session
    $_SESSION['business_category'] = $_POST['category'];
    // Redirect to the business permit submission page
    header("Location: business-permit-submit.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Select Category</title>
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

    .type-container {
      background-color: black;
      padding: 20px;
      border-radius: 8px;
      width: 300px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
      text-align: center;
      margin: 50px auto;
    }
    .type-container h2 {
      margin-top: 0;
      font-size: 32px;
      text-align: left;
      color: white;
    }
    .type-container .input-container {
      width: 100%;
      margin: 20px 0;
    }

    .type-options {
      text-align: left;
      margin: 20px 0;
    }
    .type-options input[type="radio"] {
      display: none;
    }
    .type-options label {
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
    .type-options input[type="radio"] + label::before {
      content: '';
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 2px solid white;
      border-radius: 50%;
      margin-right: 10px;
    }
    .type-options input[type="radio"]:checked + label {
      background-color: green;
    }
    .type-options input[type="radio"]:checked + label::before {
      background-color: white;
      border-color: white;
    }
    .type-container button {
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
  
  <!-- Category Selection Form -->
  <div class="type-container">
    <h2>Select Category</h2>
    <form action="" method="post">
      <div class="type-options">
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            // Loop through each category and generate radio buttons
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<input type="radio" id="category_' . $row['id'] . '" name="category" value="' . $row['id'] . '" required>';
                echo '<label for="category_' . $row['id'] . '">' . $row['category_name'] . '</label>';
            }
        } else {
            echo 'No categories available.';
        }
        ?>
      </div>
      <button type="submit">Continue</button>
    </form>
  </div>

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
  </script>
</body>
</html>
