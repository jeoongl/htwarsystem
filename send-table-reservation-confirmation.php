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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: log-in.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch data from the POST request
$reservation_date = $_POST['reservation_date'] ?? '';
$reservation_time = $_POST['reservation_time'] ?? '';
$num_people = $_POST['num_people'] ?? '';
$lastname = $_POST['lastname'] ?? '';
$firstname = $_POST['firstname'] ?? '';
$gender = $_POST['gender'] ?? '';
$tourist_type = $_POST['tourist_type'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$notes = $_POST['notes'] ?? '';
$business_id = $_POST['business_id'] ?? '';
$payment_price = $_POST['payment_price'] ?? '';

// Insert reservation into the database
$query = "INSERT INTO reservations_tbl (business_id, reservation_date, reservation_time, num_people, lastname, firstname, gender, tourist_type, phone_number, email, notes, payment_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ississsssssd", $business_id, $reservation_date, $reservation_time, $num_people, $lastname, $firstname, $gender, $tourist_type, $phone, $email, $notes, $payment_price);

if ($stmt->execute()) {
    // Fetch the created_at value
    $created_at_query = "SELECT created_at,  payment_status, payment_method FROM reservations_tbl WHERE id = ?";
    $reservation_id = $stmt->insert_id; // Get the last inserted ID
    $created_at_stmt = $conn->prepare($created_at_query);
    $created_at_stmt->bind_param("i", $reservation_id);
    $created_at_stmt->execute();
    $created_at_stmt->bind_result($created_at, $payment_status, $payment_method);
    $created_at_stmt->fetch();
    $created_at_stmt->close();

    // Fetch business email from business_embeds_tbl
    $business_email_query = "SELECT email FROM business_embeds_tbl WHERE business_id = ?";
    $business_email_stmt = $conn->prepare($business_email_query);
    $business_email_stmt->bind_param("i", $business_id);
    $business_email_stmt->execute();
    $business_email_stmt->bind_result($business_email);
    $business_email_stmt->fetch();
    $business_email_stmt->close();

    // Fetch business name from businesses_tbl
    $business_name_query = "SELECT name, address FROM businesses_tbl WHERE id = ?";
    $business_name_stmt = $conn->prepare($business_name_query);
    $business_name_stmt->bind_param("i", $business_id);
    $business_name_stmt->execute();
    $business_name_stmt->bind_result($business_name, $business_address); // Add $business_address
    $business_name_stmt->fetch();
    $business_name_stmt->close();    

    // PDF generation
    $pdf = new TCPDF();
    $pdf->AddPage();

    // Set font for business name (bold and bigger font size)
    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->Cell(0, 10, $business_name, 0, 1, 'C'); // Business name at top center

    // Set font for address (smaller and not bold)
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 10, $business_address, 0, 1, 'C'); // Business address below the name

    // Write "Reservation Details" text
    $pdf->Ln(10); // Add a line break
    $pdf->SetFont('Helvetica', 'B', 12); // Make "Reservation Details" bold
    $pdf->Cell(0, 10, "Booker's Info", 0, 1, 'C');

    // Set font for reservation details (normal)
    $pdf->SetFont('Helvetica', '', 10);

    $pdf->Cell(0, 8, 'Name: ' . $firstname . ' ' . $lastname, 0, 1);
    $pdf->Cell(0, 8, 'Reservation Date: ' . $reservation_date, 0, 1);
    $pdf->Cell(0, 8, 'Reservation Time: ' . $reservation_time, 0, 1);
    $pdf->Cell(0, 8, 'Number of People: ' . $num_people, 0, 1);
    $pdf->Cell(0, 8, 'Gender: ' . $gender, 0, 1);
    $pdf->Cell(0, 8, 'Tourist Type: ' . $tourist_type, 0, 1);
    $pdf->Cell(0, 8, 'Phone: ' . $phone, 0, 1);
    $pdf->Cell(0, 8, 'Email: ' . $email, 0, 1);
    $pdf->Cell(0, 8, 'Notes: ' . $notes, 0, 1);
    $pdf->Cell(0, 8, 'Created At: ' . $created_at, 0, 1); // Add created_at below notes
    $pdf->Ln(10); // Add a line break

    $pdf->Ln(10); // Add a line break
    $pdf->SetFont('Helvetica', 'B', 12); // Reset font to smaller
    $pdf->Cell(0, 10, 'Payment Details:', 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 8, 'Table Price: PHP ' . number_format($payment_price, 2), 0, 1);
    $pdf->SetFont('Helvetica', 'B', 14); // Make price bold
    $pdf->Cell(0, 8, 'Total Payment: PHP ' . number_format($payment_price, 2), 0, 1, ); // Align price to the right
    $pdf->SetFont('Helvetica', '', 12);

    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 8, 'Payment Status: ' . $payment_status, 0, 1);
    $pdf->Cell(0, 8, 'Payment Method: ' . $payment_method, 0, 1);

    // Save the PDF to a temporary file
    $pdf_file_path = __DIR__ . '/temp/reservation_' . time() . '.pdf'; // Use relative path
    $pdf->Output($pdf_file_path, 'F');

    // Send email with PHPMailer
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jeoong012303@gmail.com'; // Your SMTP username
        $mail->Password = 'ubwr jphx hcod kibg'; // Your SMTP password or App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender and recipient
        $mail->setFrom('jeoong012303@gmail.com', 'Hinunangan Tourism'); // Business email and name
        $mail->addAddress($email, $firstname . ' ' . $lastname); // User email and name

        // Attach PDF
        $mail->addAttachment($pdf_file_path);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Reservation Success!';
        $mail->Body = '
        <p>Your table reservation at ' . $business_name . ' is successfully confirmed. Please find the reservation details attached in the PDF.</p>
        <p>Click the following link to see payment details, and upload your proof of payment:</p>
        <p><a href="http://localhost/htwarsystem/submit-proof-of-payment.php?reservation_id=' . $reservation_id . '">Upload Proof of Payment</a></p>';
        $mail->AltBody = 'Your table reservation at ' . $business_name . ' is successfully confirmed. 
        Click the following link to see payment details, and upload your proof of payment: 
        http://localhost/htwarsystem/submit-proof-of-payment.php' . $reservation_id;
        
        // Send email
        $mail->send();
    } catch (Exception $e) {
        echo "Reservation made, but email could not be sent. Error: {$mail->ErrorInfo}";
    }

    // Cleanup: Delete the temporary PDF file
    unlink($pdf_file_path);

    // Redirect to business profile page
    header("Location: manage-reservations.php?business_id=$business_id");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
