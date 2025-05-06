<?php
require_once('tcpdf.php');
include 'includes/dbconnection.php';
session_start();

// Start output buffering
ob_start();

// Dashboard data fetching logic
include 'admin-dashboard.php'; // Reuse your data fetching logic here
// Create a new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Admin Dashboard');
$pdf->SetTitle('Dashboard Report');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 5, 'Dashboard Report', 0, 1, 'C');
$pdf->Ln(8);

// Add Visitor Statistics
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Visitor Statistics', 0, 1);
$pdf->SetFont('helvetica', '', 10);

$stats = [
    'Today' => $dataToday,
    'This Week' => $dataWeek,
    'This Month' => $dataMonth,
    'This Year' => $dataYear,
];

foreach ($stats as $period => $data) {
    $pdf->Cell(0, 5, $period, 0, 1);
    $pdf->Cell(40, 5, 'Total Visitors: ' . $data['total'], 0, 1);
    $pdf->Cell(40, 5, 'Male: ' . $data['male'], 0, 1);
    $pdf->Cell(40, 5, 'Female: ' . $data['female'], 0, 1);
    $pdf->Ln(5);
}
$pdf->Ln(5);
// Add Top 5 Tourist Spots for Each Category
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Top 5 Visited Places', 0, 1);

$categories = [
    'Eco Attractions' => $ecoAttractionsTop5,
    'Hotels' => $hotelsTop5,
    'Dining' => $diningTop5,
];

foreach ($categories as $category => $places) {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 5, $category, 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    foreach ($places as $place) {
        $pdf->Cell(0, 5, $place['name'] . ' - Reservations: ' . $place['count'], 0, 1);
    }
    $pdf->Ln(2);
}
$pdf->Ln(5);
// Add Other Dashboard Metrics
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Additional Metrics', 0, 1);
$pdf->SetFont('helvetica', '', 10);

$pdf->Cell(0, 5, 'Most Visited Day for Eco Attractions: ' . $mostVisitedDayEcoAttractions, 0, 1);
$pdf->Cell(0, 5, 'Most Visited Time for Dining: ' . $mostVisitedTimeDining, 0, 1);
$pdf->Cell(0, 5, 'Average Days of Stay for Hotels: ' . $averageDaysOfStayHotels . ' days', 0, 1);

ob_end_clean();
// Output PDF
$pdf->Output('dashboard_report.pdf', 'I');
