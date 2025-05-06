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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate form data
    if (isset($_POST['business-name']) && isset($_POST['business-address'])) {
        // Store business name and address in the session
        $_SESSION['business_name'] = $_POST['business-name'];
        $_SESSION['business_address'] = $_POST['business-address'];
        $_SESSION['opening_time'] = $_POST['opening-time'];  // Store opening time in session
        $_SESSION['closing_time'] = $_POST['closing-time'];  // S

        // Redirect to the next page for additional information
        header("Location: create-store-select-category.php");
        exit();
    } else {
        // Debugging output
        echo "Form data is missing.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Store</title>
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

    .create-store-container {
      background-color: black;
      padding: 20px;
      border-radius: 8px;
      width: 300px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
      text-align: center;
      margin: 50px auto;
    }
    .create-store-container h2 {
      margin-top: 0;
      font-size: 32px;
      text-align: left;
      color: white;
    }
    .create-store-container .input-container {
      position: relative;
      width: 100%;
      margin: 25px 0;

    }
    .create-store-container input {
      width: 88%;
      padding: 15px;
      border: 1px solid #555;
      border-radius: 4px;
      background-color: #222;
      color: white;

    }

    .create-store-container button {
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
    .time-input-container {
      display: flex;
      justify-content: flex-start; /* Align items closely */
      gap: 30px; /* Reduce space between the two inputs */
      margin: 25px 0;
  }

  .time-input {
      display: flex;
      flex-direction: column;
      padding-right: 0;
      width: 42%;
     
  }

  .time-input label {
      margin-bottom: 5px;
      color: white;
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

<div class="create-store-container">
    <h2>Create Store</h2>
    <form action="" method="post"> <!-- Submit to the same page -->
        <div class="input-container">
            <input name="business-name" placeholder="Business Name" required>
        </div>
        <div class="input-container">
            <input name="business-address" placeholder="Business Address" required>
        </div>

        <!-- Add Opening Time and Closing Time input fields in one line -->
        <div class="time-input-container">
            <div class="time-input">
                <label for="opening-time">Opening Time:</label>
                <input type="time" id="opening-time" name="opening-time" required>
            </div>

            <div class="time-input">
                <label for="closing-time">Closing Time:</label>
                <input type="time" id="closing-time" name="closing-time" required>
            </div>
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
    function togglePassword(fieldId, icon) {
      const field = document.getElementById(fieldId);
      if (field.type === "password") {
        field.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        field.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    }
  </script>
</body>
</html>
