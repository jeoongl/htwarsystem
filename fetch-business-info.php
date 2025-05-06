<?php
include 'includes/dbconnection.php';

if (isset($_POST['id'])) {
    $business_id = $_POST['id'];

    // Prepare query to fetch business details
    $query = "
        SELECT b.name, b.business_owner, b.address, c.category_name, b.business_permit_no, 
               b.created_at, b.updated_at, b.business_status
        FROM businesses_tbl b
        JOIN categories_tbl c ON b.category = c.id
        WHERE b.id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $business_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch and output the business details as JSON
        $business = $result->fetch_assoc();
        echo json_encode($business);
    } else {
        echo json_encode(['error' => 'Business not found']);
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
