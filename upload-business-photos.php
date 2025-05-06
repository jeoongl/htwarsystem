<?php
// Start session and include database connection
session_start();
include 'includes/dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: log-in.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch business details based on the business ID (this can be passed via GET request)
$business_id = isset($_GET['business_id']) ? $_GET['business_id'] : null;
$show_owners_button = false; // Initialize a variable to control the button visibility

// Directory where photos will be saved
$uploadDir = 'uploads/business_photos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true); // Create directory if it doesn't exist
}

// Fetch the current photos from the database
$query = "SELECT photo1, photo2, photo3, photo4, photo5, photo6, photo7, photo8, photo9, photo10, photo11, photo12 FROM business_photos_tbl WHERE business_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$existingPhotos = $result->fetch_assoc();
$stmt->close();

// Handle uploaded files
$uploadedPhotos = $_FILES['photos'];
$totalFiles = count($uploadedPhotos['name']);
$updateFields = [];
$updateValues = [];

// Loop through the uploaded files
for ($i = 0; $i < $totalFiles; $i++) {
    $fileName = basename($uploadedPhotos['name'][$i]);
    $fileTmpName = $uploadedPhotos['tmp_name'][$i];

    // Check for empty slots in the photo columns
    for ($j = 1; $j <= 12; $j++) {
        if (empty($existingPhotos["photo$j"])) {
            $newFilePath = $uploadDir . uniqid() . '-' . $fileName;

            // Move uploaded file to the designated directory
            if (move_uploaded_file($fileTmpName, $newFilePath)) {
                // Update the corresponding column in the database
                $updateFields[] = "photo$j = ?";
                $updateValues[] = $newFilePath;
            }
            break; // Stop the inner loop once a slot is filled
        }
    }
}

// If there are new photos, update the database
if (!empty($updateFields)) {
    $sql = "UPDATE business_photos_tbl SET " . implode(', ', $updateFields) . " WHERE business_id = ?";
    $updateValues[] = $user_id;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($updateValues) - 1) . 'i', ...$updateValues);
    if ($stmt->execute()) {
        echo "Photos updated successfully";
    } else {
        echo "Error updating photos: " . $conn->error;
    }
    $stmt->close();
} else {
    echo "No new photos to upload.";
}

$conn->close();
?>
