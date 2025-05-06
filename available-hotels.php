<?php
// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check_in'], $_GET['check_out'])) {
    $check_in = $_GET['check_in'];
    $check_out = $_GET['check_out'];
    $adults = $_GET['adults'];
    $children = $_GET['children'];
    $rooms = $_GET['rooms'];

    // Query to get available hotels
    $query = "
        SELECT b.id AS hotel_id, b.business_name, b.business_address, b.cover_photo, r.room_type, ra.available_rooms
        FROM businesses_tbl b
        JOIN rooms_tbl r ON b.id = r.business_id
        JOIN rooms_availability ra ON r.id = ra.room_id
        WHERE b.category_id = 3
        AND ra.available_rooms >= ?
        AND ra.date BETWEEN ? AND ?
        GROUP BY b.id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $rooms, $check_in, $check_out);
    $stmt->execute();
    $result = $stmt->get_result();

    // Display results
    if ($result->num_rows > 0) {
        echo '<div class="all-tourist-sites">';
        while ($hotel = $result->fetch_assoc()) {
            echo '<div class="container">';
            echo '<img src="img/' . htmlspecialchars($hotel['cover_photo']) . '" alt="Hotel">';
            echo '<div class="details">';
            echo '<h4>' . htmlspecialchars($hotel['business_name']) . '</h4>';
            echo '<p class="address"><i class="fas fa-map-marker-alt"></i>' . htmlspecialchars($hotel['business_address']) . '</p>';
            echo '<div class="buttons-wrapper">';
            echo '<a href="hotel-details.php?id=' . $hotel['hotel_id'] . '" class="view-btn">View</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>No hotels available for the selected dates and conditions.</p>';
    }

    $stmt->close();
}
?>
