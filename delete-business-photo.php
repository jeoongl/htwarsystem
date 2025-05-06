<?php
session_start();
include 'includes/dbconnection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

// Retrieve the user's ID from the session
$user_id = $_SESSION['user_id'];

// Get the photo index and business ID from the request
if (isset($_POST['photoIndex']) && isset($_POST['business_id'])) {
    $photoIndex = (int)$_POST['photoIndex'];
    $businessId = (int)$_POST['business_id'];

    // Prepare the SQL update query to delete the photo
    $query = "UPDATE business_photos_tbl SET photo{$photoIndex} = NULL WHERE business_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $businessId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Photo deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
