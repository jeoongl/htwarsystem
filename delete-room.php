<?php
include 'includes/dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'])) {
    $business_id = $_POST['business_id'];
    $room_id = intval($_POST['room_id']);

    // Delete the room from the database
    $query = "DELETE FROM rooms_tbl WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $room_id);

    if ($stmt->execute()) {
        header('Location: user-settings.php?business_id=' . $business_id);
        exit();
    } else {
        echo "Error updating room: " . $stmt->error;
    }
    $stmt->close();
}
?>
