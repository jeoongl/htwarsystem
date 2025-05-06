<?php
include 'includes/dbconnection.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $business_id = $_POST['business_id'];
  $opening_time = $_POST['opening_time'];
  $closing_time = $_POST['closing_time'];

  $query = "UPDATE businesses_tbl SET opening_time = ?, closing_time = ? WHERE id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ssi", $opening_time, $closing_time, $business_id);
  if ($stmt->execute()) {
    header("Location: user-settings.php?business_id=$business_id");
  } else {
    echo "Error updating business hours.";
  }
  $stmt->close();
}
?>
