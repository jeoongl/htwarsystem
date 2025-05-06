<?php
session_start();
$target_dir = "uploads/";

// Database connection
$servername = "your_servername";
$username = "your_username";
$password = "your_password";
$dbname = "your_dbname";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    echo "User not authenticated.";
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $photos = $_FILES['photos'];
    $total_photos = count($photos['name']);

    if ($total_photos > 10) {
        echo "You can upload a maximum of 10 photos.";
        exit;
    }

    for ($i = 0; $i < $total_photos; $i++) {
        $target_file = $target_dir . basename($photos["name"][$i]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($photos["tmp_name"][$i]);
        if($check === false) {
            echo "File is not an image.";
            $uploadOk = 0;
        }

        // Check if file already exists
        if (file_exists($target_file)) {
            echo "Sorry, file already exists.";
            $uploadOk = 0;
        }

        // Check file size
        if ($photos["size"][$i] > 5000000) { // 5MB limit
            echo "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
            echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
        // if everything is ok, try to upload file
        } else {
            if (move_uploaded_file($photos["tmp_name"][$i], $target_file)) {
                // Prepare an SQL statement to insert the file metadata into the database
                $stmt = $conn->prepare("INSERT INTO photos (file_name, file_path, user_id) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $photos["name"][$i], $target_file, $user_id);

                if ($stmt->execute()) {
                    echo "The file ". htmlspecialchars(basename($photos["name"][$i])). " has been uploaded and saved to the database.";
                } else {
                    echo "Sorry, there was an error saving your file metadata to the database.";
                }

                $stmt->close();
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    }
} else {
    echo "No file uploaded.";
}

$conn->close();
?>
