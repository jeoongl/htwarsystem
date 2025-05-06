<?php
// Include database connection
include 'includes/dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $business_id = intval($_POST['id']);

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Delete related records from table_reservations_tbl
        $delete_reservations_query = "DELETE FROM table_reservations_tbl WHERE business_id = ?";
        $stmt_reservations = $conn->prepare($delete_reservations_query);
        $stmt_reservations->bind_param("i", $business_id);
        $stmt_reservations->execute();
        $stmt_reservations->close();

        // Delete related records from business_embeds_tbl
        $delete_embeds_query = "DELETE FROM business_embeds_tbl WHERE business_id = ?";
        $stmt_embeds = $conn->prepare($delete_embeds_query);
        $stmt_embeds->bind_param("i", $business_id);
        $stmt_embeds->execute();
        $stmt_embeds->close();

        // Delete related records from business_photos_tbl
        $delete_photos_query = "DELETE FROM business_photos_tbl WHERE business_id = ?";
        $stmt_photos = $conn->prepare($delete_photos_query);
        $stmt_photos->bind_param("i", $business_id);
        $stmt_photos->execute();
        $stmt_photos->close();

        // Delete the business from businesses_tbl
        $delete_business_query = "DELETE FROM businesses_tbl WHERE id = ?";
        $stmt_business = $conn->prepare($delete_business_query);
        $stmt_business->bind_param("i", $business_id);
        $stmt_business->execute();
        $stmt_business->close();

        // Commit the transaction if all queries succeed
        $conn->commit();
        echo 'success';
    } catch (Exception $e) {
        // Rollback transaction if any query fails
        $conn->rollback();
        echo 'error';
    }
}

$conn->close();
?>
