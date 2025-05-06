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

// Query to fetch user information
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


$show_services_option = false; // Default: do not show "Services"

$boat_price = 'N/A';
$ecoattraction_environmental_fee = 'N/A';
$island_environmental_fee = 'N/A';
$dining_environmental_fee = 'N/A';
$registration_fee = 'N/A';
$table_fee = 'N/A';

if ($business_id) {
    $business_query = "SELECT name, category FROM businesses_tbl WHERE id = ?";
    $business_stmt = $conn->prepare($business_query);
    $business_stmt->bind_param("i", $business_id);
    $business_stmt->execute();
    $business_result = $business_stmt->get_result();
    $business = $business_result->fetch_assoc();
    
    $category = $business['category'] ?? null;

    // If category is 1, fetch boat_price and environmental_fee
    if ($category == 1) {
        $fare_query = "SELECT boat_price, environmental_fee FROM boat_fare_tbl WHERE business_id = ?";
        $fare_stmt = $conn->prepare($fare_query);
        $fare_stmt->bind_param("i", $business_id);
        $fare_stmt->execute();
        $fare_result = $fare_stmt->get_result();
        $fare = $fare_result->fetch_assoc();

        if ($fare) {
            $boat_price = $fare['boat_price'];
            $island_environmental_fee = $fare['environmental_fee'];
        }

        $fare_stmt->close();
    }

    elseif ($category == 2) {
      $registration_query = "SELECT registration_price, environmental_fee FROM registration_prices_tbl WHERE business_id = ?";
      $registration_stmt = $conn->prepare($registration_query);
      $registration_stmt->bind_param("i", $business_id);
      $registration_stmt->execute();
      $registration_result = $registration_stmt->get_result();
      $registration = $registration_result->fetch_assoc();

      if ($registration) {
          $registration_fee = $registration['registration_price'];
          $ecoattraction_environmental_fee = $registration['environmental_fee'];
      }

      $registration_stmt->close();
  }

    elseif ($category == 4) {
      $table_query = "SELECT table_price, environmental_fee FROM table_prices_tbl WHERE business_id = ?";
      $table_stmt = $conn->prepare($table_query);
      $table_stmt->bind_param("i", $business_id);
      $table_stmt->execute();
      $table_result = $table_stmt->get_result();
      $table = $table_result->fetch_assoc();

      if ($table) {
          $table_fee = $table['table_price'];
          $dining_environmental_fee = $table['environmental_fee'];
      }

      $table_stmt->close();
  }

}

$terms_and_conditions = "Default terms and conditions text.";
$contact_no = 'N/A';
$email = 'N/A';
$social_links = [
  'facebook' => '',
  'instagram' => '',
  'x_twitter' => '',
  'linkedin' => '',
  'google_maps_link' => ''
];
// Default Terms and Conditions text
$default_terms_and_conditions = "1. Agreement to Terms\nBy completing your reservation and payment, you agree to these Terms and Conditions. Please review them carefully.\n
2. Cancellation and Rescheduling Policy\nReservations cannot be cancelled or rescheduled through the website.\nHowever, if you need to cancel or reschedule, you must contact the store directly using the provided contact information.\nRefunds or changes may be possible only upon mutual agreement with the store and may incur additional fees.\n
3. Contact Information\nFor any inquiries, cancellations, or rescheduling requests, please contact the store using the details in your booking confirmation.\n
4. Customer Responsibilities\nCustomers must ensure all booking details are accurate.\nArrive on time as specified in the booking confirmation.\nLate arrivals may result in forfeiture of the reservation.\n
5. Store Responsibilities\nThe store is responsible for providing the services as detailed in the booking confirmation.\nMaintain accurate and accessible contact information for customers.\n
6. Force Majeure\nThe store is not liable for cancellations or changes due to events beyond its control, such as natural disasters or government actions.\n
7. Acceptance of Terms\nBy making a reservation, you acknowledge and accept these Terms and Conditions in full.";

// Fetch business contact information including social links and terms and conditions
if ($business_id) {
    $contactQuery = "SELECT contact_no, email, facebook, instagram, x_twitter, linkedin, google_maps_link, terms_and_conditions FROM business_embeds_tbl WHERE business_id = ?";
    $stmt = $conn->prepare($contactQuery);
    $stmt->bind_param("i", $business_id);
    $stmt->execute();
    $contactResult = $stmt->get_result();
    $contactInfo = $contactResult->fetch_assoc();

    if ($contactInfo) {
        $contact_no = $contactInfo['contact_no'];
        $email = $contactInfo['email'];
        $social_links['facebook'] = $contactInfo['facebook'];
        $social_links['instagram'] = $contactInfo['instagram'];
        $social_links['x_twitter'] = $contactInfo['x_twitter'];
        $social_links['linkedin'] = $contactInfo['linkedin'];
        $social_links['google_maps_link'] = $contactInfo['google_maps_link'];

        // Use default terms if terms_and_conditions is empty
        $terms_and_conditions = !empty($contactInfo['terms_and_conditions']) ? $contactInfo['terms_and_conditions'] : $default_terms_and_conditions;
    } else {
        // Use default terms if no data is fetched
        $terms_and_conditions = $default_terms_and_conditions;
    }
    $stmt->close();
} else {
    // Use default terms if business_id is not provided
    $terms_and_conditions = $default_terms_and_conditions;
}

// Check if business_id is provided
if ($business_id) {
    // Query to fetch business hours from businesses_tbl
    $businessQuery = "SELECT opening_time, closing_time FROM businesses_tbl WHERE id = ?";
    $stmt = $conn->prepare($businessQuery);
    $stmt->bind_param("i", $business_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $businessHours = $result->fetch_assoc();
    
    // Check if business hours are available
    if ($businessHours) {
        $opening_time = $businessHours['opening_time'];
        $closing_time = $businessHours['closing_time'];

    } else {
        // Set default values if no business hours are found
        $opening_time = 'N/A';
        $closing_time = 'N/A';
    }
    $stmt->close();
} else {
    // Set default values if business_id is not provided
    $opening_time = 'N/A';
    $closing_time = 'N/A';
}

$rooms = [];
if ($business_id && $category == 3) {
    $rooms_query = "SELECT id, room_type, max_occupancy, price, total_rooms FROM rooms_tbl WHERE business_id = ?";
    $rooms_stmt = $conn->prepare($rooms_query);
    $rooms_stmt->bind_param("i", $business_id);
    $rooms_stmt->execute();
    $rooms_result = $rooms_stmt->get_result();
    while ($room = $rooms_result->fetch_assoc()) {
        $rooms[] = $room;
    }
    $rooms_stmt->close();
}

// Initialize default values
$paymentOptions = [
    'cash' => null,
    'payment_option_1' => null,
    'payment_option_2' => null,
    'payment_option_3' => null,
];

// Fetch payment options from the database if the business ID exists
if ($business_id) {
    $query = "SELECT cash_option, payment_option_1, payment_option_2, payment_option_3 FROM payment_options WHERE business_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $business_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $paymentOptions = $result->fetch_assoc();
    }
    $stmt->close();
}

$paymentOptions['cash'] = $paymentOptions['cash_option'] == 1 ? true : false;


// Output the payment options as a JavaScript variable
echo "<script>const availablePaymentOptions = " . json_encode($paymentOptions) . ";</script>";


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

    .container-wrapper {
      display: flex;
      justify-content: center;
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
      height: calc(80vh - 60px); /* Adjust height based on header and margins */
      overflow: hidden;
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
      box-sizing: border-box; /* Include padding in width/height */
    }

    .content::-webkit-scrollbar {
      width: 15px; /* Adjust scrollbar width */
    }

    .content::-webkit-scrollbar-track {
      background: transparent; /* Optional: Adjust the scrollbar track color */
    }

    .content::-webkit-scrollbar-thumb {
      background-color: #555; /* Scrollbar color */
      border-radius: 10px; /* Rounded corners */
      border: 3px solid #222; /* Create space around scrollbar */
    }
    .container h1 {
    font-size: 28px;
    text-align: left;
    margin-left: 15px;
    margin-bottom: -5px; /* Reduce spacing */
    color: white;
}

.business-name {
    font-size: 18px;
    color: white;
    padding: 0 20px; /* Remove vertical padding */
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
    .terms-and-conditions-container {
      max-width: 100%;
      word-wrap: break-word;
      word-break: break-all; /* Ensures long URLs wrap */
      white-space: normal;
      overflow: hidden;
      color: white; /* Ensures white text across these sections */
    }

      /* Modal styles */
      .modal, .terms-modal {
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
      margin: 10% auto;
      padding: 20px;
      border-radius: 5px;
      width: 80%;
      max-width: 400px;
      color: white;
    }
    .terms-modal-content {
      background-color: #222;
      margin: 10% auto;
      padding: 20px;
      border-radius: 5px;
      width: 80%;
      max-width: 600px;
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
      margin: 10px 0 5px;
    }
    .modal-content input[type="time"], .modal-content input[type="text"], 
    .modal-content input[type="email"], .modal-content input[type="number"],
    .modal-content textarea,  .modal-content select {
      width: 100%;
      padding: 8px;
      margin-bottom: 15px;
      background-color: #333;
      color: white;
      border: none;
      border-radius: 3px;
    }
    .terms-modal-content textarea {
      width: 100%;
      height: 25%;
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
    .add-button {
    background-color: #28a745;
    color: #fff;
    border: none;
    padding: 8px 12px;
    cursor: pointer;
    font-size: 16px;
    border-radius: 4px;
}

.add-button:hover {
    background-color: #218838;
}
table {
  width: 100%;
  color: white;
  border-collapse: collapse;
}
th, td {
  text-align: center; /* Center-align text in all cells */
  padding: 5px;       /* Add padding for better spacing */
  border: 1px solid white; /* Optional: to better define table boundaries */
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

<div class="container-wrapper">
    <div class="container">
        <h1>
            <button class="back-button" onclick="goBack()">
                <i class="fa fa-angle-left"></i>
            </button>
            Business Settings
        </h1>
        <p class="business-name"><i class="fas fa-store"></i> <?php echo htmlspecialchars($business['name']); ?></p>
        <div class="content-wrapper">
            <div class="sidebar">
            <ul>
                <?php if ($category == 1): ?>
                    <li><a href="javascript:void(0);" onclick="showContent('boats')">Boats</a></li>
                <?php elseif ($category == 2): ?>
                    <li><a href="javascript:void(0);" onclick="showContent('registration')">Registration</a></li>
                <?php elseif ($category == 3): ?>
                    <li><a href="javascript:void(0);" onclick="showContent('rooms')">Rooms</a></li>
                <?php elseif ($category == 4): ?>
                    <li><a href="javascript:void(0);" onclick="showContent('tables')">Tables</a></li>
                <?php endif; ?>
                <li><a href="javascript:void(0);" onclick="showContent('business-hours')">Business Hours</a></li>
                <li><a href="javascript:void(0);" onclick="showContent('payment-options')">Payment Settings</a></li>
                <li><a href="javascript:void(0);" onclick="showContent('contact-info')">Contact Information</a></li>
                <li><a href="javascript:void(0);" onclick="showContent('social-links')">Social Links</a></li>
                <li><a href="javascript:void(0);" onclick="showContent('google-maps-embed')">Google Maps Embed</a></li>
                <li><a href="javascript:void(0);" onclick="showContent('terms-and-conditions')">Terms and Conditions</a></li>
            </ul>
            </div>

            <div class="content" id="content">
            <?php if ($category == 1): ?>
              <div id="boats">
                <div class="edit-container">
                      <h2>Boats</h2>
                      <p><strong>Boat Price: </strong><?php echo htmlspecialchars($boat_price); ?></p>
                      <p><strong>Environmental Fee: </strong><?php echo htmlspecialchars($island_environmental_fee); ?></p>
                      <button class="edit-button" onclick="showModal('boatPriceModal')">Edit Prices</button>
                  </div>
                </div>
                <?php elseif ($category == 2): ?>
                  <div id="registration">
                    <div class="edit-container">
                      <h2>Registration</h2>
                      <p><strong>Registration Fee: </strong><?php echo htmlspecialchars($registration_fee); ?></p>
                      <p><strong>Environmental Fee: </strong><?php echo htmlspecialchars($ecoattraction_environmental_fee); ?></p>
                      <button class="edit-button" onclick="showModal('ecoattractionPriceModal')">Edit Prices</button>
                  </div>
                </div>
                <?php elseif ($category == 3): ?>
                  <div id="rooms">
                    <div class="edit-container">
                    <h2>Rooms <button class="add-button" onclick="showModal('addRoomModal')">+</button></h2>
                    <?php if (!empty($rooms)) { ?>
                      <table border="1" style="width: 100%; color: white; border-collapse: collapse;">
                          <thead>
                              <tr>
                                  <th>Room Type</th>
                                  <th>Max Occupancy</th>
                                  <th>Price(PHP)</th>
                                  <th>Total Rooms</th>
                                  <th>Action</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php foreach ($rooms as $room) { ?>
                                  <tr>
                                      <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                      <td><?php echo htmlspecialchars($room['max_occupancy']); ?></td>
                                      <td><?php echo htmlspecialchars($room['price']); ?></td>
                                      <td><?php echo htmlspecialchars($room['total_rooms']); ?></td>
                                      <td>
                                      <button onclick="editRoom(<?php echo $room['id']; ?>)" style="color: green;">
                                          <i class="fa fa-edit"></i>
                                      </button>
                                      <button onclick="deleteRoom(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_type']); ?>')" style="color: red;">
                                          <i class="fa fa-trash"></i>
                                      </button>

                                      </button>
                                      </td>
                                  </tr>
                              <?php } ?>
                          </tbody>
                      </table>
                  <?php } else { ?>
                      <p>No rooms available for this business.</p>
                  <?php } ?>
                  </div>
                </div>
                <?php elseif ($category == 4): ?>
                  <div id="tables">
                  <div class="edit-container">
                    <h2>Tables</h2>
                    <p><strong>Table Price: </strong><?php echo htmlspecialchars($table_fee); ?></p>
                    <p><strong>Environmental Fee: </strong><?php echo htmlspecialchars($dining_environmental_fee); ?></p>
                    <button class="edit-button" onclick="showModal('tablePriceModal')">Edit Prices</button>
                  </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Business Hours Modal -->
<div id="businessHoursModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('businessHoursModal')">&times;</span>
    <h2>Edit Business Hours</h2>
      <form id="businessHoursForm" method="POST" action="update-business-hours.php">
      <label for="opening-time">Opening Time</label>
      <input type="time" id="opening-time" name="opening_time" value="<?php echo htmlspecialchars($opening_time); ?>">
      <label for="closing-time">Closing Time</label>
      <input type="time" id="closing-time" name="closing_time" value="<?php echo htmlspecialchars($closing_time); ?>">
      <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">
      <button type="submit" class="edit-button">Save Changes</button>
    </form>
  </div>
</div>

<div id="paymentOptionsModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('paymentOptionsModal')">&times;</span>
    <h2>Edit Payment Options</h2>
    <form id="paymentOptionsForm" method="POST" action="update-payment-options.php">
      <label for="cash_payment">Cash Payment Available:</label>
      <input type="checkbox" id="cash_payment" name="cash_payment" 
        <?php echo !empty($paymentOptions['cash_option']) ? 'checked' : ''; ?>>

      <label for="payment_option_1">Payment Option 1:</label>
      <input type="text" id="payment_option_1" name="payment_option_1"
        value="<?php echo htmlspecialchars($paymentOptions['payment_option_1'] ?? ''); ?>">

      <label for="payment_option_2">Payment Option 2:</label>
      <input type="text" id="payment_option_2" name="payment_option_2"
        value="<?php echo htmlspecialchars($paymentOptions['payment_option_2'] ?? ''); ?>">

      <label for="payment_option_3">Payment Option 3:</label>
      <input type="text" id="payment_option_3" name="payment_option_3"
        value="<?php echo htmlspecialchars($paymentOptions['payment_option_3'] ?? ''); ?>">

      <!-- Hidden field to pass business ID -->
      <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">

      <button type="submit" class="edit-button">Save Changes</button>
    </form>
  </div>
</div>



<!-- Payment Options Modal -->
<div id="tablePriceModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('tablePriceModal')">&times;</span>
    <h2>Edit Price</h2>
    <form id="tablePriceForm" method="POST" action="update-table-price.php">
    <label for="table-price">Table Price</label>
      <input type="number" id="table-price" name="table_price" value="<?php echo htmlspecialchars($table_fee); ?>" placeholder="Table price" min="0" step="0.01">
      
      <label for="dining-envi-fee">Environmental Fee</label>
      <input type="number" id="dining-envi-fee" name="dining_environmental_fee" value="<?php echo htmlspecialchars($dining_environmental_fee); ?>" placeholder="Environmental Fee" min="0" step="0.01">
      
      <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">
      <button type="submit" class="edit-button">Save Changes</button>
  </form>
  </div>
</div>

<!-- Payment Options Modal -->
<div id="ecoattractionPriceModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('ecoattractionPriceModal')">&times;</span>
    <h2>Edit Price</h2>
    <form id="ecoattractionPriceForm" method="POST" action="update-ecoattraction-price.php">
      <!-- Add fields for payment options here -->
      <label for="registration-fee">Registration Fee</label>
      <input type="number" id="registration-fee" name="registration_fee" placeholder="Registration Fee" value="<?php echo htmlspecialchars($registration_fee); ?>" min="0" step="0.01">
      <input type="number" id="ecoattraction-envi-fee" name="ecoattraction_envi_fee" placeholder="Environmental Fee"  value="<?php echo htmlspecialchars($ecoattraction_environmental_fee); ?>" min="0" step="0.01">

      <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">
      <button type="submit" class="edit-button">Save Changes</button>
    </form>
  </div>
</div>

<!-- Payment Options Modal -->
<div id="boatPriceModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('boatPriceModal')">&times;</span>
    <h2>Edit Price</h2>
    <form id="boatPriceForm" method="POST" action="update-boat-price.php">
      <!-- Add fields for payment options here -->
      <label for="boat-price">Boat Price</label>
      <input type="number" id="boat-price" name="boat_price" placeholder="Boat Price" value="<?php echo htmlspecialchars($boat_price); ?>" min="0" step="0.01">
      <label for="island-envi-fee">Environmental Fee</label>
      <input type="number" id="island-envi-fee" name="island_envi_fee" placeholder="Environmental Fee" value="<?php echo htmlspecialchars($island_environmental_fee); ?>" min="0" step="0.01">
      
      <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">
      <button type="submit" class="edit-button">Save Changes</button>
    </form>
  </div>
</div>

<!-- Payment Options Modal -->
<div id="contactInfoModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('contactInfoModal')">&times;</span>
    <h2>Edit Contact Info</h2>
    <form id="contactInfoForm" method="POST" action="update-contact-info.php">
      <!-- Add fields for payment options here -->
      <label for="contact-no">Contact Number</label>
      <input type="text" id="contact-no" name="contact_no" value="<?php echo htmlspecialchars($contact_no); ?>">

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">

      <!-- Hidden field to pass business ID -->
      <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">
      <button type="submit" class="edit-button">Save Changes</button>
    </form>
  </div>
</div>

<!--Social Links Modal -->
<div id="socialLinksModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('socialLinksModal')">&times;</span>
    <h2>Edit Social Links</h2>
    <form id="socialLinksForm" method="POST" action="update-social-links.php">

      <label for="facebook">Facebook</label>
      <input type="text" id="facebook" name="facebook" value="<?php echo htmlspecialchars($social_links['facebook']); ?>">

      <label for="instagram">Instagram</label>
      <input type="text" id="instagram" name="instagram" value="<?php echo htmlspecialchars($social_links['instagram']); ?>">

      <label for="x-twitter">X</label>
      <input type="text" id="x-twitter" name="x_twitter" value="<?php echo htmlspecialchars($social_links['x_twitter']); ?>">

      <label for="linkedin">LinkedIn</label>
      <input type="text" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($social_links['linkedin']); ?>">

      <!-- Hidden field to pass business ID -->
      <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">

      <button type="submit" class="edit-button">Save Changes</button>
    </form>
  </div>
</div>

<!-- Google Maps Link -->
<div id="googleMapsLinkForm" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('googleMapsLinkForm')">&times;</span>
    <h2>Edit Google Maps Links</h2>
    <form id="googleMapsLinkForm" method="POST" action="update-google-maps.php">
    <label for="google-maps-link">Google Maps Link</label>
      <textarea id="google-maps-link" name="google_maps_link"><?php echo htmlspecialchars($social_links['google_maps_link']); ?></textarea>
      <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">

      <button type="submit" class="edit-button">Save Changes</button>
   </form>
  </div>
</div>


<!-- Terms and Conditions -->
<div id="termsAndConditionsForm" class="terms-modal">
  <div class="terms-modal-content">
    <span class="close" onclick="closeModal('termsAndConditionsForm')">&times;</span>
    <h2>Edit Terms and Conditions</h2>
    <form id="termsAndConditionsForm" method="POST" action="update-terms-and-conditions.php">
      <label for="terms-and-conditions">Terms and Conditions</label>
      <textarea id="terms-and-conditions" name="terms_and_conditions"><?php echo htmlspecialchars($terms_and_conditions); ?></textarea>
      <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">
      <button type="submit" class="edit-button">Save Changes</button>
    </form>
  </div>
</div>

<!--Social Links Modal -->
<div id="editRoomModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal('editRoomModal')">&times;</span>
    <h2>Edit Room</h2>
    <form id="editRoomForm" method="POST" action="update-room.php">
      <input type="hidden" name="room_id" id="room_id">
      <label for="room_type">Room Type:</label>
      <input type="text" id="room_type" name="room_type" required>
      <label for="max_occupancy">Max Occupancy:</label>
      <input type="number" id="max_occupancy" name="max_occupancy" required>
      <label for="price">Price(PHP):</label>
      <input type="number" id="price" name="price" step="0.01" required>
      <label for="total_rooms">Total Rooms:</label>
      <input type="number" id="total_rooms" name="total_rooms" required>
      <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">
      <button type="submit" class="edit-button">Save Changes</button>
    </form>
  </div>
</div>

<div id="addRoomModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addRoomModal')">&times;</span>
        <h2>Add Room</h2>
        <form id="addRoomForm" method="POST" action="add-room.php">
            <label for="room_type">Room Type:</label>
            <input type="text" id="room_type" name="room_type" required>
            <label for="max_occupancy">Max Occupancy:</label>
            <input type="number" id="max_occupancy" name="max_occupancy" required>
            <label for="price">Price(PHP):</label>
            <input type="number" id="price" name="price" step="0.01" required>
            <label for="total_rooms">Total Rooms:</label>
            <input type="number" id="total_rooms" name="total_rooms" required>
            <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">
            <button type="submit" class="edit-button">Add Room</button>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteRoomModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closeModal('deleteRoomModal')">&times;</span>
    <h2>Delete Room</h2>
    <p>Are you sure you want to delete <strong id="deleteRoomType"></strong>?</p>
    <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">
    <button id="confirmDeleteButton" class="delete-button" style="background-color: red;" onclick="confirmDelete()">Yes, Delete</button>
    <button onclick="closeModal('deleteRoomModal')"class="edit-button">Cancel</button>
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
      case 'boats':
            html = `
                <div class="edit-container">
                    <h2>Boats</h2>
                    <p><strong>Boat Price: </strong><?php echo htmlspecialchars($boat_price); ?></p>
                    <p><strong>Environmental Fee: </strong><?php echo htmlspecialchars($island_environmental_fee); ?></p>
                    <button class="edit-button" onclick="showModal('boatPriceModal')">Edit Prices</button>
                </div>`;
            break;
        case 'registration':
            html = `
                <div class="edit-container">
                    <h2>Registration</h2>
                    <p><strong>Registration Fee: </strong><?php echo htmlspecialchars($registration_fee); ?></p>
                    <p><strong>Environmental Fee: </strong><?php echo htmlspecialchars($ecoattraction_environmental_fee); ?></p>
                    <button class="edit-button" onclick="showModal('ecoattractionPriceModal')">Edit Prices</button>
                </div>`;
            break;
        case 'rooms':
            html = `
                <div class="edit-container">
                    <h2>Rooms <button class="add-button" onclick="showModal('addRoomModal')">+</button></h2>
                     <?php if (!empty($rooms)) { ?>
                      <table border="1" style="width: 100%; color: white; border-collapse: collapse;">
                          <thead>
                              <tr>
                                  <th>Room Type</th>
                                  <th>Max Occupancy</th>
                                  <th>Price(PHP)</th>
                                  <th>Total Rooms</th>
                                  <th>Action</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php foreach ($rooms as $room) { ?>
                                  <tr>
                                      <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                      <td><?php echo htmlspecialchars($room['max_occupancy']); ?></td>
                                      <td><?php echo htmlspecialchars($room['price']); ?></td>
                                      <td><?php echo htmlspecialchars($room['total_rooms']); ?></td>
                                      <td>
                                          <button onclick="editRoom(<?php echo $room['id']; ?>)" style="color: green;">
                                              <i class="fa fa-edit"></i>
                                          </button>
                                          <button onclick="deleteRoom(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_type']); ?>')" style="color: red;">
                                              <i class="fa fa-trash"></i>
                                          </button>
                                      </td>
                                  </tr>
                              <?php } ?>
                          </tbody>
                      </table>
                  <?php } else { ?>
                      <p>No rooms available for this business.</p>
                  <?php } ?>
                </div>`;
            break;
        case 'tables':
            html = `
                <div class="edit-container">
                    <h2>Tables</h2>
                    <p><strong>Table Price: </strong><?php echo htmlspecialchars($table_fee); ?></p>
                    <p><strong>Environmental Fee: </strong><?php echo htmlspecialchars($dining_environmental_fee); ?></p>
                    <button class="edit-button" onclick="showModal('tablePriceModal')">Edit Prices</button>
                </div>`;
            break;
      case 'business-hours':
        html = `
          <div class="edit-container">
            <h2>Business Hours</h2>
            <p><i class="fas fa-clock"></i> Opening Time: <?php echo htmlspecialchars($opening_time); ?></p>
            <p><i class="far fa-clock"></i> Closing Time: <?php echo htmlspecialchars($closing_time); ?></p>
            <button class="edit-button" onclick="showModal('businessHoursModal')">Edit</button> 
        </div>`;
        break;
        case 'payment-options':
                      let paymentMethodsHtml = '';
            if (availablePaymentOptions) {
              paymentMethodsHtml += `<p><strong>Cash: </strong>${availablePaymentOptions.cash ? 'Available' : 'Not Available'}</p>`;

                if (availablePaymentOptions.payment_option_1) {
                    paymentMethodsHtml += `<p>${availablePaymentOptions.payment_option_1}</p>`;
                }
                if (availablePaymentOptions.payment_option_2) {
                    paymentMethodsHtml += `<p>${availablePaymentOptions.payment_option_2}</p>`;
                }
                if (availablePaymentOptions.payment_option_3) {
                    paymentMethodsHtml += `<p>${availablePaymentOptions.payment_option_3}</p>`;
                }
            } else {
                paymentMethodsHtml = '<p>No payment options available for this business.</p>';
            }

            html = `
                <div class="edit-container">
                    <h2>Payment Options</h2>
                    <div class="payment-methods">
                        ${paymentMethodsHtml}
                    </div>
                    <button class="edit-button" onclick="showModal('paymentOptionsModal')">Edit Payment Options</button>
                </div>`;
            break;
      case 'contact-info':
        html = `
          <div class="edit-container">
            <h2>Contact Information</h2>
            <p><i class="fas fa-phone"></i> Contact No: <?php echo htmlspecialchars($contact_no); ?></p>
            <p><i class="fas fa-envelope"></i> Email: <?php echo htmlspecialchars($email); ?></p>
            <button class="edit-button" onclick="showModal('contactInfoModal')">Edit</button>
          </div>`;
        break;
      case 'social-links':
        html = `
          <div class="edit-container">
            <h2>Social Links</h2>
            <p><i class="fab fa-facebook"></i> Facebook: <?php echo htmlspecialchars($social_links['facebook']); ?></p>
            <p><i class="fab fa-instagram"></i> Instagram: <?php echo htmlspecialchars($social_links['instagram']); ?></p>
            <p><i class="fa-brands fa-x-twitter"></i> X: <?php echo htmlspecialchars($social_links['x_twitter']); ?></p>
            <p><i class="fab fa-linkedin"></i> LinkedIn: <?php echo htmlspecialchars($social_links['linkedin']); ?></p>
            <button class="edit-button" onclick="showModal('socialLinksModal')">Edit</button>
          </div>`;
        break;
      case 'google-maps-embed':
        html = `
        <div class="edit-container">
          <h2>Google Maps Embed</h2>
          <div class="google-maps-link-container">
          <div class="google-maps-container">
              <?php 
              // Check if google_maps_link exists before displaying it
              if (!empty($social_links['google_maps_link'])) {
                  echo $social_links['google_maps_link'];
              } else {
                  // Optionally, display a placeholder or nothing at all
                  echo "No Google Maps link attached.";
              }
              ?>
            </div>
            <button class="edit-button" onclick="showModal('googleMapsLinkForm')">Edit</button>
          </div>
        </div>`;
        break;
        case 'terms-and-conditions':
    html = `
        <div class="edit-container">
            <h2>Terms and Conditions</h2>
            <p>
                <?php 
                    echo !empty(trim($terms_and_conditions)) 
                        ? nl2br(htmlspecialchars($terms_and_conditions)) 
                        : "1. Agreement to Terms<br>
                        By completing your reservation and payment, you agree to these Terms and Conditions. Please review them carefully.
                        <br>
                        <br>
                        2. Cancellation and Rescheduling Policy<br>
                        Reservations cannot be cancelled or rescheduled through the website.<br>
                        However, if you need to cancel or reschedule, you must contact the store directly using the provided contact information.<br>
                        Refunds or changes may be possible only upon mutual agreement with the store and may incur additional fees.
                        <br>
                        <br>
                        3. Contact Information<br>
                        For any inquiries, cancellations, or rescheduling requests, please contact the store using the details in your booking confirmation.
                        <br>
                        <br>
                        4. Customer Responsibilities<br>
                        Customers must ensure all booking details are accurate.<br>
                        Arrive on time as specified in the booking confirmation.<br>
                        Late arrivals may result in forfeiture of the reservation.
                        <br>
                        <br>
                        5. Store Responsibilities<br>
                        The store is responsible for providing the services as detailed in the booking confirmation.<br>
                        Maintain accurate and accessible contact information for customers.
                        <br>
                        <br>
                        6. Force Majeure<br>
                        The store is not liable for cancellations or changes due to events beyond its control, such as natural disasters or government actions.
                        <br>
                        <br>
                        7. Acceptance of Terms<br>
                        By making a reservation, you acknowledge and accept these Terms and Conditions in full.";
                ?>
            </p>
            <button class="edit-button" onclick="showModal('termsAndConditionsForm')">Edit Terms and Conditions</button>
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

    function formatTimeToAMPM($time) {
    // Convert 24-hour format to 12-hour format
    return date("h:i A", strtotime($time));
}

function editRoom(roomId) {
        // Find the row containing the clicked button
        const tableRow = event.target.closest('tr');
        const roomType = tableRow.cells[0].textContent;
        const maxOccupancy = tableRow.cells[1].textContent;
        const price = tableRow.cells[2].textContent;
        const totalRooms = tableRow.cells[3].textContent;

        // Populate the modal input fields
        document.getElementById('room_id').value = roomId; // Use hidden input to track room ID
        document.getElementById('room_type').value = roomType;
        document.getElementById('max_occupancy').value = maxOccupancy;
        document.getElementById('price').value = price;
        document.getElementById('total_rooms').value = totalRooms;

        // Display the modal
        document.getElementById('editRoomModal').style.display = 'block';
    }

    // Close the modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function deleteRoom(roomId, roomType) {
    // Set room type dynamically in the modal
    document.getElementById('deleteRoomType').textContent = roomType;
    document.getElementById('confirmDeleteButton').setAttribute('data-room-id', roomId);
    document.getElementById('deleteRoomModal').style.display = 'block';
}

function confirmDelete() {
    const roomId = document.getElementById('confirmDeleteButton').getAttribute('data-room-id');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'delete-room.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status === 200) {
            // Close the modal
            closeModal('deleteRoomModal');
            location.reload();
        } else {
            alert('An error occurred while deleting the room.');
        }
    };
    xhr.send('room_id=' + roomId);
}

</script>
</body>
</html>
