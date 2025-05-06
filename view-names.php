<?php
// Include database connection
include 'includes/dbconnection.php';

if (isset($_GET['reservation_id'], $_GET['category_id'])) {
    $reservation_id = intval($_GET['reservation_id']);
    $category_id = intval($_GET['category_id']);

    if ($category_id === 1) {
        $query = "SELECT passenger_name FROM boat_passengers_tbl WHERE bookers_id = ?";
    } elseif ($category_id === 2) {
        $query = "SELECT name FROM eco_attraction_visitors_tbl WHERE bookers_id = ?";
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid category.']);
        exit;
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $people = [];
        while ($row = $result->fetch_assoc()) {
            $people[] = $row['passenger_name'] ?? $row['name'];
        }
        echo json_encode(['success' => true, 'people' => $people]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No people found.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
