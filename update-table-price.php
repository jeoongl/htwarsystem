<?php
include 'includes/dbconnection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $business_id = $_POST['business_id'];
    $table_price = $_POST['table_price'];
    $environmental_fee = $_POST['dining_environmental_fee'];

    // Check if record exists
    $check_query = "SELECT id FROM table_prices_tbl WHERE business_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $business_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // Update existing record
        $update_query = "UPDATE table_prices_tbl SET table_price = ?, environmental_fee = ? WHERE business_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ddi", $table_price, $environmental_fee, $business_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert new record
        $insert_query = "INSERT INTO table_prices_tbl (business_id, table_price, environmental_fee) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("idd", $business_id, $table_price, $environmental_fee);
        $insert_stmt->execute();
        $insert_stmt->close();
    }

    $check_stmt->close();
    $conn->close();

    // Redirect back to the main page or settings page
    header("Location: user-settings.php?business_id=$business_id");
    exit();
}
?>
