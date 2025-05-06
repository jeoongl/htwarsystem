<?php
session_start();
include 'includes/dbconnection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Check if reservation ID is provided
if (!isset($_POST['reservation_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Reservation ID is missing']);
    exit();
}

$reservation_id = intval($_POST['reservation_id']);

// Perform the deletion
$query = "DELETE FROM reservations_tbl WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservation_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Reservation deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete reservation']);
}

$stmt->close();
$conn->close();
?>
