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

// Fetch business details including name, address, opening and closing time
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve the posted reservation details
    $business_id = $_POST['business_id'];
    $reservation_date = $_POST['reservation_date'];
    $num_people = $_POST['num_people'];

    // Ensure all fields are present
    if (empty($reservation_date) || empty($num_people)) {
        echo "All fields are required.";
        exit();
    }

    // Fetch business details based on the business_id from the POST request
    $business_query = "SELECT name, address, opening_time, closing_time FROM businesses_tbl WHERE id = ?";
    $business_stmt = $conn->prepare($business_query);
    $business_stmt->bind_param("i", $business_id);
    $business_stmt->execute();
    $business_result = $business_stmt->get_result();
    $business = $business_result->fetch_assoc();

    // Business details
    $business_name = $business['name']; // Business name
    $business_address = $business['address']; // Business address
    $opening_time = $business['opening_time']; // Opening time
    $closing_time = $business['closing_time']; // Closing time

    $business_stmt->close();

} else {
    echo "Invalid request method!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Boat Reservation Form</title>
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
      font-size: 20px;
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
    .main-container {
      margin: 40px auto;
      width: 1000px;
      background-color: #444;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.5);
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }
    .title-container {
      width: 100%;
      text-align: left;
      margin-bottom: 10px;
    }
    .title-container h1 {
      font-size: 26px;
      margin-top: 0;
      margin-bottom: 5px;
      color: white;
    }
    .title-container p {
      font-size: 18px;
      margin-top: 0;
      margin-bottom: 5px;
      color: white;
    }
    .form-section {
      width: 94%;
      max-height: 400px;
      overflow-y: auto;
      background-color: #444;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .form-container {
      margin-bottom: 20px;
      background-color: #333;
      padding: 20px;
      border-radius: 10px;
      width: 95%;
      max-width: 1000px;
    }
    .form-container h2 {
      font-size: 24px;
      text-align: center;
      margin-bottom: 20px;
      color: white;
    }
    .form-container label {
      display: block;
      margin-bottom: 8px;
      font-size: 14px;
      color: white;
    }
    .form-container input, .form-container select {
      width: calc(100%);
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #555;
      border-radius: 5px;
      background-color: #222;
      color: white;
      box-sizing: border-box;
    }

    .form-container select {
      padding-right: 30px;
    }

    .add-btn {
      display: flex;
      justify-content: flex-end; /* Align items to the end of the container */
      align-items: center;
      margin: 10px 0;
      width: 100%;
    }

    .button-group {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .guest-count {
      display: inline-block;
      background-color: #333; 
      border-radius: 5px; 
      padding: 0 15px;
      width: 60px;
      height: 40px; 
      line-height: 40px; 
      text-align: center; 
      font-size: 20px; 
      color: white;
    }

    .add-btn span {
      margin-right: 10px; /* Space between the text and the number indicator */
      font-size: 16px; /* Adjust font size if needed */
    }

    .add-btn button {
      background-color: green;
      border: none;
      color: white;
      width: 40px;
      height: 40px;
      font-size: 20px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 5px; /* Add border-radius to soften edges */
    }

    .add-btn button#remove-form {
      background-color: red;
      border-radius: 5px; /* Add border-radius to soften edges */
    }

    .submit-btn {
      display: block;
      width: 100%;
      padding: 10px;
      background-color: green;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      color: white;
      font-size: 16px;
      margin-top: 20px;
    }
    .info-text {
      font-size: 20px;
      text-align: center;
      margin: 15px;
      color: white;
    }

    .flex-container {
      display: flex;
      justify-content: space-between;
      gap: 20px;
    }

    .flex-item {
      flex: 1;
      min-width: 48%;
    }
        /* Store Info */
        .store-info {
      width: 100%;
      text-align: left;
    }

    .store-info h1 {
      font-size: 26px;
      color: white;
      margin: 0;
    }

    .store-info p {
      font-size: 18px;
      color: white;
      margin-top: 5px;
    }

    .store-address {
      display: flex;
      align-items: center;
      color: white;
    }

    .store-address i {
      margin-right: 8px;
      margin-top: -15px;
    }
    .reservation-details {
    display: flex;
    justify-content: flex-start; /* Aligns the details to the right */
    align-items: center;
    background-color: #333;
    padding: 15px 15px 15px 0;
    margin-bottom: 20px;
    border-radius: 8px;
    color: white;
    gap: 20px;
    width: 100%; /* Ensures it takes up the full width */
}

.reservation-details .detail {
    display: flex;
    align-items: center;
    gap: 10px;
}

.reservation-details p {
    margin: 0;
    font-size: 18px;
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
        <a href="business-owner-login-index.php"><i class="fas fa-home"></i> Home</a>
        <a href="business-owner-profile-page.php"><i class="fas fa-user"></i> My Account</a>
        <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
</header>


  <div class="main-container">
  <div class="store-info">
        <h1>Reserve boat to <?php echo $business_name; ?></h1>
        <div class="store-address">
          <i class="fas fa-map-marker-alt" style="color: white;"></i>
          <p><?php echo $business_address; ?></p>
        </div>
    </div>


    <div class="reservation-details">
    <div class="detail">
        <i class="fas fa-calendar" style="color: white;"></i>
        <p><?php echo htmlspecialchars($reservation_date); ?></p>
    </div>
    <div class="detail">
        <i class="fas fa-users" style="color: white;"></i>
        <p><?php echo htmlspecialchars($num_people); ?></p>
    </div>
</div>
    <div class="add-btn">
      <div class="button-group">
        <div class="guest-count" id="guest-count">1</div>
        <button id="remove-form" onclick="removeGuestForm()">-</button>
        <button id="add-form" onclick="addGuestForm()">+</button>
      </div>
    </div>

    <div class="form-section" id="form-section">
      <div class="form-container">
        <h2>Passenger #1 Information</h2>
        <form class="guest-form">
          <label for="name">Passenger Name:</label>
          <input type="text" id="name" name="name" required>

          <div class="flex-container">
            <div class="flex-item">
              <label for="gender">Gender:</label>
              <select id="gender" name="gender" required>
                <option value="" disabled selected>Select your gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="flex-item">
              <label for="age">Age:</label>
              <input type="number" id="age" name="age" min="0" required>
            </div>
          </div>

          <div class="flex-container">
            <div class="flex-item">
              <label for="type">Type:</label>
              <select id="type" name="type" required>
                <option value="" disabled selected>Select type</option>
                <option value="international">International</option>
                <option value="local">Local</option>
              </select>
            </div>
            <div class="flex-item">
              <label for="citizenship">Citizenship:</label>
              <input type="text" id="citizenship" name="citizenship" required>
            </div>
          </div>

          <label for="address">Address:</label>
          <input type="text" id="address" name="address" required>

          <label for="mobile">Mobile Number:</label>
          <input type="tel" id="mobile" name="mobile" required>

          <label for="email">Email:</label>
          <input type="email" id="email" name="email" required>
        </form>
      </div>
    </div>

    <button class="submit-btn">Submit</button>
  </div>

  <script>

  function toggleDropdown() {
    const dropdown = document.getElementById("dropdown-menu");
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
  }

  window.onclick = function(event) {
    const dropdown = document.getElementById("dropdown-menu");
    if (!event.target.closest('.profile-icon') && !event.target.closest('.dropdown-menu')) {
      dropdown.style.display = "none";
    }
  }

  document.getElementById("dropdown-menu").addEventListener("click", function(event) {
    event.stopPropagation();
  });

  function goBack() {
    window.history.back(); // Navigate back to the previous page
  }

    function updateGuestCount() {
      const formContainers = document.getElementsByClassName('form-container');
      document.getElementById('guest-count').textContent = formContainers.length;
    }

    function addGuestForm() {
      const formSection = document.getElementById('form-section');
      const formContainers = document.getElementsByClassName('form-container');
      const newFormNumber = formContainers.length + 1;

      const newForm = document.createElement('div');
      newForm.classList.add('form-container');
      newForm.innerHTML = `
        <h2>Passenger #${newFormNumber} Information</h2>
        <form class="guest-form">
          <label for="name">Passenger Name:</label>
          <input type="text" name="name" required>

          <div class="flex-container">
            <div class="flex-item">
              <label for="gender">Gender:</label>
              <select name="gender" required>
                <option value="" disabled selected>Select your gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="flex-item">
              <label for="age">Age:</label>
              <input type="number" name="age" min="0" required>
            </div>
          </div>

          <div class="flex-container">
            <div class="flex-item">
              <label for="type">Type:</label>
              <select name="type" required>
                <option value="" disabled selected>Select type</option>
                <option value="international">International</option>
                <option value="local">Local</option>
              </select>
            </div>
            <div class="flex-item">
              <label for="citizenship">Citizenship:</label>
              <input type="text" name="citizenship" required>
            </div>
          </div>

          <label for="address">Address:</label>
          <input type="text" name="address" required>

          <label for="mobile">Mobile Number:</label>
          <input type="tel" name="mobile" required>

          <label for="email">Email:</label>
          <input type="email" name="email" required>
        </form>
      `;

      formSection.appendChild(newForm);
      updateGuestCount();
    }

    function removeGuestForm() {
      const formSection = document.getElementById('form-section');
      const formContainers = document.getElementsByClassName('form-container');

      if (formContainers.length > 1) {
        formSection.removeChild(formSection.lastElementChild);
        updateGuestCount();
      }
    }
  </script>
</body>
</html>
