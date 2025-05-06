<?php
session_start();
include 'includes/dbconnection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT contact_no, email, facebook, instagram, x_twitter, linkedin 
          FROM hinunangan_info_tbl 
          WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data) {
    echo json_encode(['status' => 'success', 'contact_no' => $data['contact_no'], 'email' => $data['email'], 
                      'facebook' => $data['facebook'], 'instagram' => $data['instagram'], 
                      'x_twitter' => $data['x_twitter'], 'linkedin' => $data['linkedin']]);
} else {
    echo json_encode(['status' => 'success', 'contact_no' => '', 'email' => '', 
                      'facebook' => '', 'instagram' => '', 
                      'x_twitter' => '', 'linkedin' => '']);
}

$stmt->close();
$conn->close();
