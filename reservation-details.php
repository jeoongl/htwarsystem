<?php
require_once 'tcpdf.php'; // Ensure this is the correct path to your TCPDF library
include 'includes/dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    $reservation_id = intval($_POST['reservation_id']);

    // Fetch reservation details, including business_id
    $query = "SELECT 
        business_id,
        lastname, 
        firstname, 
        gender, 
        tourist_type, 
        num_people, 
        reservation_date, 
        reservation_time, 
        room_id,
        check_in,
        check_out,
        num_adults,
        num_children,
        num_rooms,
        phone_number, 
        email, 
        created_at, 
        payment_price, 
        payment_method,
        proof_of_payment,
        payment_status
    FROM reservations_tbl 
    WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();

    if (!$reservation) {
        echo "Reservation not found.";
        exit();
    }
    $stmt->close();

    // Fetch business details and category
    $business_id = $reservation['business_id'];
    $business_name = "Unknown Business";
    $business_address = "No Address Available";
    $category = null;

    if ($business_id) {
        $business_query = "SELECT name, address, category FROM businesses_tbl WHERE id = ?";
        $business_stmt = $conn->prepare($business_query);
        if (!$business_stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $business_stmt->bind_param("i", $business_id);
        $business_stmt->execute();
        $business_stmt->bind_result($business_name, $business_address, $category);
        $business_stmt->fetch();
        $business_stmt->close();
    }
    // Fetch boat price and environmental fee
    $boat_price = 0;
    $boat_envi_fee = 0;

    $fare_query = "SELECT boat_price, environmental_fee FROM boat_fare_tbl WHERE business_id = ?";
    $fare_stmt = $conn->prepare($fare_query);
    if (!$fare_stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $fare_stmt->bind_param("i", $business_id);
    $fare_stmt->execute();
    $fare_stmt->bind_result($boat_price, $boat_envi_fee);
    $fare_stmt->fetch();
    $fare_stmt->close();

    // Calculate fees
    $num_people = $reservation['num_people'];
    $total_boat_env_fee = $boat_envi_fee * $num_people;
    $total_boat_payment = $total_boat_env_fee + $boat_price;

    $attraction_price = 0;
    $eco_envi_fee = 0;
    $total_attraction_price = 0;

    $attraction_query = "SELECT registration_price, environmental_fee FROM registration_prices_tbl WHERE business_id = ?";
    $attraction_stmt = $conn->prepare($attraction_query);
    if (!$attraction_stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $attraction_stmt->bind_param("i", $business_id);
    $attraction_stmt->execute();
    $attraction_stmt->bind_result($attraction_price, $eco_envi_fee);
    $attraction_stmt->fetch();
    $attraction_stmt->close();

    $total_eco_env_fee = $eco_envi_fee * $num_people;
    $total_attraction_price = $attraction_price * $num_people;
    $total_eco_payment = $total_eco_env_fee + $total_attraction_price;

    // Fetch room type if category is 3
    $room_type = "N/A"; // Default value in case no room type is found
    $room_price = 0;

    if ($category == 3 && isset($reservation['room_id'])) {
        $room_id = $reservation['room_id'];
        $room_query = "SELECT room_type, price FROM rooms_tbl WHERE id = ?";
        $room_stmt = $conn->prepare($room_query);
        if (!$room_stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $room_stmt->bind_param("i", $room_id);
        $room_stmt->execute();
        $room_stmt->bind_result($room_type, $room_price);
        $room_stmt->fetch();
        $room_stmt->close();
    }

    $num_rooms = $reservation['num_rooms'];
    $total_hotel_payment = $room_price * $num_rooms;

    $table_query = "SELECT table_price FROM table_prices_tbl WHERE business_id = ?";
    $table_stmt = $conn->prepare($table_query);

    if (!$table_stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $table_stmt->bind_param("i", $business_id);
    $table_stmt->execute();
    $table_stmt->bind_result($table_price);
    $table_stmt->fetch();
    $table_stmt->close();

    // Create new PDF document
    $pdf = new TCPDF();
    $pdf->AddPage();

    // Business Name and Address
    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->Cell(0, 10, $business_name, 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 10, $business_address, 0, 1, 'C');

    if ($category == 1) {
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
            // Reservation Details
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(0, 10, "Booker's Info", 0, 1, 'C');

    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 8, 'Name: ' . $reservation['firstname'] . ' ' . $reservation['lastname'], 0, 1);
    $pdf->Cell(0, 8, 'Reservation Date: ' . $reservation['reservation_date'], 0, 1);
    $pdf->Cell(0, 8, 'Reservation Time: ' . $reservation['reservation_time'], 0, 1);
    $pdf->Cell(0, 8, 'Number of People: ' . $reservation['num_people'], 0, 1);
    $pdf->Cell(0, 8, 'Gender: ' . $reservation['gender'], 0, 1);
    $pdf->Cell(0, 8, 'Tourist Type: ' . $reservation['tourist_type'], 0, 1);
    $pdf->Cell(0, 8, 'Phone: ' . $reservation['phone_number'], 0, 1);
    $pdf->Cell(0, 8, 'Email: ' . $reservation['email'], 0, 1);
    $pdf->Cell(0, 8, 'Created At: ' . $reservation['created_at'], 0, 1);
    $pdf->Ln(10); // Add a line break

    // Payment Details
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Payment Details', 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 8, 'Environmental Fee: PHP ' . number_format($boat_envi_fee, 2) . ' x ' . $num_people . ' = PHP ' . number_format($total_boat_env_fee, 2), 0, 1);
    $pdf->Cell(0, 8, 'Boat Price: PHP ' . number_format($boat_price, 2), 0, 1);
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Total Payment: PHP ' . number_format($total_boat_payment, 2), 0, 1);

    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 8, 'Payment Status: ' . $reservation['payment_status'], 0, 1);
    $pdf->Cell(0, 8, 'Payment Method: ' . $reservation['payment_method'], 0, 1);

    } elseif ($category == 2) {
        $pdf->Ln(10);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Visitors Details', 0, 1, 'C');
        
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
        
        $visitors_query = "SELECT name, gender, age, tourist_type, citizenship, address, phone, email FROM eco_attraction_visitors_tbl WHERE bookers_id = ?";
        $stmt = $conn->prepare($visitors_query);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $visitors_result = $stmt->get_result();
        
        while ($row = $visitors_result->fetch_assoc()) {
            $pdf->Cell(30, 8, $row['name'], 1);
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
            // Reservation Details
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(0, 10, "Booker's Info", 0, 1, 'C');

    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 8, 'Name: ' . $reservation['firstname'] . ' ' . $reservation['lastname'], 0, 1);
    $pdf->Cell(0, 8, 'Reservation Date: ' . $reservation['reservation_date'], 0, 1);
    $pdf->Cell(0, 8, 'Reservation Time: ' . $reservation['reservation_time'], 0, 1);
    $pdf->Cell(0, 8, 'Number of People: ' . $reservation['num_people'], 0, 1);
    $pdf->Cell(0, 8, 'Gender: ' . $reservation['gender'], 0, 1);
    $pdf->Cell(0, 8, 'Tourist Type: ' . $reservation['tourist_type'], 0, 1);
    $pdf->Cell(0, 8, 'Phone: ' . $reservation['phone_number'], 0, 1);
    $pdf->Cell(0, 8, 'Email: ' . $reservation['email'], 0, 1);
    $pdf->Cell(0, 8, 'Created At: ' . $reservation['created_at'], 0, 1);
    $pdf->Ln(10); // Add a line break
    // Payment Details
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Payment Details', 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 8, 'Environmental Fee: PHP ' . number_format($eco_envi_fee, 2) . ' x ' . $num_people . ' = PHP ' . number_format($total_eco_env_fee, 2), 0, 1);
    $pdf->Cell(0, 8, 'Registration Fee: PHP ' . number_format($attraction_price, 2) . ' x ' . $num_people . ' = PHP ' . number_format($total_attraction_price, 2), 0, 1);
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Total Payment: PHP ' . number_format($total_eco_payment, 2), 0, 1);

    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 8, 'Payment Status: ' . $reservation['payment_status'], 0, 1);
    $pdf->Cell(0, 8, 'Payment Method: ' . $reservation['payment_method'], 0, 1);

    } elseif ($category == 3) {
        $pdf->Ln(10);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 10, "Booker's Info", 0, 1, 'C');
    
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 8, 'Name: ' . $reservation['firstname'] . ' ' . $reservation['lastname'], 0, 1);
        $pdf->Cell(0, 8, 'Check-in Date: ' . $reservation['check_in'], 0, 1);
        $pdf->Cell(0, 8, 'Check-out Date: ' . $reservation['check_out'], 0, 1);
        $pdf->Cell(0, 8, 'Number of Adults: ' . $reservation['num_adults'], 0, 1);
        $pdf->Cell(0, 8, 'Number of Children: ' . $reservation['num_children'], 0, 1);
        $pdf->Cell(0, 8, 'Number of Rooms: ' . $reservation['num_rooms'], 0, 1);
        $pdf->Cell(0, 8, 'Room Type: ' . $room_type, 0, 1);
        $pdf->Cell(0, 8, 'Gender: ' . $reservation['gender'], 0, 1);
        $pdf->Cell(0, 8, 'Tourist Type: ' . $reservation['tourist_type'], 0, 1);
        $pdf->Cell(0, 8, 'Phone: ' . $reservation['phone_number'], 0, 1);
        $pdf->Cell(0, 8, 'Email: ' . $reservation['email'], 0, 1);
        $pdf->Cell(0, 8, 'Created At: ' . $reservation['created_at'], 0, 1);
        $pdf->Ln(10);
    
        // Payment Details
        $pdf->Ln(10);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Payment Details', 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 8, 'Room Price: PHP ' . number_format($room_price, 2) . ' x ' . $num_rooms, 0, 1);
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Total Payment: PHP ' . number_format($total_hotel_payment, 2), 0, 1);
    
        $pdf->Ln(10);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 8, 'Payment Status: ' . $reservation['payment_status'], 0, 1);
        $pdf->Cell(0, 8, 'Payment Method: ' . $reservation['payment_method'], 0, 1);
    } elseif ($category == 4) {
    // Reservation Details
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(0, 10, "Booker's Info", 0, 1, 'C');

    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 8, 'Name: ' . $reservation['firstname'] . ' ' . $reservation['lastname'], 0, 1);
    $pdf->Cell(0, 8, 'Reservation Date: ' . $reservation['reservation_date'], 0, 1);
    $pdf->Cell(0, 8, 'Reservation Time: ' . $reservation['reservation_time'], 0, 1);
    $pdf->Cell(0, 8, 'Number of People: ' . $reservation['num_people'], 0, 1);
    $pdf->Cell(0, 8, 'Gender: ' . $reservation['gender'], 0, 1);
    $pdf->Cell(0, 8, 'Tourist Type: ' . $reservation['tourist_type'], 0, 1);
    $pdf->Cell(0, 8, 'Phone: ' . $reservation['phone_number'], 0, 1);
    $pdf->Cell(0, 8, 'Email: ' . $reservation['email'], 0, 1);
    $pdf->Cell(0, 8, 'Created At: ' . $reservation['created_at'], 0, 1);
    $pdf->Ln(10);

    // Payment Details
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Payment Details', 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 8, 'Table Price: PHP ' . number_format($table_price, 2), 0, 1);
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Total Payment: PHP ' . $reservation['payment_price'], 0, 1);

    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 8, 'Payment Status: ' . $reservation['payment_status'], 0, 1);
    $pdf->Cell(0, 8, 'Payment Method: ' . $reservation['payment_method'], 0, 1);

    } else {
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 8, 'No specific content for this category.', 0, 1);
    }



    // Save the PDF and output to browser
    $pdf->Output('reservation_details.pdf', 'I');

} else {
    echo "Invalid request or missing reservation ID.";
}

$conn->close();
?>
