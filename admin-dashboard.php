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
$stmt->close();

// Helper function to get data for a specific timeframe
function fetchReservationData($conn, $timeCondition) {
  $query = "
      SELECT 
          COUNT(*) AS total,
          SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) AS male,
          SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) AS female,
          SUM(CASE WHEN tourist_type = 'local' THEN 1 ELSE 0 END) AS local,
          SUM(CASE WHEN tourist_type = 'non-hinunangnon' THEN 1 ELSE 0 END) AS non_hinunangnon,
          SUM(CASE WHEN tourist_type = 'foreign' THEN 1 ELSE 0 END) AS `foreign`
      FROM reservations_tbl r
      INNER JOIN businesses_tbl b ON r.business_id = b.id
      WHERE r.payment_status = 'confirmed'
      AND b.business_status = 2
      AND ($timeCondition)";
  
  $stmt = $conn->prepare($query);
  if (!$stmt) {
      die("Query preparation failed: " . $conn->error);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_assoc();
}

// Define time conditions for queries
$todayCondition = "(DATE(reservation_date) = CURDATE() OR DATE(check_in) = CURDATE())";
$weekCondition = "(YEARWEEK(reservation_date, 1) = YEARWEEK(CURDATE(), 1) OR YEARWEEK(check_in, 1) = YEARWEEK(CURDATE(), 1))";
$monthCondition = "(YEAR(reservation_date) = YEAR(CURDATE()) AND MONTH(reservation_date) = MONTH(CURDATE())) OR (YEAR(check_in) = YEAR(CURDATE()) AND MONTH(check_in) = MONTH(CURDATE()))";
$yearCondition = "(YEAR(reservation_date) = YEAR(CURDATE()) OR YEAR(check_in) = YEAR(CURDATE()))";

// Fetch data for each timeframe
$dataToday = fetchReservationData($conn, $todayCondition);
$dataWeek = fetchReservationData($conn, $weekCondition);
$dataMonth = fetchReservationData($conn, $monthCondition);
$dataYear = fetchReservationData($conn, $yearCondition);

// Fetch the top 5 most visited places for each category
function fetchTopPlaces($conn, $categories, $limit = 5) {
  $placeholders = implode(',', array_fill(0, count($categories), '?'));
  $query = "
      SELECT 
          b.name AS name, 
          COUNT(r.id) AS reservation_count
      FROM reservations_tbl r
      INNER JOIN businesses_tbl b ON r.business_id = b.id
      WHERE b.category IN ($placeholders) AND r.payment_status = 'confirmed' AND b.business_status = 2
      GROUP BY r.business_id
      ORDER BY reservation_count DESC
      LIMIT ?";
  
  $stmt = $conn->prepare($query);
  if (!$stmt) {
      die("Query preparation failed: " . $conn->error);
  }
  
  $params = array_merge($categories, [$limit]);
  $stmt->bind_param(str_repeat('i', count($categories)) . 'i', ...$params);
  $stmt->execute();
  $result = $stmt->get_result();
  
  $places = [];
  while ($row = $result->fetch_assoc()) {
      $places[] = [
          'name' => $row['name'],
          'count' => $row['reservation_count']
      ];
  }
  return $places;
}

// Define categories for each section
$ecoAttractionsCategories = [1, 2];
$hotelCategories = [3];
$diningCategories = [4];

// Fetch data for each category
$ecoAttractionsTop5 = fetchTopPlaces($conn, $ecoAttractionsCategories);
$hotelsTop5 = fetchTopPlaces($conn, $hotelCategories);
$diningTop5 = fetchTopPlaces($conn, $diningCategories);

// Fetch the most visited day for eco-attractions
function fetchMostVisitedDayForEcoAttractions($conn, $categories) {
  $placeholders = implode(',', array_fill(0, count($categories), '?'));
  $query = "
      SELECT 
          DAYNAME(reservation_date) AS day,
          COUNT(*) AS visit_count
      FROM reservations_tbl r
      INNER JOIN businesses_tbl b ON r.business_id = b.id
      WHERE b.category IN ($placeholders) 
      AND r.payment_status = 'confirmed' 
      AND b.business_status = 2
      GROUP BY DAYNAME(reservation_date)
      ORDER BY visit_count DESC
      LIMIT 1";

  $stmt = $conn->prepare($query);
  if (!$stmt) {
      die("Query preparation failed: " . $conn->error);
  }

  $stmt->bind_param(str_repeat('i', count($categories)), ...$categories);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  return $row ? $row['day'] : 'N/A';
}

// Define categories for eco-attractions
$ecoAttractionsCategories = [1, 2];

// Fetch the most visited day for eco-attractions
$mostVisitedDayEcoAttractions = fetchMostVisitedDayForEcoAttractions($conn, $ecoAttractionsCategories);

// Fetch the most visited time for dining
function fetchMostVisitedTimeForDining($conn, $category) {
  $query = "
      SELECT 
          TIME_FORMAT(reservation_time, '%h:%i %p') AS visit_time,
          COUNT(*) AS visit_count
      FROM reservations_tbl r
      INNER JOIN businesses_tbl b ON r.business_id = b.id
      WHERE b.category = ? 
      AND r.payment_status = 'confirmed' 
      AND b.business_status = 2
      GROUP BY reservation_time
      ORDER BY visit_count DESC
      LIMIT 1";

  $stmt = $conn->prepare($query);
  if (!$stmt) {
      die("Query preparation failed: " . $conn->error);
  }

  $stmt->bind_param('i', $category);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  return $row ? $row['visit_time'] : 'N/A';
}

// Fetch the average days of stay for hotels
function fetchAverageDaysOfStay($conn, $category) {
  $query = "
      SELECT 
          AVG(DATEDIFF(check_out, check_in)) AS average_days
      FROM reservations_tbl r
      INNER JOIN businesses_tbl b ON r.business_id = b.id
      WHERE b.category = ? 
      AND r.payment_status = 'confirmed' 
      AND b.business_status = 2";

  $stmt = $conn->prepare($query);
  if (!$stmt) {
      die("Query preparation failed: " . $conn->error);
  }

  $stmt->bind_param('i', $category);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  return $row ? round($row['average_days'], 1) : 0;
}

// Define categories for hotels and dining
$hotelCategory = 3; // Hotels
$diningCategory = 4; // Dining

// Fetch the most visited time for dining
$mostVisitedTimeDining = fetchMostVisitedTimeForDining($conn, $diningCategory);

// Fetch the average days of stay for hotels
$averageDaysOfStayHotels = fetchAverageDaysOfStay($conn, $hotelCategory);


?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    body {
      font-family: Helvetica, Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: black;
      color: white;
    }

    header {
      background-color: black;
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
            width: 180px;
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


    .dashboard-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 90%;
      margin: 20px auto;
      background-color: #333;
      padding: 20px;
      border-radius: 8px;
      position: relative;
    }

    .dashboard-title {
      position: absolute;
      top: 20px;
      left: 20px;
      font-size: 30px;
      font-weight: bold;
    }

    .visitor-stats {
      display: flex;
      justify-content: space-around;
      width: 100%;
      margin-bottom: 20px;
      margin-top: 60px;
    }

    .stat-box {
      background-color: #444;
      padding: 20px;
      border-radius: 8px;
      text-align: center;
      width: 23%;
    }

    .stat-box h3 {
      margin: 0;
      margin-bottom: 10px;
    }

    .stat-box p {
      font-size: 35px;
      margin: 0;
    }

    .spot-categories {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        margin-top: 20px;
        width: 100%;
    }
    .tourist-spots {
      width: 100%;
    }

    /* Individual category styling */
    .category {
        background-color: #444;
        padding: 0 20px 20px 20px;
        border-radius: 8px;
        width: 30%;
    }

    /* List and list items styling */
    .category h3 {
        margin-bottom: 15px;
        font-size: 20px;
    }

    .spot-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .spot-list li {
        background-color: #555;
        padding: 10px;
        margin-bottom: 8px;
        border-radius: 5px;
    }
    .generate-report-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 10px 15px;
        background-color: #d9534f;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .generate-report-btn:hover {
        background-color: #c9302c;
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
        <a href="admin-dashboard.php"><i class="fa fa-bar-chart"></i>Dashboard</a>
        <a href="businesses.php"><i class="fas fa-users"></i>Businesses</a>
        <a href="admin-settings.php"><i class="fas fa-cogs"></i>Settings</a>
        <a href="index.php"><i class="fas fa-sign-out-alt"></i>Log out</a>
      </div>
    </div>
</header>

    <div class="dashboard-container">
    <div class="dashboard-title">Dashboard</div>
    <a href="generate-dashboard-report.php" target="_blank" class="generate-report-btn">
      <i class="fas fa-file-pdf"></i> Generate Report
    </a>
    <div class="visitor-stats">
      <div class="stat-box">
        <h3>Visitors Today</h3>
        <p><?php echo $dataToday['total']; ?></p>
        <small>Male: <?php echo $dataToday['male']; ?>, Female: <?php echo $dataToday['female']; ?>, Local: <?php echo $dataToday['local']; ?>, Non-Hinunangon: <?php echo $dataToday['non_hinunangnon']; ?>, Foreign: <?php echo $dataToday['foreign']; ?></small>
      </div>
      <div class="stat-box">
        <h3>Visitors this Week</h3>
        <p><?php echo $dataWeek['total']; ?></p>
        <small>Male: <?php echo $dataWeek['male']; ?>, Female: <?php echo $dataWeek['female']; ?>, Local: <?php echo $dataWeek['local']; ?>, Non-Hinunangon: <?php echo $dataWeek['non_hinunangnon']; ?>, Foreign: <?php echo $dataWeek['foreign']; ?></small>
      </div>
      <div class="stat-box">
        <h3>Visitors this Month</h3>
        <p><?php echo $dataMonth['total']; ?></p>
        <small>Male: <?php echo $dataMonth['male']; ?>, Female: <?php echo $dataMonth['female']; ?>, Local: <?php echo $dataMonth['local']; ?>, Non-Hinunangon: <?php echo $dataMonth['non_hinunangnon']; ?>, Foreign: <?php echo $dataMonth['foreign']; ?></small>
      </div>
      <div class="stat-box">
        <h3>Visitors this Year</h3>
        <p><?php echo $dataYear['total']; ?></p>
        <small>Male: <?php echo $dataYear['male']; ?>, Female: <?php echo $dataYear['female']; ?>, Local: <?php echo $dataYear['local']; ?>, Non-Hinunangon: <?php echo $dataYear['non_hinunangnon']; ?>, Foreign: <?php echo $dataYear['foreign']; ?></small>
      </div>
    </div>
    <div class="tourist-spots">
    <h2>Most Visited Places</h2>
    <div class="spot-categories">
        <div class="category eco-attractions">
            <h3>Eco-Attractions</h3>
            <p>Most visited day: <?php echo $mostVisitedDayEcoAttractions; ?></p>
            <h4>Top 5</h4>
            <ul class="spot-list">
            <?php foreach ($ecoAttractionsTop5 as $spot): ?>
                <li><?= htmlspecialchars($spot['name']) ?> - <?= $spot['count'] ?> reservations</li>
            <?php endforeach; ?>
            </ul>
        </div>
        <div class="category hotels">
            <h3>Hotels & Accommodations</h3>
            <p>Average Days of Stay: <?php echo $averageDaysOfStayHotels; ?> days</p>
            <h4>Top 5</h4>
            <ul class="spot-list">
            <?php foreach ($hotelsTop5 as $spot): ?>
                <li><?= htmlspecialchars($spot['name']) ?> - <?= $spot['count'] ?> reservations</li>
            <?php endforeach; ?>
            </ul>
        </div>
        <div class="category dining">
            <h3>Dining</h3>
            <p>Most Visited Time: <?php echo $mostVisitedTimeDining; ?></p>
            <h4>Top 5</h4>
            <ul class="spot-list">
            <?php foreach ($diningTop5 as $spot): ?>
                <li><?= htmlspecialchars($spot['name']) ?> - <?= $spot['count'] ?> reservations</li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
  </div>
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
  </script>
</body>
</html>
