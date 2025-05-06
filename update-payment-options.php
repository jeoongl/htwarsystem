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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $business_id = $_POST['business_id'];
    $cash_payment = isset($_POST['cash_payment']) ? 1 : 0; // Checkbox returns 1 if checked
    $payment_option_1 = $_POST['payment_option_1'] ?? null;
    $payment_option_2 = $_POST['payment_option_2'] ?? null;
    $payment_option_3 = $_POST['payment_option_3'] ?? null;

    // Check if the business already has payment options
    $stmt = $conn->prepare("SELECT COUNT(*) FROM payment_options WHERE business_id = ?");
    $stmt->bind_param('i', $business_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        // Update existing payment options
        $updateQuery = "
            UPDATE payment_options
            SET cash_option = ?, payment_option_1 = ?, payment_option_2 = ?, payment_option_3 = ?
            WHERE business_id = ?
        ";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('isssi', $cash_payment, $payment_option_1, $payment_option_2, $payment_option_3, $business_id);
        if ($stmt->execute()) {
            echo "Payment options updated successfully!";
        } else {
            echo "Error updating payment options: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Insert new payment options
        $insertQuery = "
            INSERT INTO payment_options (business_id, cash_option, payment_option_1, payment_option_2, payment_option_3)
            VALUES (?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('iisss', $business_id, $cash_payment, $payment_option_1, $payment_option_2, $payment_option_3);
        if ($stmt->execute()) {
            echo "Payment options added successfully!";
        } else {
            echo "Error adding payment options: " . $stmt->error;
        }
        $stmt->close();
    }

    // Redirect back to the original page or display success message
    header("Location: user-settings.php?business_id=$business_id");
    exit;
} else {
    echo "Invalid request method.";
} 

?>
