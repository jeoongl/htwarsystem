<?php
session_start();
ob_start();  // Start output buffering

// Ensure the session variables are set
if (!isset($_SESSION['email']) || !isset($_SESSION['password'])) {
    // Redirect to the login page if session variables are missing
    header("Location: login.php");
    exit();
}

// Include the database connection
require_once 'includes/dbconnection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get data from session and POST request
    $email = $_SESSION['email'];
    $password = $_SESSION['password'];  // Assuming the password is already hashed
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    // Determine role_id based on selected role
    $role_id = ($role == 'business-owner') ? 2 : 3;

    // Prepare and bind the statement
    $stmt = $conn->prepare("INSERT INTO users_tbl (fullname, username, email, password, role_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $fullname, $username, $email, $password, $role_id);

    // Execute the statement
    if ($stmt->execute()) {
        // Fetch the user_id of the newly created account
        $user_id = $stmt->insert_id;

        // Store account details in session
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role_id'] = $role_id;
        $_SESSION['user_id'] = $user_id;  // Store user_id in session for later use

        // Redirect based on role_id
        if ($role_id == 2) {
            header("Location: user-login-index.php");
        } elseif ($role_id == 3) {
            header("Location: user-login-index.php");
        }
        exit();
    } else {
        // Handle errors
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
} else {
    // Redirect to the first page if accessed directly
    header("Location: index.html");
    exit();
}

ob_end_flush();  // End output buffering and send output
?>
