<?php
// Start session and include database connection
session_start();
include 'includes/dbconnection.php';
require_once('tcpdf.php'); // Correct path to TCPDF
require 'src/PHPMailer.php';  // PHPMailer library
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Check if reservation_id is passed in the URL
if (!isset($_GET['reservation_id'])) {
    die("Reservation ID not provided.");
}

$reservation_id = $_GET['reservation_id'];

// Initialize success flag
$success = false;

// Fetch reservation details including payment_reference
$query = "
    SELECT 
        b.id AS business_id,
        b.name AS business_name, 
        r.payment_price, 
        r.reservation_date,
        r.proof_of_payment 
    FROM reservations_tbl r
    JOIN businesses_tbl b ON r.business_id = b.id
    WHERE r.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$stmt->bind_result($business_id, $business_name, $payment_price, $reservation_date, $proof_of_payment);
$stmt->fetch();
$stmt->close();

// Fetch payment options for the business
$payment_options_query = "
    SELECT cash_option, payment_option_1, payment_option_2, payment_option_3 
    FROM payment_options 
    WHERE business_id = ?
";
$payment_options_stmt = $conn->prepare($payment_options_query);
$payment_options_stmt->bind_param("i", $business_id);
$payment_options_stmt->execute();
$payment_options_stmt->bind_result($cash_option, $payment_option_1, $payment_option_2, $payment_option_3);
$payment_options_stmt->fetch();
$payment_options_stmt->close();

// If no data is found, display an error
if (!$business_name || !$payment_price) {
    die("Invalid Reservation ID or no data found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate payment method
    if (empty($_POST['payment-method'])) {
        die("Please select a payment method.");
    }
    $payment_method = $_POST['payment-method'];

    // Validate file upload
    if (!isset($_FILES['payment-photo']) || $_FILES['payment-photo']['error'] !== UPLOAD_ERR_OK) {
        die("Error uploading the photo. Please try again.");
    }

    // Process file upload
    $upload_dir = 'uploads/payment_photos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $file_name = uniqid() . "_" . basename($_FILES['payment-photo']['name']);
    $target_file = $upload_dir . $file_name;

    if (!move_uploaded_file($_FILES['payment-photo']['tmp_name'], $target_file)) {
        die("Failed to save the uploaded file. Please try again.");
    }

    // Update the reservation with the payment method and proof
    $update_query = "
        UPDATE reservations_tbl 
        SET payment_method = ?, proof_of_payment = ?
        WHERE id = ?
    ";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $payment_method, $file_name, $reservation_id);
    if ($update_stmt->execute()) {
        $success = true;

        // Fetch the business owner's email
        $email_query = "SELECT email FROM business_embeds_tbl WHERE business_id = ?";
        $email_stmt = $conn->prepare($email_query);
        $email_stmt->bind_param("i", $business_id);
        $email_stmt->execute();
        $email_stmt->bind_result($business_email);
        $email_stmt->fetch();
        $email_stmt->close();

        // Send email with PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'jeoong012303@gmail.com'; // Your SMTP username
            $mail->Password = 'ubwr jphx hcod kibg';   // Your SMTP password or App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Sender and recipient
            $mail->setFrom('jeoong012303@gmail.com', 'Hinunangan Tourism');
            $mail->addAddress($business_email, $business_name);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'New Reservation Created';
            $mail->Body = "
                <p>A new reservation is created for your business <strong>{$business_name}</strong> for {$reservation_date}.</p>
                <p>Please visit you store and review the payment proof to confirm reservation.</p>
                <p><a href='http://localhost/htwarsystem/index.php'>Visit Store</a></p>
            ";
            $mail->AltBody = "
                A new reservation is created for your business {$business_name}. Please review it.
                <p><a href='http://localhost/htwarsystem/index.php'>Visit Store</a></p>";

            // Send email
            $mail->send();
        } catch (Exception $e) {
            echo "Reservation updated, but email could not be sent. Error: {$mail->ErrorInfo}";
        }

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?reservation_id=" . $reservation_id);
        exit();
    } else {
        die("Failed to update reservation. Please try again.");
    }
    $update_stmt->close();
}

// Fetch terms and conditions for the business
$terms_query = "SELECT terms_and_conditions FROM business_embeds_tbl WHERE business_id = ?";
$terms_stmt = $conn->prepare($terms_query);
$terms_stmt->bind_param("i", $business_id);
$terms_stmt->execute();
$terms_stmt->bind_result($terms_and_conditions);
$terms_stmt->fetch();
$terms_stmt->close();

// Default terms and conditions if none exist in the database
if (empty($terms_and_conditions)) {
    $terms_and_conditions = "1. Agreement to Terms
By completing your reservation and payment, you agree to these Terms and Conditions. Please review them carefully.

2. Cancellation and Rescheduling Policy
Reservations cannot be cancelled or rescheduled through the website.
However, if you need to cancel or reschedule, you must contact the store directly using the provided contact information.
Refunds or changes may be possible only upon mutual agreement with the store and may incur additional fees.

3. Contact Information
For any inquiries, cancellations, or rescheduling requests, please contact the store using the details in your booking confirmation.

4. Customer Responsibilities
Customers must ensure all booking details are accurate.
Arrive on time as specified in the booking confirmation.
Late arrivals may result in forfeiture of the reservation.

5. Store Responsibilities
The store is responsible for providing the services as detailed in the booking confirmation.
Maintain accurate and accessible contact information for customers.

6. Force Majeure
The store is not liable for cancellations or changes due to events beyond its control, such as natural disasters or government actions.

7. Acceptance of Terms
By making a reservation, you acknowledge and accept these Terms and Conditions in full.";
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Info</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      margin: 0;
      padding: 0;
      background-color: black;
      color: white;
    }
    header {
      background-color: green;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo {
      width: 350px;
    }
        .payment-container {
            background-color: black;
            padding: 20px;
            border-radius: 8px;
            width: 300px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            text-align: center;
            margin: 50px auto;
        }
        .payment-container h2 {
            margin-top: 0;
            font-size: 32px;
            text-align: left;
        }
        .payment-container p {
            text-align: left;
            margin-bottom: 20px;
        }
        .payment-container select {
            width: 100%;
            padding: 15px;
            border: 1px solid #555;
            border-radius: 4px;
            background-color: #333;
            color: white;
            appearance: none; /* Removes default browser styles for the dropdown */
            cursor: pointer;
        }

        .payment-container #payment-proof-text {
            text-align: left;
            font-size: 11px;
            font-style: italic;
            margin-bottom: 20px;
        }
        .payment-container .payment-photo,
        .payment-container .payment-method {
            position: relative;
            width: 100%;
            margin: 25px 0;
        }

        .payment-container .payment-method::after {
            content: '\f107'; /* FontAwesome dropdown icon */
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            pointer-events: none;
        }

        .payment-container input {
            width: 89.5%;
            padding: 15px;
            border: 1px solid #555;
            border-radius: 4px;
            background-color: #333;
            color: white;
        }
        .payment-container button {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: green;
            color: white;
            font-size: 16px;
        }
        .payment-option {
            margin: 0; /* Remove additional margins */
            line-height: 0.2; /* Adjust line spacing */
        }

        .modal {
      display: none;
      position: fixed;
      z-index: 1;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: #333;
      color: white;
      padding: 20px;
      border-radius: 8px;
      text-align: center;
    }

    .modal-content h2 {
      font-size: 24px;
    }
    .modal-content button {
      padding: 10px 20px;
      margin: 5px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }
    .modal-content .close {
      background-color: #555;
      color: white;
    }

    .modal-content .close:hover {
      background-color: #444;
    }

    .modal-content .agree {
      background-color: green;
      color: white;
    }

    .modal-content .agree:hover {
      background-color: darkgreen;
    }
    .success-icon {
      text-align: center;
      margin-bottom: 20px;
    }

    .success-icon i {
      font-size: 70px;
      color: green;
      border: 5px solid green;
      border-radius: 50%;
      padding: 20px;
    }

  </style>
</head>
<body>
<header>
    <img class="logo" src="img/logo.png" alt="Logo">
  </header>
  
  <div class="payment-container">
    <?php if (empty($proof_of_payment)) { ?>
    <h2>Upload Proof of Payment</h2>
    <p id="payment-proof-text">
        Please submit proof of payment (screenshots or pictures) using the methods provided by the business owner. e.g. Gcash, PayPal, or bank transfers.
    </p>
    <form action="" method="post" enctype="multipart/form-data">
        <div class="payment-method">
        <select name="payment-method" onchange="toggleSubmitButton()" required>
                <option value="" disabled selected>Select Payment Method</option>
                <option value="Gcash">Gcash</option>
                <option value="Paypal">Paypal</option>
                <option value="PNB">PNB</option>
                <option value="BDO">BDO</option>
            </select>
        </div>
        <div class="payment-photo">
        <input type="file" name="payment-photo" onchange="toggleSubmitButton()" required>
        </div>
      <!-- Terms and Conditions -->
      <div style="display: flex; align-items: center; gap:5px;">
      <input type="checkbox" id="termsCheckbox" onchange="toggleSubmitButton()" style="margin: 0;">
        <label for="termsCheckbox" style="margin: 0 10px 0 0; white-space: nowrap;">
            I agree to the <a href="javascript:void(0);" onclick="showTermsModal()" style="color: lightblue; text-decoration: none;">Terms and Conditions</a>.
        </label>
    </div>
    <button type="submit" id="submitButton" disabled style="background-color: gray; cursor: not-allowed;">Submit</button>
  </form>

    <h3>Payment Details</h3>

    <p>Pending Payment at <?php echo htmlspecialchars($business_name); ?>: <strong>PHP <?php echo number_format($payment_price, 2); ?></strong></p>

    <h3>How to pay?</h3>
    <ul>
        <?php if ($cash_option == 1) { ?>
            <p class="payment-option"><i class="fas fa-check"></i> Cash</p>
        <?php } ?>
        <?php if (!empty($payment_option_1)) { ?>
            <p class="payment-option"><i class="fas fa-check"></i> <?php echo htmlspecialchars($payment_option_1); ?></p>
        <?php } ?>
        <?php if (!empty($payment_option_2)) { ?>
            <p class="payment-option"><i class="fas fa-check"></i> <?php echo htmlspecialchars($payment_option_2); ?></p>
        <?php } ?>
        <?php if (!empty($payment_option_3)) { ?>
            <p class="payment-option"><i class="fas fa-check"></i> <?php echo htmlspecialchars($payment_option_3); ?></p>
        <?php } ?>
    </ul>
    <?php } else { ?>
      <div class="success-icon">
        <i class="fas fa-check-circle"></i>
    </div>
        <h2>Already Submitted</h2>
        <p>You have already submitted your proof of payment for <?php echo htmlspecialchars($business_name); ?>. 
        Thank you for submitting. Note that the owner will review your proof of payment and will reply if there is any problem.</p>
    <?php } ?>
</div>

<div id="success-modal" class="modal">
  <div class="modal-content">
    <h2>Business registered successfully!</h2>
    <p>Redirecting to your profile page...</p>
  </div>
</div>

<!-- Terms and Conditions Modal -->
<div id="termsModal" class="modal">
  <div class="modal-content">
        <h2>Terms and Conditions</h2>
        <pre style="text-align: left; white-space: pre-wrap; color: white; font-family: inherit;">
<?php echo htmlspecialchars($terms_and_conditions); ?>
        </pre>
        <button class="close" onclick="closeTermsModal()">Close</button>
        <button class="agree" onclick="agreeToTerms()">Agree</button>
    </div>
</div>


  <script>
    // Script to trigger the file input when the label is clicked
    document.querySelector('.upload-photo-label').addEventListener('click', function() {
      document.getElementById('upload-photo').click();
    });

    // Update the input field with the selected file name
    document.getElementById('upload-photo').addEventListener('change', function() {
      const fileName = this.files[0].name;
      document.getElementById('photo-name').value = fileName;
    });

    function toggleSubmitButton() {
      const paymentMethod = document.querySelector("select[name='payment-method']").value;
      const paymentPhoto = document.querySelector("input[name='payment-photo']").files.length > 0;
      const termsCheckbox = document.getElementById("termsCheckbox").checked;

      const submitButton = document.getElementById("submitButton");

      // Enable the submit button if all conditions are met
      if (paymentMethod && paymentPhoto && termsCheckbox) {
          submitButton.disabled = false;
          submitButton.style.backgroundColor = "green";
          submitButton.style.cursor = "pointer";
      } else {
          submitButton.disabled = true;
          submitButton.style.backgroundColor = "gray";
          submitButton.style.cursor = "not-allowed";
      }
  }

  // Add event listeners to form elements
  document.querySelector("select[name='payment-method']").addEventListener("change", toggleSubmitButton);
  document.querySelector("input[name='payment-photo']").addEventListener("change", toggleSubmitButton);
  document.getElementById("termsCheckbox").addEventListener("change", toggleSubmitButton);

  function agreeToTerms() {
      document.getElementById('termsCheckbox').checked = true;
      closeTermsModal();
    }
  // Show Terms and Conditions modal
  function showTermsModal() {
      document.getElementById('termsModal').style.display = 'flex';
  }

  // Close Terms and Conditions modal
  function closeTermsModal() {
      document.getElementById('termsModal').style.display = 'none';
  }
  </script>
</body>
</html>
