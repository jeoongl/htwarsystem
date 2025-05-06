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
$business_id = isset($_GET['business_id']) ? $_GET['business_id'] : null;

$business_query = "SELECT name, address, opening_time, closing_time FROM businesses_tbl WHERE id = ?";
$business_stmt = $conn->prepare($business_query);
$business_stmt->bind_param("i", $business_id);
$business_stmt->execute();
$business_result = $business_stmt->get_result();
$business = $business_result->fetch_assoc();

$business_name = $business['name']; // Business name
$business_address = $business['address']; // Business address
$opening_time = $business['opening_time']; // Opening time
$closing_time = $business['closing_time']; // Closing time

$business_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $business_id = $_POST['business_id'];
  $reservation_date = $_POST['reservation_date'];
  $reservation_time = $_POST['reservation_time'];
  $num_people = $_POST['num_people'];

  // Assuming you have a connection to the database ($conn)

  // Prepare SQL query to insert reservation data
  $reservation_query = "INSERT INTO reservations_tbl (business_id, reservation_date, reservation_time, num_people) VALUES (?, ?, ?, ?)";
  $stmt = $conn->prepare($reservation_query);
  $stmt->bind_param("isss", $business_id, $reservation_date, $reservation_time, $num_people);
  $stmt->execute();

  if ($stmt->affected_rows > 0) {
      echo "Reservation successful!";
      // Optionally, redirect to a confirmation page
      header('Location: confirmation.php');
  } else {
      echo "Reservation failed. Please try again.";
  }

  $stmt->close();
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Table Reservation Form</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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

    /* Main container for the calendar, time selector, and number of people */
    .calendar-container {
      display: flex;
      flex-direction: column;
      justify-content: start;
      align-items: center;
      background-color: #444;
      color: black;
      border-radius: 10px;
      padding: 20px;
      width: 1000px;
      margin: 40px auto;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    /* Store Info */
    .store-info {
      width: 100%;
      text-align: left;
      margin-bottom: 20px;
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

    /* Calendar and Time Selectors */
    .calendar-row {
      display: flex;
      justify-content: start;
      align-items: stretch;
      width: 100%;
    }

    .calendar-group {
      display: flex;
      width: 50%;
    }

    .calendar-date {
      background-color: black;
      color: white;
      padding: 20px;
      text-align: center;
      width: 150px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      border-top-left-radius: 10px;
      border-bottom-left-radius: 10px;
    }

    .calendar-date h2 {
      margin: 0;
      font-size: 24px;
    }

    .calendar-date p {
      margin: 5px 0;
    }

    .flatpickr-calendar {
      width: calc(100% - 180px);
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
      border-top-right-radius: 10px;
      border-bottom-right-radius: 10px;
      background-color: white;
      color: black;
      display: flex;
    }

    .flatpickr-day.selected {
      background-color: black;
      color: white;
      border-radius: 50%;
    }

    .time-and-buttons {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      margin-left: 20px;
      width: 50%;
    }

    .time-selector-container {
      background-color: #333;
      color: white;
      padding: 20px;
      border-radius: 10px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      margin-bottom: 10px;
    }

    .time-selector-container label {
      margin-bottom: 10px;
      font-size: 16px;
      font-weight: normal;
    }

    .time-selector {
      padding: 10px;
      font-size: 16px;
      border-radius: 5px;
      background-color: #222;
      border: 1px solid #555;
      color: white;
      width: 100%;
    }

    .number-input-container {
      background-color: #333;
      color: white;
      padding: 20px;
      border-radius: 10px;
      display: flex;
      flex-direction: column;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      margin-bottom: 10px;
    }

    .number-label {
      margin-bottom: 10px;
      font-size: 16px;
      font-weight: normal;
    }

    .number-input-wrapper {
      display: flex;
      align-items: center;
    }

    .number-input {
      width: 60px;
      padding: 10px;
      font-size: 16px;
      border: 1px solid #555;
      border-radius: 5px;
      background-color: #222;
      color: white;
      text-align: center;
      margin-right: 10px;
    }

    .number-buttons {
      display: flex;
    }

    .number-buttons button {
      width: 40px;
      height: 40px;
      background-color: #555;
      color: white;
      border: none;
      font-size: 18px;
      cursor: pointer;
      border-radius: 5px;
    }

    .number-buttons .minus-btn {
      background-color: red;
    }

    .number-buttons .plus-btn {
      background-color: green;
      margin-left: 10px;
    }

    .button-group {
      display: flex;
      justify-content: space-between;
    }

    .button-group button {
      flex: 1;
      padding: 10px;
      font-size: 16px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .button-group .back-btn {
      background-color: #ccc;
      color: black;
      margin-right: 10px;
    }

    .button-group .next-btn {
      background-color: black;
      color: white;
    }

    .button-group {
      margin-top: auto; /* Push buttons to the bottom */
      display: flex;
      justify-content: flex-end; /* Align buttons to the right */
    }
    .store-address {
        display: flex; /* Use flexbox to align items */
        align-items: center; /* Center items vertically */
        color: white; /* Set text color to white */
    }

    .store-address i {
        margin-right: 8px; /* Add some space between the icon and the address */
        margin-top: -15px;
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

<div class="calendar-container">
    <div class="store-info">
        <h1>Reserve table at <?php echo $business_name; ?></h1>
        <div class="store-address">
          <i class="fas fa-map-marker-alt" style="color: white;"></i>
          <p><?php echo $business_address; ?></p>
        </div>
    </div>

    <div class="calendar-row">
  <div class="calendar-group">
    <div class="calendar-date">
      <h2 id="calendar-year"></h2>
      <p id="calendar-day"></p>
      <span id="selected-date" style="font-size: 18px; color: white;"></span> <!-- Added span for selected date -->
    </div>
    <div id="calendar"></div>
  </div>
  
  <div class="time-and-buttons">
    <div class="time-selector-container">
      <label for="time-selector">Select Time:</label>
      <select id="time-selector" class="time-selector"></select>
    </div>
    <div class="number-input-container">
      <label class="number-label" for="number-of-people">Number of People:</label>
      <div class="number-input-wrapper">
        <input type="text" id="number-of-people" class="number-input" value="1" readonly>
        <div class="number-buttons">
          <button class="minus-btn" onclick="decrement()">-</button>
          <button class="plus-btn" onclick="increment()">+</button>
        </div>
      </div>
    </div>
    <div class="button-group">
      <button class="back-btn">BACK</button>
      <<button class="next-btn" onclick="showDetailsForm()">NEXT</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  var openingTime = '<?php echo $opening_time; ?>';
  var closingTime = '<?php echo $closing_time; ?>';

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
  }

  document.getElementById("dropdown-menu").addEventListener("click", function(event) {
    event.stopPropagation();
  });

  // Get current date
  const currentDate = new Date();
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  const formattedCurrentDate = currentDate.toLocaleDateString('en-US', options);

  // Set default display for calendar date
  document.getElementById('calendar-year').textContent = currentDate.getFullYear();
  document.getElementById('calendar-day').textContent = currentDate.toLocaleString('en-US', { weekday: 'long' });
  document.getElementById('selected-date').textContent = formattedCurrentDate;

  flatpickr("#calendar", {
    inline: true,
    defaultDate: currentDate, // Set default date to current date
    onChange: function(selectedDates) {
      const selectedDate = selectedDates[0];
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      const formattedDate = selectedDate.toLocaleDateString('en-US', options);
      
      document.getElementById('calendar-year').textContent = selectedDate.getFullYear();
      document.getElementById('calendar-day').textContent = selectedDate.toLocaleString('en-US', { weekday: 'long' });
      document.getElementById('selected-date').textContent = formattedDate; // Update selected date display
    }
  });

  // Increment and decrement functions remain unchanged
  function increment() {
    const numberInput = document.getElementById('number-of-people');
    numberInput.value = parseInt(numberInput.value) + 1;
  }

  function decrement() {
    const numberInput = document.getElementById('number-of-people');
    if (numberInput.value > 1) {
      numberInput.value = parseInt(numberInput.value) - 1;
    }
  }

  function generateTimeSlots(openingTime, closingTime) {
    const timeSelector = document.getElementById('time-selector');
    timeSelector.innerHTML = '';
    
    const openingDate = new Date(`1970-01-01T${openingTime}`);
    const closingDate = new Date(`1970-01-01T${closingTime}`);

    const timeOptions = [];
    let currentTime = new Date(openingDate);

    while (currentTime <= closingDate) {
      const hours = currentTime.getHours();
      const minutes = currentTime.getMinutes();
      const timeString = formatTime(hours, minutes);
      timeOptions.push(timeString);
      currentTime.setMinutes(currentTime.getMinutes() + 60);
    }

    timeOptions.forEach(time => {
      const option = document.createElement('option');
      option.textContent = time;
      option.value = time;
      timeSelector.appendChild(option);
    });
  }

  function formatTime(hours, minutes) {
    const period = hours >= 12 ? 'PM' : 'AM';
    const formattedHours = hours % 12 || 12;
    const formattedMinutes = minutes < 10 ? `0${minutes}` : minutes;
    return `${formattedHours}:${formattedMinutes} ${period}`;
  }

  generateTimeSlots(openingTime, closingTime);
</script>
</body>
</html>
