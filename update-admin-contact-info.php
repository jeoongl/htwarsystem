<?php
session_start();
include 'includes/dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized access!";
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_no = $_POST['contact_no'] ?? null;
    $email = $_POST['email'] ?? null;
    $facebook = $_POST['facebook'] ?? null;
    $instagram = $_POST['instagram'] ?? null;
    $x_twitter = $_POST['x_twitter'] ?? null;
    $linkedin = $_POST['linkedin'] ?? null;

    // Validate required fields (optional but recommended)
    if (empty($contact_no) || empty($email)) {
        echo "Contact number and email are required.";
        exit();
    }

    // Check if a record for the user already exists
    $query_check = "SELECT id FROM hinunangan_info_tbl WHERE user_id = ?";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Update the existing record
        $query_update = "UPDATE hinunangan_info_tbl 
                         SET contact_no = ?, email = ?, facebook = ?, instagram = ?, x_twitter = ?, linkedin = ?, updated_by = ? 
                         WHERE user_id = ?";
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bind_param("ssssssii", $contact_no, $email, $facebook, $instagram, $x_twitter, $linkedin, $user_id, $user_id);

        if ($stmt_update->execute()) {
            // Redirect to admin-settings.php after successful update
            header('Location: admin-settings.php');
            exit(); // Always call exit after header redirection
        } else {
            echo "Failed to update contact information: " . $stmt_update->error;
        }

        $stmt_update->close();
    } else {
        // Insert a new record
        $query_insert = "INSERT INTO hinunangan_info_tbl (user_id, contact_no, email, facebook, instagram, x_twitter, linkedin, updated_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($query_insert);
        $stmt_insert->bind_param("issssssi", $user_id, $contact_no, $email, $facebook, $instagram, $x_twitter, $linkedin, $user_id);

        if ($stmt_insert->execute()) {
            // Redirect to admin-settings.php after successful insertion
            header('Location: admin-settings.php');
            exit(); // Always call exit after header redirection
        } else {
            echo "Failed to add contact information: " . $stmt_insert->error;
        }

        $stmt_insert->close();
    }

    $stmt_check->close();
} else {
    echo "Invalid request method.";
}

$conn->close();
?>
