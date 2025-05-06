<?php
require_once('tcpdf.php');
include 'includes/dbconnection.php';

// Create new PDF document
$pdf = new TCPDF();

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Dashboard Report');
$pdf->SetSubject('Report');
$pdf->SetKeywords('TCPDF, PDF, report');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Title
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Businesses Report', 0, 1, 'C');
$pdf->Ln(10); // Add space after the title

// Fetch data from the database
$pending_query = "
    SELECT b.name, b.address, b.business_owner, c.category_name, b.business_status
    FROM businesses_tbl b
    JOIN categories_tbl c ON b.category = c.id
    WHERE b.business_status = 1
";
$pending_result = $conn->query($pending_query);

$registered_query = "
    SELECT b.name, b.address, b.business_owner, c.category_name, b.business_status
    FROM businesses_tbl b
    JOIN categories_tbl c ON b.category = c.id
    WHERE b.business_status = 2
";
$registered_result = $conn->query($registered_query);

// Table Header Style
$table_header_style = '
    <table border="1" cellpadding="5">
        <thead>
            <tr style="background-color:#f2f2f2;">
                <th><b>Business Name</b></th>
                <th><b>Address</b></th>
                <th><b>Business Owner</b></th>
                <th><b>Category</b></th>
                <th><b>Status</b></th>
            </tr>
        </thead>
        <tbody>
';

// Pending Businesses
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Write(0, 'Pending Businesses', '', 0, 'L', true, 0, false, false, 0);
$pdf->Ln(5); // Add space below the title
$pdf->SetFont('helvetica', '', 10);

if ($pending_result->num_rows > 0) {
    $table_content = $table_header_style;

    while ($row = $pending_result->fetch_assoc()) {
        $status = 'Pending';
        $table_content .= "
            <tr>
                <td>{$row['name']}</td>
                <td>{$row['address']}</td>
                <td>{$row['business_owner']}</td>
                <td>{$row['category_name']}</td>
                <td>{$status}</td>
            </tr>
        ";
    }
    $table_content .= '</tbody></table>';
    $pdf->writeHTML($table_content, true, false, false, false, '');
} else {
    $pdf->Write(0, 'No pending businesses.', '', 0, 'L', true, 0, false, false, 0);
}

// Add space between tables
$pdf->Ln(10);

// Registered Businesses
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Write(0, 'Registered Businesses', '', 0, 'L', true, 0, false, false, 0);
$pdf->Ln(5); // Add space below the title
$pdf->SetFont('helvetica', '', 10);

if ($registered_result->num_rows > 0) {
    $table_content = $table_header_style;

    while ($row = $registered_result->fetch_assoc()) {
        $status = 'Registered';
        $table_content .= "
            <tr>
                <td>{$row['name']}</td>
                <td>{$row['address']}</td>
                <td>{$row['business_owner']}</td>
                <td>{$row['category_name']}</td>
                <td>{$status}</td>
            </tr>
        ";
    }
    $table_content .= '</tbody></table>';
    $pdf->writeHTML($table_content, true, false, false, false, '');
} else {
    $pdf->Write(0, 'No registered businesses.', '', 0, 'L', true, 0, false, false, 0);
}

// Close and output PDF
$pdf->Output('dashboard-report.pdf', 'I'); // 'I' forces the PDF to open in the browser
?>
