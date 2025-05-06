<?php
// Start session and include the database connection
session_start();
include 'includes/dbconnection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: log-in.php');
    exit();
}

// Check if store_id is set in the URL and is valid
if (isset($_GET['store_id']) && is_numeric($_GET['store_id'])) {
    $store_id = $_GET['store_id'];

    // Prepare a delete statement to remove the store from the database
    $delete_query = "DELETE FROM businesses_tbl WHERE id = ? AND created_by = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $store_id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        // Redirect to the profile page with a success message
        header('Location: user-profile-page.php?message=Store+Deleted');
        exit();
    } else {
        // Handle error if deletion fails
        header('Location: user-profile-page.php?error=Failed+to+Delete');
        exit();
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    // Redirect if store_id is not set or invalid
    header('Location: user-profile-page.php?error=Invalid+Store');
    exit();
}
?>
