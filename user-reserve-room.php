<?php
session_start();
include 'includes/dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: log-in.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve user info
$query = "SELECT fullname, username, email, profile_photo, role_id FROM users_tbl WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$profile_photo = !empty($user['profile_photo']) ? $user['profile_photo'] : 'img/default-profile.jpg';
$stmt->close();

// Retrieve business ID and details
$business_id = isset($_POST['business_id']) ? intval($_POST['business_id']) : null;
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : null;

if ($business_id && $room_id) {
    // Fetch business details
    $query = "SELECT name, address FROM businesses_tbl WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $business_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $business = $result->fetch_assoc();
        $business_name = $business['name'];
        $business_address = $business['address'];
    } else {
        echo "Error: Business not found.";
        exit();
    }
    $stmt->close();

    // Fetch room price
    $query = "SELECT price FROM rooms_tbl WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $fee = $result->fetch_assoc();
        $room_price = $fee['price'];
    } else {
        echo "Error: Room not found.";
        exit();
    }
    $stmt->close();
} else {
    echo "Error: Required data not provided.";
    exit();
}

// Handle reservation details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = htmlspecialchars($_POST['check_in']);
    $check_out = htmlspecialchars($_POST['check_out']);
    $adults = intval($_POST['adults']);
    $children = intval($_POST['children']);
    $rooms_requested = intval($_POST['rooms']);
    $room_type = htmlspecialchars($_POST['room_type']);

    // Format check-in and check-out dates
    $check_in_formatted = date("M j, Y", strtotime($check_in));
    $check_out_formatted = date("M j, Y", strtotime($check_out));
    $adults_text = $adults === 1 ? "Adult" : "Adults";
    $children_text = $children === 1 ? "Child" : "Children";
    $rooms_text = $rooms_requested === 1 ? $room_type : $room_type . "s";

    // Calculate total price
    $total_price = $room_price * $rooms_requested;
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

    /* Main container for the form */
    .reservation-container {
    display: grid;
    grid-template-columns: 1fr 1fr; /* Two equal columns */
    gap: 20px; /* Space between columns */
    width: 1000px;
    margin: 40px auto;
    background-color: #333;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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

    /* Form Fields */
    .form-field {
      margin-bottom: 20px;
      width: 100%;
    }

    .form-field label {
      display: block;
      margin-bottom: 5px;
      color: white;
    }

    /* Form containers */
    .form-container {
      display: flex;
      justify-content: space-between;
      width: 100%;
      gap: 20px;
    }

    .form-fields-container {
    grid-column: 1; /* First column */
    background-color: #444;
    padding: 15px 15px 0 15px;
    border-radius: 7.5px;
  }

  /* Right column with stacked containers */
  .right-column {
    grid-column: 2; /* Second column */
    display: flex;
    flex-direction: column;
    gap: 15px;
  }

  .notes-container{
    background-color: #444;
    padding: 15px;
    border-radius: 7.5px;
  }
  .price-container {
    background-color: #333;
    padding: 15px 15px 0 15px;
    border-radius: 7.5px;
  }
  .price-details p {
    margin-bottom: -5px;
    color:#ccc
  } 
  .price-details h3 {
    margin-top: 25px;
    margin-bottom: 15px;
    font-size: 25px;
    color: white;
}
.button-container {
    background-color: #333;
    padding: 0 0 15px 0;
    border-radius: 7.5px;
  }

/* Form field inputs */
.form-field input,
.form-field select {
    width: 95%;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #555;
    border-radius: 5px;
    background-color: #333;
    color: white;
}


.button-container {
    display: flex;
    justify-content: flex-end;
  }

  .button-container button {
    margin-left: 10px;
  }

    /* Button Group */
    .button-group {
      display: flex;
      justify-content: flex-end;
      margin-top: 20px;
      width: 100%;
    }

    .button-group button {
      padding: 10px 50px;
      font-size: 16px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      margin-left: 10px;
    }

    .button-group .next-btn {
      background-color: black;
      color: white;
    }

    .button-group .back-btn {
      background-color: #ccc;
      color: black;
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
    padding: 0;
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

    /* Modal Styling */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.8);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: #333;
      padding: 20px;
      border-radius: 8px;
      width: 300px;
      text-align: center;
      position: relative;
    }
    .modal-content h2 {
    text-align: left;
    color: white;
    margin: 0 0 20px 0;
  }

    .modal-content ul {
      list-style: none;
      padding: 0;
    }

/* Modal list items */
.modal-content ul li {
  background-color: #333;
  margin: 0;
  padding: 10px;
  cursor: pointer;
  color: white;
  position: relative; /* For the circle */
  display: flex;
  align-items: center;
}

.modal-content ul li::before {
  content: '';
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid white;
  margin-right: 10px;
  display: inline-block;
}

.modal-content ul li.selected::before {
  background-color: white; /* Fill the circle when selected */
}

.modal-content ul li:hover {
  background-color: #555;
}

.modal-content ul li:not(:last-child) {
  border-bottom: 1px solid #444; /* Thin separator line */
}

    .modal-content .close {
      position: absolute;
      top: 10px;
      right: 15px;
      color: white;
      cursor: pointer;
      font-size: 25px;
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

<div class="reservation-container">
    <!-- Store Info -->
    <div class="store-info">
        <h1>Reserve room/s at <?php echo htmlspecialchars($business_name); ?></h1>
        <div class="store-address">
            <i class="fas fa-map-marker-alt" style="color: white;"></i>
            <p><?php echo htmlspecialchars($business_address); ?></p>
        </div>

        <!-- Reservation Details moved here -->
        <div class="reservation-details">
            <div class="detail">
                <i class="fas fa-calendar" style="color: white;"></i>
                <p><?php echo htmlspecialchars($check_in_formatted); ?> - <?php echo htmlspecialchars($check_out_formatted); ?></p>
            </div>
            <div class="detail">
                <i class="fas fa-users" style="color: white;"></i>
                <p>
                <?php echo htmlspecialchars($adults); ?>
                <?php echo htmlspecialchars($adults_text); ?> · 
                <?php echo htmlspecialchars($children); ?>
                <?php echo htmlspecialchars($children_text); ?>
              </p>
            </div>
            <div class="detail">
                <i class="fas fa-bed" style="color: white;"></i>
                <p>
                <?php echo htmlspecialchars($rooms_requested); ?> 
                <?php echo htmlspecialchars($rooms_text); ?>
              </p>
            </div>
        </div>
    </div>


    <div class="form-fields-container">
    <form action="user-submit-room-reservation.php" id="reservation-form" method="POST">
        <input type="hidden" name="reservation_form" value="1">
        <input type="hidden" name="business_id" value="<?php echo $business_id; ?>">
        <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
        <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">
        <input type="hidden" name="num_rooms" value="<?php echo $rooms_requested; ?>">
        <input type="hidden" name="adults" value="<?php echo $adults; ?>">
        <input type="hidden" name="children" value="<?php echo $children; ?>">
        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
        <input type="hidden" name="room_type" value="<?php echo $room_type; ?>">

        <div class="form-field">
            <label for="lastname">Last Name:</label>
            <input type="text" id="lastname" name="lastname" required>
        </div>
        <div class="form-field">
            <label for="firstname">First Name:</label>
            <input type="text" id="firstname" name="firstname" required>
        </div>
        <div class="form-field">
            <label for="gender">Gender:</label>
            <input type="text" id="gender" name="gender" placeholder="Select Gender" readonly onclick="openModal('gender-modal')" required>
        </div>

        <div class="form-field">
          <label for="tourist_type">Tourist Type:</label>
          <input type="text" id="tourist_type" name="tourist_type" placeholder="Select Tourist Type" readonly onclick="openModal('tourist-type-modal')" required>
        </div>

        <div class="form-field">
            <label for="phone">Phone No.:</label>
            <input type="phone" id="phone" name="phone" required>
        </div>
        <div class="form-field">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
</div>


<div class="right-column">
        <div class="notes-container">
            <div class="form-field">
                <label for="notes">Notes and Special Requests:</label>
                <textarea id="notes" name="notes" rows="8" placeholder="Enter any special requests or notes" style="width: 95%; padding: 10px; font-size: 16px; border: 1px solid #555; border-radius: 5px; background-color: #222; color: white;"></textarea>
            </div>
        </div>
        
    <div class="price-container">
    <div class="price-details">
            <p><strong>Note:</strong> <em>The reservation payment details will be sent to the email provided.</em></p>
            <p><strong>Room Price:</strong> PHP <?php echo number_format($room_price, 2); ?> x <?php echo htmlspecialchars($rooms_requested); ?></p>
            <p><strong>Environmental Fee (per person):</strong> N/A</p>
            <h3>Total Price: PHP <?php echo number_format($total_price, 2); ?></h3>
            <input type="hidden" id="payment_price" name="payment_price" value="<?php echo htmlspecialchars($total_price); ?>">
    </div>
    </div>
    <div class="button-container">
    <div class="button-group">
            <button type="submit" class="next-btn">Reserve</button>
        </div>
    </form>
    </div>
</div>
  </div>

<!-- Gender Modal -->
<div id="gender-modal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('gender-modal')">&times;</span>
    <h2>Select Gender</h2>
    <ul>
      <li onclick="selectOption('gender', 'Male')">Male</li>
      <li onclick="selectOption('gender', 'Female')">Female</li>
    </ul>
  </div>
</div>

<div class="modal" id="tourist-type-modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('tourist-type-modal')">&times;</span>
        <h2>Select Tourist Type</h2>
        <ul>
            <li onclick="selectOption('tourist-type-modal', 'tourist_type', 'Local')">Local</li>
            <li onclick="selectOption('tourist-type-modal', 'tourist_type', 'Non-Hinunangnon')">Non-Hinunangnon</li>
            <li onclick="selectOption('tourist-type-modal', 'tourist_type', 'Foreign')">Foreign</li>
          </ul>
    </div>
</div>

<div class="floating-home" onclick="location.href='user-login-index.php';">
  <i class="fas fa-home"></i>
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

// Function to open modal
  // Function to open a modal
  function openModal(modalId) {
    document.getElementById(modalId).style.display = "flex";
  }

  // Function to close a modal
  function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
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

// Function to handle closing the modal if clicked outside content
function outsideClickListener(event) {
  const modals = document.getElementsByClassName('modal');
  for (let modal of modals) {
    if (event.target === modal) {
      closeModal(modal.id);
    }
  }
}

// General function to handle option selection for modals
function selectOption(modalId, inputId, selectedText) {
    document.getElementById(inputId).value = selectedText;

    // Remove 'selected' class from all options in the modal
    const options = document.querySelectorAll(`#${modalId} ul li`);
    options.forEach(option => option.classList.remove('selected'));

    // Add 'selected' class to the clicked option
    const selectedOption = Array.from(options).find(option => option.textContent.trim() === selectedText);
    if (selectedOption) selectedOption.classList.add('selected');

    closeModal(modalId);
}

  // Existing dropdown toggle script
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
  
  window.onclick = function(event) {
    const dropdown = document.getElementById("dropdown-menu");
    if (!event.target.closest('.profile-icon') && !event.target.closest('.dropdown-menu')) {
      dropdown.style.display = "none";
    }
  }

  document.getElementById("dropdown-menu").addEventListener("click", function(event) {
    event.stopPropagation();
  });

  // Event listeners for opening modals and selecting options
document.getElementById('gender').addEventListener('click', () => openModal('gender-modal'));
document.getElementById('tourist_type').addEventListener('click', () => openModal('citizenship-modal'));

// Add click events to all gender options
document.querySelectorAll('#gender-modal ul li').forEach(option => {
    option.addEventListener('click', () => {
        selectOption('gender-modal', 'gender', option.textContent.trim());
    });
});

// Add click events to all citizenship options
document.querySelectorAll('#citizenship-modal ul li').forEach(option => {
    option.addEventListener('click', () => {
        selectOption('citizenship-modal', 'tourist_type', option.textContent.trim());
    });
});


  

</script>
</body>
</html>
