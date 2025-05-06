<?php 
session_start();
include 'includes/dbconnection.php';
require_once('tcpdf.php'); // Correct path to TCPDF
require 'src/PHPMailer.php';  // PHPMailer library
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $status = $_POST['status'];

    // Step 1: Update the payment status in the reservations table
    $query = "UPDATE reservations_tbl SET payment_status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $status, $id);

    if ($stmt->execute()) {
        // Step 2: Fetch the email address and business_id of the reservation
        $query = "SELECT email, business_id FROM reservations_tbl WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $email = $row['email'];
            $business_id = $row['business_id'];

            // Step 3: Fetch the business name from the businesses_tbl using business_id
            $query = "SELECT name FROM businesses_tbl WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $business_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $business_row = $result->fetch_assoc();
                $business_name = $business_row['name'];

                // Step 4: Send the email using PHPMailer
                try {
                    $mail = new PHPMailer(true);
                    
                    //Server settings
                    $mail->isSMTP();                                            // Send using SMTP
                    $mail->Host = 'smtp.gmail.com';                              // Set the SMTP server to send through
                    $mail->SMTPAuth = true;
                    $mail->Username = 'jeoong012303@gmail.com';                 // Your SMTP username
                    $mail->Password = 'ubwr jphx hcod kibg';                    // Your SMTP password or App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
                    $mail->Port       = 587;                                    // TCP port to connect to

                    //Recipients
                    $mail->setFrom('jeoong012303@gmail.com', 'Hinunangan Tourism');  // Business email and name
                    $mail->addAddress($email);                                   // Add a recipient

                    // Content
                    $mail->isHTML(false);                                       // Set email format to plain text
                    $mail->Subject = 'Reservation Confirmed';
                    $mail->Body    = "Your reservation at {$business_name} has been successfully processed. You can show this as proof of your reservation.\n\nBest regards,\nHinunangan Tourism";

                    // Send the email
                    $mail->send();
                    echo 'success';
                } catch (Exception $e) {
                    echo "email_error: {$mail->ErrorInfo}";
                }
            } else {
                echo 'business_not_found';
            }
        } else {
            echo 'no_email_found';
        }

        $stmt->close();
    } else {
        echo 'error';
    }
}
?>
