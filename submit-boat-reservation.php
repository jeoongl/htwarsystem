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
// Retrieve business ID and reservation details
$business_id = $_POST['business_id'];
$reservation_date = $_POST['reservation_date'];
$num_people = $_POST['num_people'];

// Booker's information from form
$bookers_lastname = $_POST['bookers_lastname'];
$bookers_firstname = $_POST['bookers_firstname'];
$bookers_gender = $_POST['bookers_gender'];
$bookers_tourist_type = $_POST['bookers_tourist_type'];
$bookers_phone = $_POST['bookers_phone'];
$bookers_email = $_POST['bookers_email'];
$notes = $_POST['notes'];
$payment_price = $_POST['payment_price'];

// Insert booker's information into `boat_reservations_tbl`
$insert_booker_query = "INSERT INTO reservations_tbl (lastname, firstname, gender, tourist_type, phone_number, email, notes, reservation_date, num_people, payment_price, business_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_booker_query);
$stmt->bind_param("ssssssssidi", $bookers_lastname, $bookers_firstname, $bookers_gender, $bookers_tourist_type, $bookers_phone, $bookers_email, $notes, $reservation_date, $num_people, $payment_price, $business_id);
$stmt->execute();

// Store the reservation ID for later use
$reservation_id = $stmt->insert_id;
$stmt->close();


// Retrieve passenger data arrays
$names = $_POST['names'];
$genders = $_POST['genders'];
$ages = $_POST['ages'];
$types = $_POST['types'];
$citizenships = $_POST['citizenships'];
$addresses = $_POST['addresses'];
$mobiles = $_POST['mobiles'];
$emails = $_POST['emails'];


// Prepare query to insert each passenger's info, linking to the booker's ID
$insert_passenger_query = "INSERT INTO boat_passengers_tbl (passenger_name, gender, age, tourist_type, citizenship, address, phone, email, bookers_id)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_passenger_query);

for ($i = 0; $i < count($names); $i++) {
    $passenger_name = htmlspecialchars($names[$i]);
    $passenger_gender = htmlspecialchars($genders[$i]);
    $passenger_age = intval($ages[$i]);
    $passenger_type = htmlspecialchars($types[$i]);
    $passenger_citizenship = htmlspecialchars($citizenships[$i]);
    $passenger_address = htmlspecialchars($addresses[$i]);
    $passenger_mobile = htmlspecialchars($mobiles[$i]);
    $passenger_email = htmlspecialchars($emails[$i]);

    // Use $reservation_id instead of $bookers_id
    $stmt->bind_param("ssisssssi", $passenger_name, $passenger_gender, $passenger_age, $passenger_type, $passenger_citizenship, $passenger_address, $passenger_mobile, $passenger_email, $reservation_id);
    $stmt->execute();
}


    // Fetch the created_at value
    $created_at_query = "SELECT created_at, payment_status, payment_method FROM reservations_tbl WHERE id = ?";
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


    // Fetch environmental fee and calculate totals
    $boat_price_query = "SELECT boat_price, environmental_fee FROM boat_fare_tbl WHERE business_id = ?";
    $stmt = $conn->prepare($boat_price_query);
    $stmt->bind_param("i", $business_id);
    $stmt->execute();
    $stmt->bind_result($boat_price, $environmental_fee);
    $stmt->fetch();
    $stmt->close();

    $total_env_fee = $environmental_fee * $num_people;
    $total_payment = $total_env_fee + $boat_price; // Use boat_price
    
// PDF generation
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('Helvetica', 'B', 16);
$pdf->Cell(0, 10, $business_name, 0, 1, 'C');
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(0, 10, $business_address, 0, 1, 'C');

$pdf->Ln(10);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Passengers Details', 0, 1, 'C');

$pdf->SetFont('Helvetica', '', 8);
$pdf->Cell(30, 8, 'Name', 1);
$pdf->Cell(15, 8, 'Gender', 1);
$pdf->Cell(10, 8, 'Age', 1);
$pdf->Cell(25, 8, 'Tourist Type', 1);
$pdf->Cell(20, 8, 'Citizenship', 1);
$pdf->Cell(35, 8, 'Address', 1);
$pdf->Cell(20, 8, 'Phone', 1);
$pdf->Cell(25, 8, 'Email', 1);
$pdf->Ln();

$passenger_query = "SELECT passenger_name, gender, age, tourist_type, citizenship, address, phone, email FROM boat_passengers_tbl WHERE bookers_id = ?";
$stmt = $conn->prepare($passenger_query);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$passenger_result = $stmt->get_result();

while ($row = $passenger_result->fetch_assoc()) {
    $pdf->Cell(30, 8, $row['passenger_name'], 1);
    $pdf->Cell(15, 8, $row['gender'], 1);
    $pdf->Cell(10, 8, $row['age'], 1);
    $pdf->Cell(25, 8, $row['tourist_type'], 1);
    $pdf->Cell(20, 8, $row['citizenship'], 1);
    $pdf->Cell(35, 8, $row['address'], 1);
    $pdf->Cell(20, 8, $row['phone'], 1);
    $pdf->Cell(25, 8, $row['email'], 1);
    $pdf->Ln();
}
$stmt->close();


    // Write "Reservation Details" text
    $pdf->Ln(10); // Add a line break
    $pdf->SetFont('Helvetica', 'B', 12); // Make "Reservation Details" bold
    $pdf->Cell(0, 10, "Booker's Info", 0, 1, 'C');

    // Set font for reservation details (normal)
    $pdf->SetFont('Helvetica', '', 10);

    $pdf->Cell(0, 8, 'Name: ' . $bookers_firstname . ' ' . $bookers_lastname, 0, 1);
    $pdf->Cell(0, 8, 'Reservation Date: ' . $reservation_date, 0, 1);
    $pdf->Cell(0, 8, 'Number of People: ' . $num_people, 0, 1);
    $pdf->Cell(0, 8, 'Gender: ' . $bookers_gender, 0, 1);
    $pdf->Cell(0, 8, 'Tourist Type: ' . $bookers_tourist_type, 0, 1);
    $pdf->Cell(0, 8, 'Phone: ' . $bookers_phone, 0, 1);
    $pdf->Cell(0, 8, 'Email: ' . $bookers_email, 0, 1);
    $pdf->Cell(0, 8, 'Notes: ' . $notes, 0, 1);
    $pdf->Cell(0, 8, 'Created At: ' . $created_at, 0, 1); // Add created_at below notes
    $pdf->Ln(10); // Add a line break

    // Add the payment price
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Payment Details', 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 8, 'Environmental Fee: PHP ' . number_format($environmental_fee, 2) . ' x ' . $num_people . ' = PHP ' . number_format($total_env_fee, 2), 0, 1);
    $pdf->Cell(0, 8, 'Boat Price: PHP ' . number_format($boat_price, 2), 0, 1);
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Total Payment: PHP ' . number_format($total_payment, 2), 0, 1);

    
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
        $mail->addAddress($bookers_email, $bookers_firstname . ' ' . $bookers_lastname); // User email and name

        // Attach PDF
        $mail->addAttachment($pdf_file_path);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Boat Reservation Created!';
        $mail->Body = '
        <p>You have reserved a boat to ' . $business_name . '. Please find the reservation details attached in the PDF.</p>
        <p>Click the following link to see payment details, and upload your proof of payment to continue with your reservation:</p>
        <p><a href="http://localhost/htwarsystem/submit-proof-of-payment.php?reservation_id=' . $reservation_id . '">Upload Proof of Payment</a></p>';
        $mail->AltBody = 'You have reserved a table at ' . $business_name . '. 
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
    header("Location: store-profile.php?business_id=$business_id");
    exit();

$stmt->close();
$conn->close();
?>
