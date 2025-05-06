<?php
// Include database connection
include 'includes/dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'])) {
        $business_id = intval($_POST['id']);

        // Update the business_status to 2 (registered)
        $update_query = "UPDATE businesses_tbl SET business_status = 2 WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $business_id);

        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'error';
        }

        // Close the statement
        $stmt->close();
    }
}

// Close the database connection
$conn->close();
?>
