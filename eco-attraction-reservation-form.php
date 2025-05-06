<?php
// Start session and include database connection
session_start();
include 'includes/dbconnection.php';

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
    .login-btn {
      background-color: transparent;
      border: none;
      font-size: 16px;
      cursor: pointer;
      color: white;
      margin-left: auto;
    }
    .main-container {
      margin: 40px auto;
      width: 1000px;
      background-color: #444;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.5);
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
      gap: 20px;
    }
    .form-container {
      background-color: #333;
      padding: 20px;
      border-radius: 10px;
      width: 100%; /* Full width of the form-section */
      max-width: 100%; /* Ensure it does not cap at 1000px */
      box-sizing: border-box;
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

    .button-group {
      display: flex;
      justify-content: space-between; /* Space between buttons */
      gap: 10px; /* Space between the minus and add button */
      }

    .button-group button {
        border: none;
        color: white;
        width: 40px;
        height: 40px;
        font-size: 24px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        transition: background-color 0.3s, transform 0.3s;
    }

    .button-group button:hover {
        transform: scale(1.1);
    }

    .button-group button#remove-form {
        background-color: red;
    }

    .button-group button#add-form {
        background-color: green;
    }

    .button-group button#remove-form:hover {
        background-color: darkred;
    }

    .button-group button#add-form:hover {
        background-color: darkgreen;
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
      justify-content: flex-start; /* Adjust spacing between elements */
      align-items: center; /* Center elements vertically */
      background-color: #444;
      padding: 5px 5px 5px 0;
      margin-bottom: 20px;
      border-radius: 8px;
      color: white;
      gap: 20px; /* Space between details */
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
    .passenger-info-container {
      text-align: center;
      margin: 10px auto; /* Adjust top and bottom spacing */
      width: 100%;
      max-width: 800px;
    }

    .passenger-info-container p {
      font-size: 18px;
      color: white;
      margin: 5px 0; /* Minimize space above and below the text */
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
    <button class="login-btn" onclick="openLogin()">Login</button>
  </header>

<div class="main-container">
  <div class="store-info">
    <h1>Reserve to <?php echo htmlspecialchars($business_name); ?></h1>
    <div class="store-address">
      <i class="fas fa-map-marker-alt" style="color: white;"></i>
      <p><?php echo htmlspecialchars($business_address); ?></p>
    </div>
  </div>

  <div class="reservation-details">
    <div class="detail">
      <i class="fas fa-calendar" style="color: white;"></i>
      <p><?php echo htmlspecialchars($reservation_date); ?></p>
    </div>
    <div class="detail">
      <i class="fas fa-users" style="color: white;"></i>
      <p id="guest-count"><?php echo htmlspecialchars($num_people); ?></p>
    </div>
    <div class="button-group">
      <button type="button" id="remove-form" onclick="removeGuestForm()">-</button>
      <button type="button" id="add-form" onclick="addGuestForm()">+</button>
    </div>
  </div>

  <form action="reserve-eco-attraction.php" method="POST">
  <input type="hidden" name="num_people_input" id="num-people-input" value="<?php echo htmlspecialchars($num_people); ?>">
  
    <!-- Passenger information fields will be generated here dynamically -->
    <div class ="form-section" id="form-section">
      <?php for ($i = 1; $i <= $num_people; $i++): ?>
      <div class="form-container">
        <h2>Visitor #<?php echo $i; ?> Information</h2>
        
        <label for="name_<?php echo $i; ?>">Visitor Name:</label>
        <input type="text" id="name_<?php echo $i; ?>" name="name[]" required>

        <div class="flex-container">
          <div class="flex-item">
            <label for="gender_<?php echo $i; ?>">Gender:</label>
            <select id="gender_<?php echo $i; ?>" name="gender[]" required>
              <option value="" disabled selected>Select your gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div class="flex-item">
            <label for="age_<?php echo $i; ?>">Age:</label>
            <input type="number" id="age_<?php echo $i; ?>" name="age[]" min="0" required>
          </div>
        </div>

        <div class="flex-container">
          <div class="flex-item">
            <label for="type_<?php echo $i; ?>">Type:</label>
            <select id="type_<?php echo $i; ?>" name="type[]" required>
              <option value="" disabled selected>Select type</option>
              <option value="Local">Local</option>
              <option value="Non-Hinunangnon">Non-Hinunangnon</option>
              <option value="Foreign">Foreign</option>
            </select>
          </div>
          <div class="flex-item">
            <label for="citizenship_<?php echo $i; ?>">Citizenship:</label>
            <input type="text" id="citizenship_<?php echo $i; ?>" name="citizenship[]" required>
          </div>
        </div>

        <label for="address_<?php echo $i; ?>">Address:</label>
        <input type="text" id="address_<?php echo $i; ?>" name="address[]" required>

        <label for="mobile_<?php echo $i; ?>">Mobile Number:</label>
        <input type="tel" id="mobile_<?php echo $i; ?>" name="mobile[]">

        <label for="email_<?php echo $i; ?>">Email:</label>
        <input type="email" id="email_<?php echo $i; ?>" name="email[]">
      </div>
      <?php endfor; ?>
    </div>

    <!-- Hidden fields for other reservation details -->
    <input type="hidden" name="reservation_date" value="<?php echo htmlspecialchars($reservation_date); ?>">
    <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">
    <input type="hidden" name="num_people" value="<?php echo htmlspecialchars($num_people); ?>">

    <button type="submit" class="submit-btn">Submit</button>
</form>
</div>
    <div class="floating-home" onclick="location.href='index.php';">
      <i class="fas fa-home"></i>
    </div>

<script>

let guestCount = <?php echo $num_people; ?>;

function updateGuestCountDisplay() {
  // Update displayed guest count and hidden input value
  document.getElementById("guest-count").textContent = guestCount;
  document.getElementById("num-people-input").value = guestCount;
}
function addGuestForm() {
  const formSection = document.getElementById("form-section");

  const formContainer = document.createElement("div");
  formContainer.classList.add("form-container");

  formContainer.innerHTML = `
    <h2>Visitor #${guestCount + 1} Information</h2>
    <label for="name_${guestCount + 1}">Visitor Name:</label>
    <input type="text" id="name_${guestCount + 1}" name="name[]" required>

    <div class="flex-container">
      <div class="flex-item">
        <label for="gender_${guestCount + 1}">Gender:</label>
        <select id="gender_${guestCount + 1}" name="gender[]" required>
          <option value="" disabled selected>Select your gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>
      </div>
      <div class="flex-item">
        <label for="age_${guestCount + 1}">Age:</label>
        <input type="number" id="age_${guestCount + 1}" name="age[]" min="0" required>
      </div>
    </div>

    <div class="flex-container">
      <div class="flex-item">
        <label for="type_${guestCount + 1}">Type:</label>
        <select id="type_${guestCount + 1}" name="type[]" required>
          <option value="" disabled selected>Select type</option>
          <option value="Local">Local</option>
          <option value="Non-Hinunangnon">Non-Hinunangnon</option>
          <option value="Foreign">Foreign</option>
        </select>
      </div>
      <div class="flex-item">
        <label for="citizenship_${guestCount + 1}">Citizenship:</label>
        <input type="text" id="citizenship_${guestCount + 1}" name="citizenship[]" required>
      </div>
    </div>

    <label for="address_${guestCount + 1}">Address:</label>
    <input type="text" id="address_${guestCount + 1}" name="address[]" required>

    <label for="mobile_${guestCount + 1}">Mobile Number:</label>
    <input type="tel" id="mobile_${guestCount + 1}" name="mobile[]">

    <label for="email_${guestCount + 1}">Email:</label>
    <input type="email" id="email_${guestCount + 1}" name="email[]">
  `;
  formSection.appendChild(formContainer);
  guestCount++;
  updateGuestCountDisplay();
}

function removeGuestForm() {
  const formSection = document.getElementById("form-section");
  if (guestCount > 1) {
      formSection.removeChild(formSection.lastElementChild);
      guestCount--;
      updateGuestCountDisplay();
  } else {
      alert("You must have at least one passenger.");
  }
}

// Ensure initial update of the guest count display
updateGuestCountDisplay();

function updateGuestCountDisplay() {
    // Update displayed guest count
    document.getElementById("guest-count").textContent = guestCount;

    // Update the hidden inputs for num_people
    document.getElementById("num-people-input").value = guestCount;
    document.querySelector("input[name='num_people']").value = guestCount; // Update the static hidden field
}


function openLogin() {
        window.location.href = 'log-in.php';
    }

  </script>
</body>
</html>
