<?php
// Start session and include database connection
session_start();
include 'includes/dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: log-in.php');
    exit();
}

// Get the user's ID from the session
$user_id = $_SESSION['user_id'];

// Check if data is posted from the contact info form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve data from the form
    $business_id = $_POST['business_id'];
    $google_maps_link = $_POST['google_maps_link'];
    $updated_by = $user_id; // Assuming the logged-in user's ID is stored in the session

    // Check if the business embed entry already exists
    $check_query = "SELECT COUNT(*) FROM business_embeds_tbl WHERE business_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $business_id);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        // Entry exists, perform an update
        $query = "UPDATE business_embeds_tbl SET google_maps_link = ?, updated_by = ? WHERE business_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $google_maps_link, $updated_by, $business_id);

        // Execute and check if update was successful
        if ($stmt->execute()) {
            $_SESSION['message'] = "Contact information updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update contact information.";
        }
    } else {
        // No entry exists, perform an insert
        $query = "INSERT INTO business_embeds_tbl (business_id, google_maps_link, updated_by) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isi", $business_id, $google_maps_link, $updated_by);

        // Execute and check if insertion was successful
        if ($stmt->execute()) {
            $_SESSION['message'] = "Contact information added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add contact information.";
        }
    }
    $stmt->close();

    // Redirect back to the main page or settings page
    header("Location: user-settings.php?business_id=$business_id");
    exit();
}
?>
