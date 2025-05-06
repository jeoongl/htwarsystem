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

// Fetch business details based on the business ID (passed via GET request)
$business_id = isset($_GET['business_id']) ? intval($_GET['business_id']) : null;

if ($business_id) {
    $business_query = "SELECT name, category FROM businesses_tbl WHERE id = ?";
    $business_stmt = $conn->prepare($business_query);
    $business_stmt->bind_param("i", $business_id);
    $business_stmt->execute();
    $business_result = $business_stmt->get_result();
    $business = $business_result->fetch_assoc();

    $category_id = $business['category'] ?? null;
    $business_name = $business['name'] ?? 'Unknown Business';
    $business_stmt->close();

    // Get current date and filter date if set
    $current_date = date('Y-m-d');
    $filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : $current_date;

    // Construct the query based on category
    if ($category_id == 3) {
        $reservation_query = "
        SELECT 
            hr.id, 
            hr.lastname, 
            hr.firstname, 
            hr.check_in, 
            hr.check_out, 
            hr.num_adults, 
            hr.num_children, 
            hr.num_rooms, 
            hr.room_id,
            hr.gender,
            hr.tourist_type,
            hr.phone_number,
            hr.email,
            hr.payment_price,
            hr.proof_of_payment,
            hr.payment_method,
            hr.payment_status,
            hr.created_at,
            r.room_type
        FROM reservations_tbl hr
        JOIN rooms_tbl r ON hr.room_id = r.id
        WHERE hr.business_id = ? AND hr.check_in = ?
        ";

        $reservation_stmt = $conn->prepare($reservation_query);
        $reservation_stmt->bind_param("is", $business_id, $filter_date);
    } else {
        $reservation_query = "
        SELECT 
            id,
            lastname, 
            firstname,
            gender,
            tourist_type,
            num_people, 
            reservation_date,
            reservation_time,
            phone_number,
            email,
            created_at,
            payment_price,
            payment_status,
            payment_method,
            proof_of_payment
        FROM reservations_tbl 
        WHERE business_id = ? AND reservation_date = ?
        ";

        $reservation_stmt = $conn->prepare($reservation_query);
        $reservation_stmt->bind_param("is", $business_id, $filter_date);
    }

    $reservation_stmt->execute();
    $reservation_result = $reservation_stmt->get_result();
} else {
    echo "Business ID is not provided.";
    exit();
}
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
    margin-bottom: -5px; /* Reduce spacing */
    color: white;
}

.business-name {
    font-size: 18px;
    color: white;
    padding: 0 20px; /* Remove vertical padding */
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
      margin: 5% auto;
      padding: 15px 20px 20px 20px;
      border-radius: 5px;
      width: 80%;
      max-width: 400px;
      color: white;
    }
    .people-modal-content {
      background-color: #222;
      margin: 10% auto;
      padding: 15px 20px 20px 20px;
      border-radius: 5px;
      width: 80%;
      max-width: 400px;
      color: white;
    }
    .status-modal-content {
      background-color: #222;
      margin: 15% auto;
      padding: 15px 20px 20px 20px;
      border-radius: 5px;
      width: 80%;
      max-width: 400px;
      color: white;
    }
    .delete-modal-content {
      background-color: #222;
      margin: 15% auto;
      padding: 15px 20px 20px 20px;
      border-radius: 5px;
      width: 80%;
      max-width: 400px;
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
    .modal-content textarea, .modal-content select {
      width: 100%;
      padding: 8px;
      margin-bottom: 15px;
      background-color: #333;
      color: white;
      border: none;
      border-radius: 3px;
    }

  .edit-button {
      margin-top: 10px;
      padding: 8px 12px;
      background-color: green;
      color: white;
      border: none;
      border-radius: 2.5px;
      cursor: pointer;
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
      border-collapse: collapse;
      table-layout: fixed;
      margin-bottom: 20px; /* Added margin to separate tables */
    }

    th, td {
      padding: 12px;
      text-align: left;
      border: 1px solid #444;
      word-wrap: break-word;
    }

    th {
      background-color: #444;
    }
    
    i {
      cursor: pointer;
    }

    .permit-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .permit-list li {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }

    .permit-list img {
      width: 20px; /* Small icon size */
      height: 20px;
      object-fit: cover;
      margin-right: 10px;
      cursor: pointer;
    }

    .permit-list span {
      color: white;
    }
    .reservation-detail {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .reservation-detail label {
        flex-basis: 50%; /* Adjusts the width as needed */
        font-weight: bold;
        display: inline-block;
        vertical-align: middle;
        margin: 0; /* Remove extra margin */
    }

    .reservation-detail span {
        flex-basis: 50%; /* Adjusts the width as needed */
        display: inline-block;
        vertical-align: middle;
        margin: 0; /* Align with the label */
    }

    /* Filter form styles */
    .filter-form {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-right: 20px;
    }

    .filter-form label {
      font-size: 16px;
    }

    .filter-form input[type="date"] {
      padding: 5px;
      font-size: 14px;
      background-color: #333;
      color: white;
      border: 1px solid #444;
      border-radius: 4px;
      width: 200px;
    }

    .filter-form button {
      padding: 8px 10px;
      font-size: 14px;
      background-color: green;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }


    .filter-form button:hover {
      background-color: #218838;
    }
    .address-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
    }

    /* Modal Overlay */
    #proofModal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      z-index: 1000;
    }

    /* Modal Content */
    .photo-modal-content {
      position: relative;
      margin: 3% auto; /* Reduced margin to increase modal height */
      padding: 0;
      width: 80%;
      max-width: 600px;
      background: #222;
      border-radius: 8px;
      text-align: center;
      max-height: 100%; /* Makes the modal larger while keeping some margin */
      overflow-y: auto; /* Adds scroll if content exceeds height */
    }

    /* Close Button */
    .photo-modal-close {
      position: absolute;
      color: white;
      top: 10px;
      right: 15px;
      font-size: 28px;
      cursor: pointer;
    }

    /* Image */
    #proofImage {
      max-width: 100%;
      max-height: 80vh; /* Adjusts based on viewport height for larger images */
    }
    .yes-btn, .no-btn {
        margin: 10px 10px 0 0;
        padding: 10px 30px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    .yes-btn {
      background-color: green;
      color: #fff;
    }
    .btn-cancel, .btn-check {
        margin: 0;
        padding: 0;
        border: none;
        border-radius: 0;
        cursor: pointer;
        background-color: transparent;
    }

    .btn-check {
        font-size: 18px;
        color: green;
    }

    .btn-cancel {
        font-size: 18px;
        color: red;
    }
    .spinner {
    font-size: 16px;
    color: whitesmoke;
    margin-top: 10px;
    text-align: center;
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
       <div class="header">
        <h1>
            <button class="back-button" onclick="goBack()">
                <i class="fa fa-angle-left"></i>
            </button>
            Reservations
        </h1>
        <div class="address-section">
        <p class="business-name"><i class="fas fa-store"></i> <?php echo htmlspecialchars($business['name']); ?></p>
        <div class="filter-form">
        <form method="GET" action="">
          <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($business_id); ?>">
          <label for="filter_date"></label>
          <input type="date" id="filter_date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
          <button type="submit"><i class="fas fa-search"></i> Search</button>
        </form>
      </div>
      </div>
      </div>
      
        <div class="content-wrapper">
        <div class="content">
        <?php if ($reservation_result->num_rows > 0): ?>
        <table>
        <thead>
        <tr>
            <th>Last Name</th>
            <th>First Name</th>
            <?php if ($category_id == 3): ?>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Adults</th>
                <th>Children</th>
                <th>Rooms</th>
                <th>Room Type</th>
            <?php else: ?>
                <?php if (in_array($category_id, [1, 2, 4])): ?>
                    <th>No. of People</th>
                    <th>Reservation Date</th>
                <?php endif; ?>
                <?php if ($category_id == 4): ?>
                    <th>Reservation Time</th>
                <?php endif; ?>
            <?php endif; ?>
            <th>Payment Method</th>
            <th>Proof of Payment</th>
            <th>Reservation Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $reservation_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['lastname']); ?></td>
                <td><?php echo htmlspecialchars($row['firstname']); ?></td>
                <?php if ($category_id == 3): ?>
                    <td><?php echo htmlspecialchars($row['check_in']); ?></td>
                    <td><?php echo htmlspecialchars($row['check_out']); ?></td>
                    <td><?php echo htmlspecialchars($row['num_adults']); ?></td>
                    <td><?php echo htmlspecialchars($row['num_children']); ?></td>
                    <td><?php echo htmlspecialchars($row['num_rooms']); ?></td>
                    <td><?php echo htmlspecialchars($row['room_type']); ?></td>
                <?php else: ?>
                    <?php if (in_array($category_id, [1, 2])): ?>
                      <td><?php echo htmlspecialchars($row['num_people'] ?? 'N/A'); ?>
                          <a href="#" class="view-people" 
                            data-reservation-id="<?php echo $row['id']; ?>" 
                            data-category-id="<?php echo $category_id; ?>">
                            View People
                          </a>
                      </td>
                        <td><?php echo htmlspecialchars($row['reservation_date'] ?? 'N/A'); ?></td>
                    <?php endif; ?>
                    <?php if ($category_id == 4): ?>
                      <td><?php echo htmlspecialchars($row['num_people'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['reservation_date'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['reservation_time'] ?? 'N/A'); ?></td>
                    <?php endif; ?>
                <?php endif; ?>
                <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                <td>  
                <?php if (!empty($row['proof_of_payment'])): ?>
                  <a href="#" class="proof-link" data-src="uploads/payment_photos/<?php echo htmlspecialchars($row['proof_of_payment']); ?>">
                    View Proof
                  </a>
                <?php else: ?>
                  No Proof Uploaded
                <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                <td>
                <?php if ($row['payment_status'] === 'pending'): ?>
                    <button class="btn-check" onclick="showConfirmModal('<?php echo $row['id']; ?>', '<?php echo $row['firstname']; ?>', '<?php echo $row['lastname']; ?>')">
                        <i class="fas fa-check"></i>
                    </button>
                <?php elseif ($row['payment_status'] === 'confirmed'): ?>
                    <button class="btn-cancel" onclick="showCancelModal('<?php echo $row['id']; ?>', '<?php echo $row['firstname']; ?>', '<?php echo $row['lastname']; ?>')">
                        <i class="fas fa-times"></i>
                    </button>
                <?php endif; ?>
                    <i class="fas fa-info-circle" id="inf-circle" onclick="showReservationInfo(
                        '<?php echo htmlspecialchars($row['id']); ?>',
                        '<?php echo htmlspecialchars($row['lastname']); ?>',
                        '<?php echo htmlspecialchars($row['firstname']); ?>',
                        '<?php echo htmlspecialchars($row['gender'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['tourist_type'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['check_in'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['check_out'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['num_adults'] ?? '0'); ?>',
                        '<?php echo htmlspecialchars($row['num_children'] ?? '0'); ?>',
                        '<?php echo htmlspecialchars($row['num_rooms'] ?? '0'); ?>',
                        '<?php echo htmlspecialchars($row['room_type'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['num_people'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['reservation_date'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['reservation_time'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['phone_number'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['created_at'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['payment_price'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['payment_method'] ?? 'N/A'); ?>',
                        '<?php echo htmlspecialchars($row['proof_of_payment'] ?? 'N/A'); ?>',                       
                        '<?php echo htmlspecialchars($row['payment_status'] ?? 'N/A'); ?>'
                    )"></i>
                    <i class="fa-solid fa-file-pdf" id="pdf-btn" onclick="downloadPDF('<?php echo htmlspecialchars($row['id']); ?>')"></i>
                    <i class="fa-solid fa-trash" id="trash-btn" onclick="showDeleteModal('<?php echo $row['id']; ?>','<?php echo $row['firstname']; ?>','<?php echo $row['lastname']; ?>')"></i>

                  </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
      <div class="no-reservations">No Reservations Found for <?php echo htmlspecialchars($filter_date); ?></div>
    <?php endif; ?>
</div>
</div>
        </div>
    </div>
</div>

<div id="peopleModal" class="modal">
    <div class="people-modal-content">
        <span class="close" onclick="closePeopleModal()">&times;</span>
        <h2>People List</h2>
        <ul id="peopleList"></ul>
    </div>
</div>

<!-- Reservation Info Modal -->
<div id="reservationInfoModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Reservation Details</h2>
        <div class="reservation-detail">
            <label>Last Name:</label>
            <span id="modalLastname"></span>
        </div>
        <div class="reservation-detail">
            <label>First Name:</label>
            <span id="modalFirstname"></span>
        </div>
        <div class="reservation-detail">
            <label>Gender:</label>
            <span id="modalGender"></span>
        </div>
        <div class="reservation-detail">
            <label>Tourist Type:</label>
            <span id="modalTouristType"></span>
        </div>
        <div class="reservation-detail">
            <label>Number of People:</label>
            <span id="modalNumPeople"></span>
        </div>
        <div class="reservation-detail">
            <label>Reservation Date:</label>
            <span id="modalReservationDate"></span>
        </div>
        <div class="reservation-detail">
            <label>Reservation Time:</label>
            <span id="modalReservationTime"></span>
        </div>
        <div class="reservation-detail">
            <label>Check-In:</label>
            <span id="modalCheckIn"></span>
        </div>
        <div class="reservation-detail">
            <label>Check-Out:</label>
            <span id="modalCheckOut"></span>
        </div>
        <div class="reservation-detail">
            <label>Adults:</label>
            <span id="modalNumAdults"></span>
        </div>
        <div class="reservation-detail">
            <label>Children:</label>
            <span id="modalNumChildren"></span>
        </div>
        <div class="reservation-detail">
            <label>Rooms:</label>
            <span id="modalNumRooms"></span>
        </div>
        <div class="reservation-detail">
            <label>Room Type:</label>
            <span id="modalRoomType"></span>
        </div>

        <div class="reservation-detail">
            <label>Phone Number:</label>
            <span id="modalPhoneNumber"></span>
        </div>
        <div class="reservation-detail">
            <label>Email:</label>
            <span id="modalEmail"></span>
        </div>
        <div class="reservation-detail">
            <label>Date Reserved:</label>
            <span id="modalDateReserved"></span>
        </div>
        <div class="reservation-detail">
            <label>Payment Price:</label>
            <span id="modalPaymentPrice"></span>
        </div>
        <div class="reservation-detail">
            <label>Payment Method:</label>
            <span id="modalPaymentMethod"></span>
        </div>
        <div class="reservation-detail">
            <label>Proof of Payment:</label>
            <span id="modalProofOfPayment"></span>
        </div>    
        <div class="reservation-detail">
            <label>Reservation Status:</label>
            <span id="modalPaymentStatus"></span>
        </div>
        <div class="reservation-detail">
            <form action="reservation-details.php" method="post" target="_blank">
                <input type="hidden" id="pdfReservationId" name="reservation_id">
                <button type="submit" class="edit-button">View in PDF</button>
            </form>
        </div>
    </div>
</div>


<!-- Modal for Proof of Payment -->
<div id="proofModal">
  <div class="photo-modal-content">
    <span id="closeModal" class="photo-modal-close">&times;</span>
    <img id="proofImage" src="" alt="Proof of Payment" />
  </div>
</div>

<div id="confirmModal" class="modal">
    <div class="status-modal-content">
        <span class="close" onclick="closeConfirmModal()">&times;</span>
        <h2>Confirm Reservation</h2>
        <p id="confirmText"></p>
        <div id="loadingSpinner" class="spinner" style="display: none;">Loading...</div> <!-- Spinner -->
        <div id="confirmButtons">
            <button class="yes-btn" onclick="confirmReservation()">Yes</button>
            <button class="no-btn" onclick="closeConfirmModal()">No</button>
        </div>
    </div>
</div>

<div id="cancelModal" class="modal">
    <div class="status-modal-content">
        <span class="close" onclick="closeCancelModal()">&times;</span>
        <h2>Cancel Reservation</h2>
        <p id="cancelText"></p>
        <div id="loadingSpinnerCancel" class="spinner" style="display: none;">Loading...</div> <!-- Spinner -->
        <div id="cancelButtons">
            <button class="yes-btn" onclick="cancelReservation()">Yes</button>
            <button class="no-btn" onclick="closeCancelModal()">No</button>
        </div>
    </div>
</div>


<!-- Delete Modal -->
<div id="deleteModal" class="modal">
    <div class="status-modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h2>Delete Reservation</h2>
        <p id="deleteText"></p>
        <button class="yes-btn" onclick="deleteReservation()">Yes</button>
        <button class="no-btn" onclick="closeDeleteModal()">No</button>
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

function showReservationInfo(
    id, lastname, firstname, gender, touristType, checkIn, checkOut, numAdults, numChildren, numRooms, roomType, 
    numPeople, reservationDate, reservationTime, phoneNumber, email, dateReserved, paymentPrice, paymentMethod, 
    proofOfPayment, paymentStatus
) {
    // Set default values
    document.getElementById('pdfReservationId').value = id;
    document.getElementById('modalLastname').textContent = lastname || 'N/A';
    document.getElementById('modalFirstname').textContent = firstname || 'N/A';
    document.getElementById('modalGender').textContent = gender || 'N/A';
    document.getElementById('modalTouristType').textContent = touristType || 'N/A';
    document.getElementById('modalCheckIn').textContent = checkIn;
    document.getElementById('modalCheckOut').textContent = checkOut;
    document.getElementById('modalNumAdults').textContent = numAdults;
    document.getElementById('modalNumChildren').textContent = numChildren;
    document.getElementById('modalNumRooms').textContent = numRooms;
    document.getElementById('modalRoomType').textContent = roomType;
    document.getElementById('modalNumPeople').textContent = numPeople || 'N/A';
    document.getElementById('modalReservationDate').textContent = reservationDate || 'N/A';
    document.getElementById('modalReservationTime').textContent = reservationTime || 'N/A';
    document.getElementById('modalPhoneNumber').textContent = phoneNumber || 'N/A';
    document.getElementById('modalEmail').textContent = email || 'N/A';
    document.getElementById('modalDateReserved').textContent = dateReserved || 'N/A';
    document.getElementById('modalPaymentPrice').textContent = paymentPrice || 'N/A';
    document.getElementById('modalPaymentMethod').textContent = paymentMethod || 'N/A';
    document.getElementById('modalProofOfPayment').textContent = proofOfPayment || 'N/A';
    document.getElementById('modalPaymentStatus').textContent = paymentStatus || 'N/A';


    // Determine visibility based on category
    const categoryId = <?php echo $category_id; ?>;

    // Hide all fields initially
    document.querySelectorAll('.reservation-detail').forEach(detail => {
        detail.style.display = 'none';
    });


    document.getElementById('modalLastname').closest('.reservation-detail').style.display = 'block';
    document.getElementById('modalFirstname').closest('.reservation-detail').style.display = 'block';
    document.getElementById('modalPhoneNumber').closest('.reservation-detail').style.display = 'block';
    document.getElementById('modalEmail').closest('.reservation-detail').style.display = 'block';
    document.getElementById('modalDateReserved').closest('.reservation-detail').style.display = 'block';
    document.getElementById('modalPaymentPrice').closest('.reservation-detail').style.display = 'block';

    // Category-specific fields
    if ([1, 2].includes(categoryId)) {
        document.getElementById('modalNumPeople').closest('.reservation-detail').style.display = 'block';
        document.getElementById('modalReservationDate').closest('.reservation-detail').style.display = 'block';
    }
    if (categoryId === 4) {
        document.getElementById('modalNumPeople').closest('.reservation-detail').style.display = 'block';
        document.getElementById('modalReservationDate').closest('.reservation-detail').style.display = 'block';
        document.getElementById('modalReservationTime').closest('.reservation-detail').style.display = 'block';
    }
    if (categoryId === 3) {
        // Replace these IDs with actual ones for category 3 fields
        document.getElementById('modalCheckIn').closest('.reservation-detail').style.display = 'block';
        document.getElementById('modalCheckOut').closest('.reservation-detail').style.display = 'block';
        document.getElementById('modalNumAdults').closest('.reservation-detail').style.display = 'block';
        document.getElementById('modalNumChildren').closest('.reservation-detail').style.display = 'block';
        document.getElementById('modalNumRooms').closest('.reservation-detail').style.display = 'block';
        document.getElementById('modalRoomType').closest('.reservation-detail').style.display = 'block';
    }
    document.getElementById('modalPaymentMethod').closest('.reservation-detail').style.display = 'block';
    document.getElementById('modalProofOfPayment').closest('.reservation-detail').style.display = 'block';
    document.getElementById('modalPaymentStatus').closest('.reservation-detail').style.display = 'block';
    document.getElementById('pdfReservationId').closest('.reservation-detail').style.display = 'block';
    // Open the modal
    document.getElementById('reservationInfoModal').style.display = 'block';
}


function closeModal() {
    document.getElementById('reservationInfoModal').style.display = 'none';
}

function downloadPDF(reservationId) {
    // Set the reservation ID for the form submission
    const pdfForm = document.createElement('form');
    pdfForm.action = 'reservation-details.php';
    pdfForm.method = 'post';
    pdfForm.target = '_blank';

    // Create a hidden input for the reservation ID
    const reservationInput = document.createElement('input');
    reservationInput.type = 'hidden';
    reservationInput.name = 'reservation_id';
    reservationInput.value = reservationId;

    // Append the input to the form
    pdfForm.appendChild(reservationInput);

    // Add the form to the document and submit it
    document.body.appendChild(pdfForm);
    pdfForm.submit();

    // Clean up the dynamically added form
    document.body.removeChild(pdfForm);
}


document.querySelectorAll('.view-people').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const reservationId = this.dataset.reservationId;
        const categoryId = this.dataset.categoryId;

        fetch(`view-names.php?reservation_id=${reservationId}&category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const peopleList = document.getElementById('peopleList');
                    peopleList.innerHTML = ''; // Clear the list
                    data.people.forEach(person => {
                        const li = document.createElement('li');
                        li.textContent = person;
                        peopleList.appendChild(li);
                    });
                    document.getElementById('peopleModal').style.display = 'block';
                } else {
                    alert(data.message || 'Unable to fetch people.');
                }
            })
            .catch(error => console.error('Error:', error));
    });
});


function closePeopleModal() {
    document.getElementById('peopleModal').style.display = 'none';
}


  function goBack() {
    window.history.back();
  }

  document.addEventListener('DOMContentLoaded', () => {
    const proofLinks = document.querySelectorAll('.proof-link');
    const modal = document.getElementById('proofModal');
    const proofImage = document.getElementById('proofImage');
    const closeModal = document.getElementById('closeModal');

    proofLinks.forEach(link => {
      link.addEventListener('click', (event) => {
        event.preventDefault();
        const proofSrc = event.target.getAttribute('data-src');
        proofImage.src = proofSrc;
        modal.style.display = 'block';
      });
    });

    closeModal.addEventListener('click', () => {
      modal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    });
  });

  let reservationId;

function showConfirmModal(id, firstName, lastName) {
    reservationId = id;
    document.getElementById('confirmText').textContent = `Do you want to confirm the reservation created by ${firstName} ${lastName}?`;
    document.getElementById('confirmModal').style.display = 'block';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

function showCancelModal(id, firstName, lastName) {
    reservationId = id;
    document.getElementById('cancelText').textContent = `Do you want to cancel the reservation created by ${firstName} ${lastName}?`;
    document.getElementById('cancelModal').style.display = 'block';
}

function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
}

function confirmReservation() {
    updateReservationStatus(reservationId, 'confirmed');
    closeConfirmModal();
}

function cancelReservation() {
    updateReservationStatus(reservationId, 'pending');
    closeCancelModal();
}

function confirmReservation() {
    toggleLoadingSpinner(true, 'confirm');
    updateReservationStatus(reservationId, 'confirmed', function () {
        toggleLoadingSpinner(false, 'confirm');
        closeConfirmModal();
    });
}

function cancelReservation() {
    toggleLoadingSpinner(true, 'cancel');
    updateReservationStatus(reservationId, 'pending', function () {
        toggleLoadingSpinner(false, 'cancel');
        closeCancelModal();
    });
}

function toggleLoadingSpinner(show, type) {
    const spinner = type === 'confirm' ? document.getElementById('loadingSpinner') : document.getElementById('loadingSpinnerCancel');
    const buttons = type === 'confirm' ? document.getElementById('confirmButtons') : document.getElementById('cancelButtons');

    if (show) {
        spinner.style.display = 'block';
        buttons.style.display = 'none';
    } else {
        spinner.style.display = 'none';
        buttons.style.display = 'block';
    }
}

function updateReservationStatus(id, status, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'update-reservation-status.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status === 200) {
            callback();
            location.reload(); // Reload page after success
        } else {
            callback();
            alert('Error updating reservation status.');
        }
    };
    xhr.send(`id=${id}&status=${status}`);
}

let deleteReservationId;

function showDeleteModal(id, firstname, lastname) {
    deleteReservationId = id;
    document.getElementById("deleteText").textContent = 
        `Are you sure you want to delete the reservation for ${firstname} ${lastname}?`;
    document.getElementById("deleteModal").style.display = "block";
}

function closeDeleteModal() {
    document.getElementById("deleteModal").style.display = "none";
    deleteReservationId = null;
}

function deleteReservation() {
    if (!deleteReservationId) return;

    fetch('delete-reservation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reservation_id: deleteReservationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            location.reload(); // Refresh the page to show updated reservations
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the reservation.');
    });

    closeDeleteModal();
}

</script>
</body> 
</html>
