<?php
// Start session and include database connection
session_start();
include 'includes/dbconnection.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

    // Fetch categories including category_id
    $categories_query = "SELECT id, category_name, thumbnail FROM categories_tbl";
    $categories_stmt = $conn->prepare($categories_query);
    $categories_stmt->execute();
    $categories_result = $categories_stmt->get_result();

    // Fetch contact information and social media links
    $contact_query = "SELECT contact_no, email, facebook, instagram, x_twitter, linkedin FROM hinunangan_info_tbl LIMIT 1";
    $contact_result = $conn->query($contact_query);
    $contact_info = $contact_result->fetch_assoc();

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

// Get category ID from the URL parameter
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_category_3 = ($category_id === 3);

$businesses = null;


if ($is_category_3) {
  if (!empty($_GET['check_in']) && !empty($_GET['check_out']) && !empty($_GET['adults']) && !empty($_GET['rooms'])) {
      $check_in = $_GET['check_in'];
      $check_out = $_GET['check_out'];
      $adults = intval($_GET['adults']);
      $children = intval($_GET['children'] ?? 0);
      $rooms_requested = intval($_GET['rooms']);
      $total_guests = $adults + $children;

      // Query for available businesses with required room conditions
      $query = "
      SELECT 
          b.id, 
          b.name, 
          b.description, 
          b.address, 
          b.business_profile_photo,
          (SUM(r.total_rooms) - COALESCE(SUM(hr.num_rooms), 0)) AS total_available_rooms
      FROM businesses_tbl b
      JOIN rooms_tbl r ON b.id = r.business_id
      LEFT JOIN reservations_tbl hr 
          ON r.id = hr.room_id
          AND NOT (
              hr.check_out <= ? OR hr.check_in >= ?
          )
      WHERE b.category = 3
        AND b.business_status = 2
        AND r.max_occupancy >= ?
      GROUP BY b.id, b.name, b.description, b.address, b.business_profile_photo
      HAVING total_available_rooms >= ?
      ORDER BY b.name;
      ";

      // Prepare the SQL statement
      $stmt = $conn->prepare($query);

      // Bind parameters to the statement
      $stmt->bind_param("ssii", 
          $check_in,       // Bind to first '?' (hr.check_in < ?)
          $check_out,      // Bind to second '?' (hr.check_out > ?)
          $total_guests,   // Bind to third '?' (r.max_occupancy >= ?)
          $rooms_requested // Bind to fourth '?' (HAVING available_rooms >= ?)
      );

      // Execute the statement
      $stmt->execute();

      // Fetch the results
      $businesses = $stmt->get_result();
      $stmt->close();
  }

} else {
  $allowed_categories = [1, 2, 4];
  if (in_array($category_id, $allowed_categories)) {
      $query = "SELECT name, description, address, business_profile_photo, id 
                FROM businesses_tbl 
                WHERE category = ? AND business_status = 2";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("i", $category_id);
      $stmt->execute();
      $businesses = $stmt->get_result();
      $stmt->close();
  } else {
      header('Location: index.php');
      exit();
  }
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Places of Interest</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <style>
    body {
      font-family: Helvetica;
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
    .login-btn, .contact-btn {
      background-color: transparent;
      border: none;
      font-size: 16px;
      cursor: pointer;
      color: white;
    }
    .login-btn {
      order: 1;
      margin-left: auto;
    }
    .contact-btn {
      order: 2;
    }
    #title {
      font-family: Helvetica;
      font-size: 50px;
      text-align: left;
      padding-left: 8%;
      margin: 50px 0;
    }
    #sub-title {
      font-family: Helvetica;
      font-size: 30px;
      font-weight: lighter;
    }
    .all-tourist-sites {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(20%, 1fr));
      grid-gap: 20px;
      justify-content: center;
      padding: 10% 2%;
    }
    .container {
      text-align: left;
      position: relative;
    }
    .container img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;
    }
    .details {
      background-color: white;
      padding: 0 0;
      padding-top: 13px;
      border-bottom-left-radius: 10px;
      border-bottom-right-radius: 10px;
      margin-top: -4px;
    }
    .description {
      color: #333;
      margin: 0;
      padding: 0 5%;
    }
    .details h4 {
      color: #333;
      margin: 0;
      padding: 0 5% 0 5%;
      font-size: 20px;
      white-space: nowrap;        /* Prevents text from wrapping to the next line */
      overflow: hidden;           /* Hides overflowing content */
      text-overflow: ellipsis;    /* Adds '...' at the end of truncated content */
    }

    .address {
      font-size: 15px;
      font-style: italic;
      display: flex;
      align-items: center;
      color: #333;
      margin: 0;
      padding: 0 5% 0 5%;
      padding-top: 10px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;

    }
    .address p {
      font-size: 15px;
      font-style: italic;
      color: #333;
      margin: 0; /* Remove bottom margin */
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .description {
      color: #333;
      margin: 0;
      padding: 0 5%; /* Remove padding at the bottom */
    }

    .description p {
      min-height: 3.5em; /* Ensures that the description box spans about 3 lines */
      overflow: hidden; /* Prevents content overflow */
      display: -webkit-box; /* Flexbox for truncating text */
      -webkit-line-clamp: 3; /* Limits the description to 3 lines */
      -webkit-box-orient: vertical;
      text-overflow: ellipsis;
    }

    .address i {
      margin-right: 5px;
    }
    .details h4 {
      font-size: 20px;
    }
    .buttons-wrapper {
      overflow: hidden;
      position: relative;
      height: auto;
      padding-top: 0;
      padding: 0 5%;
      margin-top: 0; /* Adjust this to reduce space */
    }

    .buttons {
      margin-top: 0;
      padding-top: 0;
      display: flex;
      justify-content: space-between;
      margin-bottom: 15px;
    }
    .visit-btn, .view-btn {
      background-color: green;
      color: white;
      border: none;
      padding: 5px 10px;
      cursor: pointer;
      height: 30px;
      width: calc(100% - 5px);
      margin-right: 15px;
      border-radius: 10px;
      box-shadow: 0px 6px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s, box-shadow 0.2s;
      text-align: center;
      line-height: 30px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .view-btn {
      background-color: #4f4f4f;
      color: white;
    }
    .visit-btn:hover,
    .view-btn:hover {
      transform: scale(1.05);
      box-shadow: 0px 6px 8px rgba(0, 0, 0, 0.1);
    }
    .visit-btn:last-child,
    .view-btn:last-child {
      margin-right: 0;
    }
    .booking-form {
      margin: 5% auto auto auto; /* Centers the form */
      padding:  20px;
      background-color: #444;
      border-radius: 10px;
      width: 60%; /* Adjust the width as needed to make the form smaller */
      box-sizing: border-box;
  }


    .booking-form .form-row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 15px;
        width: 97.5%;
    }

    .booking-form .form-group {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .booking-form input {
        width: 100%;
        padding: 8px;
        border-radius: 5px;
        border: none;
        outline: none;
        font-size: 14px;
        background-color: #333;
        color: white;
        border: 1px solid #555;
    }

    .booking-form button {
        background-color: green;
        color: white;
        padding: 10px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        width: 100%; 
    }
    .image-container {
    position: relative;
    width: 100%;
    height: auto;
}

.available-rooms-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: red;
    color: white;
    font-weight: bold;
    font-size: 14px;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.decorative-line {
      margin: 0; /* Remove margin */
      padding: 0; /* Remove padding */
      display: block; /* Ensure block-level behavior */
      background-color: red;
      object-fit: cover;
      background-position: center;
      height: 45px; /* Fixed height */
      width: 100%;
      max-width: 100%;
    }
    footer {
        background-color: #222;
        color: white;
        padding: 20px 0;
        text-align: center;
    }

    .footer-container {
        display: flex;
        justify-content: space-around;
        flex-wrap: wrap;
        padding: 0 20px;
    }

    .footer-column {
        flex: 1;
        min-width: 200px;
        margin-bottom: 20px;
    }

    .footer-column img {
        width: 100px;
        margin-bottom: 10px;
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
  
    @media only screen and (max-width: 767px) {
    .decorative-line {
      margin: 0; /* Remove margin */
      padding: 0; /* Remove padding */
      display: block; /* Ensure block-level behavior */
      background-color: red;
      object-fit: cover;
      background-position: center;
      height: 45px; /* Fixed height */
      width: 100%;
      max-width: 100%;
    }}


    @media only screen and (max-width: 767px) {

    footer {
        background-color: #222;
        color: white;
        padding: 20px 0;
        text-align: center;
    }

    .footer-container {
        display: flex;
        justify-content: space-around;
        flex-wrap: wrap;
        padding: 0 20px;
    }

    .footer-column {
        flex: 1;
        min-width: 200px;
        margin-bottom: 20px;
    }

    .footer-column img {
        width: 100px;
        margin-bottom: 10px;
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
    }}

    @media only screen and (max-width: 767px) {
      .all-tourist-sites {
        grid-template-columns: repeat(1, 1fr);
        padding: 10% 8%;
      }
      .container img {
        height: 200px;
      }

      .logo {
        width: 250px;
      }
      .profile-icon {
        font-size: 24px;
      }
      .booking-form {
      margin:  auto; /* Centers the form */
      padding: 20px;
      background-color: #444;
      border-radius: 10px;
      width: 93%; /* Adjust the width as needed to make the form smaller */
      box-sizing: border-box;
  }
    }
  </style>
</head>
<body>

<header>
    <img class="logo" src="img/logo.png" alt="Logo">
    <button class="login-btn" onclick="location.href='log-in.php'">Login</button>
  </header>

  <?php if ($is_category_3): ?>
    <div class="booking-form">
    <form method="GET" action="">
        <input type="hidden" name="id" value="3">
            <?php
            // Get current date and the next day's date
            $current_date = date('Y-m-d');
            $next_date = date('Y-m-d', strtotime('+1 day'));
            ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="check-in">Check-in Date:</label>
                    <input type="date" id="check-in" name="check_in" value="<?php echo htmlspecialchars($_GET['check_in'] ?? $current_date); ?>" required>
                </div>
                <div class="form-group">
                    <label for="check-out">Check-out Date:</label>
                    <input type="date" id="check-out" name="check_out" value="<?php echo htmlspecialchars($_GET['check_out'] ?? $next_date); ?>" required>
                </div>
            </div>
        
        <!-- Second row: Adults, Children, and Rooms -->
        <div class="form-row">
            <div class="form-group">
                <label for="adults">Adults:</label>
                <input type="number" id="adults" name="adults" min="1" value="<?php echo htmlspecialchars($_GET['adults'] ?? 1); ?>" required>
            </div>
            <div class="form-group">
                <label for="children">Children:</label>
                <input type="number" id="children" name="children" min="0" value="<?php echo htmlspecialchars($_GET['children'] ?? 0); ?>">
            </div>
            <div class="form-group">
                <label for="rooms">Rooms:</label>
                <input type="number" id="rooms" name="rooms" min="1" value="<?php echo htmlspecialchars($_GET['rooms'] ?? 1); ?>" required>
            </div>
        </div>
        
            <button type="submit">Search</button>

    </form>
</div>
    <?php endif; ?>

<?php if ($businesses): ?>
<div class="all-tourist-sites">
    <?php while ($business = $businesses->fetch_assoc()): ?>
<div class="container">
<div class="image-container">
        <img src="<?php echo !empty($business['business_profile_photo']) ? htmlspecialchars($business['business_profile_photo']) : 'img/default-store-profile.jpg'; ?>" alt="Business Image">
        <?php if ($is_category_3 && isset($business['total_available_rooms'])): ?>
            <div class="available-rooms-badge">
                <?php echo $business['total_available_rooms']; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="details">
    <h4>
        <?php
        // Check if the business name is too long and truncate if needed
        $business_name = htmlspecialchars($business['name']);
        if (strlen($business_name) > 40) {
            echo substr($business_name, 0, 20) . '...';
        } else {
            echo $business_name;
        }
        ?>
    </h4>
    <div class="address">
            <i class="fas fa-map-marker-alt"></i>
            <p>
                <?php
                // Check if the address is available and display it
                if (!empty($business['address'])) {
                    echo htmlspecialchars($business['address']);
                } else {
                    echo "Address not available.";
                }
                ?>
            </p>
        </div>
    <div class="description">
        <p>
            <?php 
            // Check if the description is empty or null
            $description = htmlspecialchars($business['description']);
            if (empty($description)) {
                // Display a default message if no description is provided
                echo "No description available.";
            } else {
                // Check if the description length exceeds 75 characters and truncate if needed
                if (strlen($description) > 75) {
                    echo substr($description, 0, 75) . '...';
                } else {
                    echo $description;
                }
            }
            ?>
        </p>
    </div>
        <div class="buttons-wrapper">
            <div class="buttons">
                <?php if (!$is_category_3): ?>
                <a href="store-profile.php?business_id=<?php echo $business['id']; ?>" class="view-btn">Visit</a>
                <?php endif; ?>
                 <?php if ($is_category_3): ?>
                <a href="store-profile.php?business_id=<?php echo $business['id']; ?>&check_in=<?php echo urlencode($check_in); ?>&check_out=<?php echo urlencode($check_out); ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>&rooms=<?php echo $rooms_requested; ?>" class="view-btn">Visit</a>
                <?php endif; ?>
              </div>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>
<?php endif; ?>

<div class="floating-home" onclick="location.href='index.php';">
  <i class="fas fa-home"></i>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

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

  flatpickr("#check-in", {
      dateFormat: "Y-m-d"
  });
  flatpickr("#check-out", {
      dateFormat: "Y-m-d"
  });
  </script>
  
</body>
</html>
