<?php
session_start();
if (!isset($_SESSION['registration_success']) || !$_SESSION['registration_success']) {
    header("Location: first_page.html");
    exit();
}
unset($_SESSION['registration_success']); // Clear the flag
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: black;
            color: white;
            margin: 0;
            padding: 0;
        }

        .modal {
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: #333;
            padding: 20px;
            border-radius: 8px;
            width: 300px;
            text-align: center;
        }

        .modal-content h2 {
            margin: 0 0 15px 0;
        }

        .modal-content button {
            background-color: green;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .modal-content button:hover {
            background-color: white;
            color: black;
        }
    </style>
</head>
<body>
    <div class="modal">
        <div class="modal-content">
            <h2>Registration Successful!</h2>
            <p>Your account has been successfully created.</p>
            <button onclick="redirect()">Go to Login</button>
        </div>
    </div>

    <script>
        function redirect() {
            window.location.href = 'login-index.php';
        }

        // Redirect after 3 seconds if not manually clicked
        setTimeout(redirect, 3000);
    </script>
</body>
</html>
