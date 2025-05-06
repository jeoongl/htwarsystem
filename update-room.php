<?php
session_start();
include 'includes/dbconnection.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_POST['business_id'];
    $roomId = $_POST['room_id'];
    $roomType = $_POST['room_type'];
    $maxOccupancy = $_POST['max_occupancy'];
    $price = $_POST['price'];
    $totalRooms = $_POST['total_rooms'];

    $updateQuery = "UPDATE rooms_tbl SET room_type = ?, max_occupancy = ?, price = ?, total_rooms = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("siidi", $roomType, $maxOccupancy, $price, $totalRooms, $roomId);

    if ($stmt->execute()) {
        header('Location: user-settings.php?business_id=' . $business_id);
        exit();
    } else {
        echo "Error updating room: " . $stmt->error;
    }
    $stmt->close();
}
?>
