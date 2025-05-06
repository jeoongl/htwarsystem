session_start();
include 'includes/dbconnection.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['user_id'])) {
    header('Location: log-in.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reservation_form'])) {
    $reservation_date = htmlspecialchars($_POST['reservation_date']);
    $num_people = intval($_POST['num_people']);
    $business_id = intval($_POST['business_id']);
    $lastname = htmlspecialchars($_POST['lastname']);
    $firstname = htmlspecialchars($_POST['firstname']);
    $gender = htmlspecialchars($_POST['gender']);
    $tourist_type = htmlspecialchars($_POST['tourist_type']);
    $citizenship = htmlspecialchars($_POST['citizenship']);
    $address = htmlspecialchars($_POST['address']);
    $phone = htmlspecialchars($_POST['phone']);
    $email = htmlspecialchars($_POST['email']);

    // Debugging output
    echo "Last Name: $lastname, First Name: $firstname, Gender: $gender, Type: $tourist_type, Citizenship: $citizenship, Address: $address, Phone: $phone, Email: $email<br>";

    $sql = "INSERT INTO boat_passengers_tbl (
                lastname, firstname, gender, tourist_type,
                citizenship, address, phone, email
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssssss", $lastname, $firstname, $gender, $tourist_type, $citizenship, $address, $phone, $email);

        if ($stmt->execute()) {
            echo "Reservation submitted successfully!";
        } else {
            echo "Execute Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Prepare Error: " . $conn->error;
    }
    $conn->close();
}
