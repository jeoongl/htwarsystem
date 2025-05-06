<?php
include 'includes/dbconnection.php';

if (isset($_POST['id'])) {
    $business_id = $_POST['id'];

    // Prepare query to fetch business details
    $query = "
        SELECT lastname, firstname, gender, tourist_type, num_people, reservation_date, reservation_time,
        phone_number, email, created_at, payment_price, payment_status
        FROM reservations_tbl 
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $business_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch and output the business details as JSON
        $reservation = $result->fetch_assoc();
        echo json_encode($reservation);
    } else {
        echo json_encode(['error' => 'Business not found']);
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
