<?php
session_start();
include 'includes/dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $business_id = $_POST['business_id'];
    $roomType = $_POST['room_type'];
    $maxOccupancy = $_POST['max_occupancy'];
    $price = $_POST['price'];
    $totalRooms = $_POST['total_rooms'];

    // Prepare the SQL query
    $addQuery = "INSERT INTO rooms_tbl (business_id, room_type, max_occupancy, price, total_rooms) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($addQuery);

    if ($stmt) {
        // Bind parameters (i = integer, s = string, d = double)
        $stmt->bind_param("isidd", $business_id, $roomType, $maxOccupancy, $price, $totalRooms);

        // Execute the statement
        if ($stmt->execute()) {
            // Redirect after successful insertion
            header('Location: user-settings.php?business_id=' . $business_id);
            exit();
        } else {
            echo "Error adding room: " . $stmt->error;
        }
        // Close the statement
        $stmt->close();
    } else {
        echo "Error preparing the query: " . $conn->error;
    }
}
// Close the connection
$conn->close();
?>
